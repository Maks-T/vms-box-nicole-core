<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Customer;
use Nicole\Box\Core\Models\Order;
use Nicole\Box\Core\Models\OrderSection;
use Nicole\Box\Core\Models\OrderProduct;
use Nicole\Box\Core\Models\OrderStatus;
use Nicole\Box\Core\Support\CustomerHelper;
use Nicole\Box\Core\Support\PhoneHelper;

class OrderService
{
  /**
   * Выполняет транзакционную операцию создания или обновления заказа (Upsert).
   *
   * @param array $data Входящий пакет данных SaveData
   * @param Order|null $order Существующий заказ для обновления (при наличии)
   * @param string|null $ipAddress IP-адрес клиента для логирования
   * @return Order Возвращает сохраненную модель заказа
   */
  public function storeOrUpdate(array $data, ?Order $order = null, ?string $ipAddress = null): Order
  {
    return DB::transaction(function () use ($data, $order, $ipAddress) {

      // Поиск или создание покупателя
      $customer = $this->firstOrCreateCustomer($data['customer'] ?? null, $ipAddress);

      // Инициализация или обновление заказа
      if ($order) {
        $this->updateExistingOrder($order, $data, $customer);
      } else {
        $order = $this->createNewOrder($data, $customer);
      }

      // Перезапись спецификаций (результатов) и сметных продуктов
      $this->recreateSectionsAndProducts($order, $data['results'] ?? []);

      return $order;
    });
  }

  /**
   * Находит существующего покупателя по телефону или email, обновляет его данные или создает нового.
   *
   * @param array|null $customerData
   * @param string|null $ipAddress
   * @return Customer|null
   */
  protected function firstOrCreateCustomer(?array $customerData, ?string $ipAddress): ?Customer
  {
    if (empty($customerData['phone']) && empty($customerData['email'])) {
      return null;
    }

    $phone = !empty($customerData['phone']) ? (string)$customerData['phone'] : null;
    $email = !empty($customerData['email']) ? trim(strtolower((string)$customerData['email'])) : null;

    // Поиск инкапсулирован внутри модели Customer
    $customer = Customer::findByPhoneOrEmail($phone, $email);

    $providedName = !empty($customerData['name']) ? trim($customerData['name']) : null;

    if ($customer) {
      // Обновление клиента
      if ($providedName) {
        // Мутатор модели сам разберет строку ФИО и обновит поля
        $customer->full_name = $providedName;
      }

      if ($email && $customer->email !== $email) {
        $customer->email = $email;
      }

      if (!empty($customerData['address']) && $customer->address !== $customerData['address']) {
        $customer->address = $customerData['address'];
      }

      if ($phone && $customer->phone !== $phone) {
        $customer->phone = $phone;
      }

      if ($ipAddress) {
        $customer->last_ip = $ipAddress;
      }

      // Eloquent автоматически проверит изменения и сохранит только обновленные поля
      $customer->save();
    } else {
      // Создание клиента
      $customer = new Customer([
        'phone' => $phone,
        'email' => $email,
        'address' => $customerData['address'] ?? null,
        'last_ip' => $ipAddress,
      ]);
      $customer->full_name = $providedName;
      $customer->save();
    }

    return $customer;
  }

  /**
   * Обновляет базовые поля существующего заказа и очищает каскадные связи.
   *
   * @param Order $order
   * @param array $data
   * @param Customer|null $customer
   * @return void
   */
  protected function updateExistingOrder(Order $order, array $data, ?Customer $customer): void
  {
    $calcState = $data['calc_state'] ?? [];

    $order->update([
      'customer_id' => $customer ? $customer->id : $order->customer_id,
      'grand_total' => $data['grand_total'],
      'currency' => $data['currency'] ?? 'RUB',
      'customer_comment' => $data['customer_comment'] ?? null,
      'manager_comment' => $data['manager_comment'] ?? null,
      'calc_state' => is_array($calcState) ? $calcState : json_decode((string)$calcState, true),
      'manager_id' => $data['manager_id'] ?? null,
    ]);

    // Очищаем старые привязанные сметные продукты во избежание дублирования
    OrderProduct::where('order_id', $order->id)->delete();

    // Каскадно удаляем разделы с очисткой медиа-коллекций чертежей
    $order->sections->each(function (OrderSection $section) {
      if (method_exists($section, 'clearMediaCollection')) {
        try {
          $section->clearMediaCollection('drawing');
        } catch (\Throwable $e) {
          Log::error("Failed to clear drawings for order section {$section->id}: " . $e->getMessage());
        }
      }
      $section->delete();
    });
  }

