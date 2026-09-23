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

/**
 * Сервис управления бизнес-логикой сохранения, обновления и привязки заказов.
 *
 * @since 2026-09-06
 */
class OrderService
{
  /**
   * Транзакционное создание или обновление расчета (Upsert).
   *
   * @param array<string, mixed> $data Входящий пакет данных SaveData
   * @param Order|null $order Существующая модель заказа (при обновлении)
   * @param string|null $ipAddress IP-адрес клиента для логирования
   * @return Order
   */
  public function storeOrUpdate(array $data, ?Order $order = null, ?string $ipAddress = null): Order
  {
    return DB::transaction(function () use ($data, $order, $ipAddress) {
      $customer = $this->firstOrCreateCustomer($data['customer'] ?? null, $ipAddress);

      if ($order) {
        $this->updateExistingOrder($order, $data, $customer);
      } else {
        $order = $this->createNewOrder($data, $customer);
      }

      $this->recreateSectionsAndProducts($order, $data['results'] ?? []);

      return $order;
    });
  }

  /**
   * Поиск существующего клиента по телефону/email или создание нового.
   *
   * @param array<string, mixed>|null $customerData
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

    $customer = Customer::findByPhoneOrEmail($phone, $email);
    $providedName = !empty($customerData['name']) ? trim($customerData['name']) : null;

    if ($customer) {
      if ($providedName) {
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
      $customer->save();
    } else {
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
   * Нормализация состояния калькулятора (поддержка готового массива и legacy JSON-строки).
   *
   * @param mixed $state
   * @return array<string, mixed>
   *
   * @deprecated since 2026-09-06. Поддержка JSON-строки. Виджеты обязаны передавать структурированный объект.
   * @todo [CLEANUP-2026] Удалить ветку проверки is_string после обновления всех калькуляторов.
   */
  protected function normalizeCalcState(mixed $state): array
  {
    if (is_array($state)) {
      return $state;
    }

    if (is_string($state) && !empty($state)) {
      $decoded = json_decode($state, true);
      return is_array($decoded) ? $decoded : [];
    }

    return [];
  }

  /**
   * Определение названия заказа из входящих данных, имени проекта или автогенерация по дате.
   *
   * @param array<string, mixed> $data
   * @param array<string, mixed> $calcState
   * @return string
   *
   * @since 2026-09-06
   */
  protected function resolveOrderName(array $data, array $calcState): string
  {
    return (string)(
      $data['name']
      ?? $data['title']
      ?? ($calcState['project']['name'] ?? '')
      ?: ('Расчет от ' . date('d.m.Y H:i'))
    );
  }