  /**
   * Создает новый заказ с генерацией уникального кода КП.
   *
   * @param array $data
   * @param Customer|null $customer
   * @return Order
   */
  protected function createNewOrder(array $data, ?Customer $customer): Order
  {
    $prefix = env('VMS_ORDER_PREFIX', 'O');
    $year = date('y');
    $sequence = Order::count() + 1;

    do {
      $suffix = strtoupper(Str::random(4));
      $orderCode = "{$prefix}-{$year}{$sequence}-{$suffix}";
    } while (Order::where('code', $orderCode)->exists());

    $statusId = OrderStatus::where('is_default', true)->value('id')
      ?? OrderStatus::where('is_active', true)->value('id');

    $calcState = $data['calc_state'] ?? [];

    return Order::create([
      'code' => $orderCode,
      'customer_id' => $customer?->id,
      'grand_total' => $data['grand_total'],
      'currency' => $data['currency'] ?? 'RUB',
      'locale' => app()->getLocale(),
      'status_id' => $statusId,
      'customer_comment' => $data['customer_comment'] ?? null,
      'manager_comment' => $data['manager_comment'] ?? null,
      'calc_state' => is_array($calcState) ? $calcState : json_decode((string)$calcState, true),
      'manager_id' => $data['manager_id'] ?? null,
    ]);
  }

  /**
   * Разбирает массив results и пересоздает вложенные разделы, чертежи и физические продукты.
   *
   * @param Order $order
   * @param array $results
   * @return void
   */
  protected function recreateSectionsAndProducts(Order $order, array $results): void
  {
    foreach ($results as $index => $resultData) {
      $price = $resultData['price'];
      $meta = $resultData['meta'] ?? [];
      $sectionTitle = $resultData['title'] ?? ('Изделие №' . ($index + 1));

      $section = OrderSection::create([
        'order_id' => $order->id,
        'item_id' => $resultData['id'] ?? ('result_' . $index),
        'type' => $resultData['type'] ?? ($meta['properties']['product'] ?? null),
        'title' => $sectionTitle,
        'price_total' => $price['total'],
        'price_grand_total' => $price['grand_total'],
        'price_vat' => $price['VAT'] ?? 0,
        'price_vat_percent' => $price['VAT_percent'] ?? 0,
        'price_discount' => $price['discount'] ?? 0,
        'price_discount_percent' => $price['discount_percent'] ?? 0,
        'description' => $resultData['description'] ?? null,
        'estimate' => $resultData['estimate'] ?? null,
        'meta' => $meta,
      ]);

      // Прикрепление чертежей к разделу
      if (!empty($resultData['draw']) && is_array($resultData['draw'])) {
        $this->attachDrawingsToSection($section, $resultData['draw']);
      }

      // Связывание физических товаров из каталога
      if (!empty($meta['items']) && is_array($meta['items'])) {
        $this->bindCatalogProductsToSection($order, $section, $meta['items']);
      }
    }
  }

  /**
   * Конвертирует Base64 и прикрепляет чертежи в медиа-коллекцию Spatie MediaLibrary.
   *
   * @param OrderSection $section
   * @param array $drawings
   * @return void
   */
  protected function attachDrawingsToSection(OrderSection $section, array $drawings): void
  {
    foreach ($drawings as $drawIndex => $base64Image) {
      if (str_starts_with($base64Image, 'data:image')) {
        try {
          $section->addMediaFromBase64($base64Image)
            ->usingFileName("drawing_section_{$section->id}_{$drawIndex}.png")
            ->usingName($section->title . " - Чертеж " . ($drawIndex + 1))
            ->toMediaCollection('drawing');
        } catch (\Throwable $e) {
          Log::error("Failed to save drawing {$drawIndex} for order section {$section->id}: " . $e->getMessage());
        }
      }
    }
  }

  /**
   * Создает строки OrderProduct для связи заказа со складскими SKU.
   *
   * @param Order $order
   * @param OrderSection $section
   * @param array $items
   * @return void
   */
  protected function bindCatalogProductsToSection(Order $order, OrderSection $section, array $items): void
  {
    foreach ($items as $groupKey => $subItems) {
      if (is_array($subItems)) {
        foreach ($subItems as $subItem) {
          if (!empty($subItem['variant_id'])) {
            $variantId = (int)$subItem['variant_id'];

            OrderProduct::create([
              'order_id' => $order->id,
              'order_section_id' => $section->id,
              'product_variant_id' => $variantId,
              'quantity' => $subItem['quantity'] ?? 1.000,
            ]);
          }
        }
      }
    }
  }

}