  /**
   * Создание нового заказа с генерацией уникального номера КП.
   *
   * @param array<string, mixed> $data
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

    $calcState = $this->normalizeCalcState($data['calc_state'] ?? null);
    $orderName = $this->resolveOrderName($data, $calcState);

    return Order::create([
      'name' => $orderName,
      'code' => $orderCode,
      'customer_id' => $customer?->id,
      'grand_total' => (float)$data['grand_total'],
      'currency' => (string)($data['currency'] ?? 'RUB'),
      'locale' => (string)($data['locale'] ?? app()->getLocale()),
      'status_id' => $statusId,
      'customer_comment' => $data['customer_comment'] ?? null,
      'manager_comment' => $data['manager_comment'] ?? null,
      'calc_state' => $calcState,
      'manager_id' => !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
    ]);
  }

  /**
   * Обновление полей существующего заказа и очистка устаревших связей.
   *
   * @param Order $order
   * @param array<string, mixed> $data
   * @param Customer|null $customer
   * @return void
   */
  protected function updateExistingOrder(Order $order, array $data, ?Customer $customer): void
  {
    $calcState = $this->normalizeCalcState($data['calc_state'] ?? null);
    $orderName = $this->resolveOrderName($data, $calcState);

    $order->update([
      'name' => $orderName,
      'customer_id' => $customer ? $customer->id : $order->customer_id,
      'grand_total' => (float)$data['grand_total'],
      'currency' => (string)($data['currency'] ?? 'RUB'),
      'customer_comment' => $data['customer_comment'] ?? null,
      'manager_comment' => $data['manager_comment'] ?? null,
      'calc_state' => $calcState,
      'manager_id' => !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
    ]);

    OrderProduct::where('order_id', $order->id)->delete();

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
   * Создание секций изделия, прикрепление чертежей и привязка складских товаров.
   *
   * @param Order $order
   * @param array<int, array<string, mixed>> $results
   * @return void
   */
  protected function recreateSectionsAndProducts(Order $order, array $results): void
  {
    foreach ($results as $index => $resultData) {
      $price = $resultData['price'] ?? [];
      $meta = $resultData['meta'] ?? [];
      $sectionTitle = $resultData['title'] ?? ('Изделие №' . ($index + 1));

      // Тип изделия: берем явный type, либо fallback на форму/продукт
      $sectionType = $resultData['type']
        ?? ($meta['properties']['product'] ?? ($meta['properties']['form'] ?? 'custom'));

      $section = OrderSection::create([
        'order_id' => $order->id,
        'item_id' => $resultData['id'] ?? ('result_' . $index),
        'type' => $sectionType,
        'title' => $sectionTitle,
        'price_total' => (float)($price['total'] ?? $price['grand_total'] ?? 0),
        'price_grand_total' => (float)($price['grand_total'] ?? 0),
        'price_vat' => (float)($price['VAT'] ?? 0),
        'price_vat_percent' => (float)($price['VAT_percent'] ?? 0),
        'price_discount' => (float)($price['discount'] ?? 0),
        'price_discount_percent' => (float)($price['discount_percent'] ?? 0),
        'description' => $resultData['description'] ?? null,
        'estimate' => $resultData['estimate'] ?? null,
        'meta' => $meta,
      ]);

      if (!empty($resultData['draw']) && is_array($resultData['draw'])) {
        $this->attachDrawingsToSection($section, $resultData['draw']);
      }

      $this->bindCatalogProductsToSection(
        $order,
        $section,
        $meta['items'] ?? [],
        $resultData['estimate'] ?? []
      );
    }
  }

  /**
   * Декодирование Base64-чертежей и прикрепление в медиаколлекцию Spatie MediaLibrary.
   *
   * @param OrderSection $section
   * @param array<int, string> $drawings
   * @return void
   */
  protected function attachDrawingsToSection(OrderSection $section, array $drawings): void
  {
    foreach ($drawings as $drawIndex => $base64Image) {
      if (str_starts_with($base64Image, 'data:image')) {
        try {
          $section->addMediaFromBase64($base64Image)
            ->usingFileName("drawing_section_{$section->id}_{$drawIndex}.png")
            ->toMediaCollection('drawing');
        } catch (\Throwable $e) {
          Log::error("Failed to save drawing for section {$section->id}: " . $e->getMessage());
        }
      }
    }
  }

  /**
   * Привязка складских товаров каталога к изделию заказа (OrderProduct).
   *
   * @param Order $order
   * @param OrderSection $section
   * @param array<string, mixed> $items Сгруппированный словарь meta.items
   * @param array<int, mixed> $estimate Дерево сметы для legacy fallback
   * @return void
   *
   * @since 2026-09-06
   */
  protected function bindCatalogProductsToSection(
    Order $order,
    OrderSection $section,
    array $items,
    array $estimate
  ): void {
    $attachedVariants = [];

    // 1. Основной путь: читаем сгруппированные позиции из meta.items
    foreach ($items as $key => $itemList) {
      if (!is_array($itemList)) continue;

      // Для старых калькуляторов, где ключом был ID варианта
      $keyVariantId = is_numeric($key) ? (int)$key : null;

      foreach ($itemList as $item) {
        if (!is_array($item)) continue;

        $variantId = $item['variant_id']
          ?? ($item['meta']['variantId'] ?? ($item['meta']['variant_id'] ?? $keyVariantId));

        $quantity = (float)($item['quantity'] ?? 1);

        if ($variantId && is_numeric($variantId)) {
          $vId = (int)$variantId;
          OrderProduct::create([
            'order_id' => $order->id,
            'order_section_id' => $section->id,
            'product_variant_id' => $vId,
            'quantity' => $quantity,
          ]);
          $attachedVariants[$vId] = true;
        }
      }
    }

    // 2. Резервный путь для старых калькуляторов: вытаскиваем товары напрямую из строк сметы
    // @deprecated since 2026-09-06. Будет удалено, когда все виджеты перейдут на отправку meta.items.
    // @todo [CLEANUP-2026] Удалить после обновления калькулятора камня.
    if (empty($attachedVariants) && !empty($estimate)) {
      $this->extractProductsFromEstimate($order, $section, $estimate);
    }
  }

  /**
   * Рекурсивный поиск variantId в строках сметы.
   *
   * @param Order $order
   * @param OrderSection $section
   * @param array<int, mixed> $estimateNodes
   * @return void
   *
   * @deprecated since 2026-09-06. Legacy fallback. Используйте meta.items.
   * @todo [CLEANUP-2026] Удалить метод после того, как все виджеты перейдут на передачу meta.items.
   */
  protected function extractProductsFromEstimate(Order $order, OrderSection $section, array $estimateNodes): void
  {
    foreach ($estimateNodes as $node) {
      if (!is_array($node)) continue;

      $meta = $node['meta'] ?? [];
      $variantId = $meta['variantId'] ?? ($meta['variant_id'] ?? null);

      if ($variantId && is_numeric($variantId)) {
        $countValue = isset($node['value'][1]) && is_numeric($node['value'][1])
          ? (float)$node['value'][1]
          : 1.0;

        OrderProduct::create([
          'order_id' => $order->id,
          'order_section_id' => $section->id,
          'product_variant_id' => (int)$variantId,
          'quantity' => $countValue,
        ]);
      }

      if (!empty($node['children']) && is_array($node['children'])) {
        $this->extractProductsFromEstimate($order, $section, $node['children']);
      }
    }
  }

}