<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Nicole\Box\Core\Http\Requests\Api\V1\SaveOrderRequest;
use Nicole\Box\Core\Models\Order;
use Nicole\Box\Core\Services\OrderService;

/**
 * @group Core: Заказы
 */
class OrderController extends Controller
{
  /**
   * Сервис для управления бизнес-логикой сохранения заказов.
   *
   * @var OrderService
   */
  protected OrderService $orderService;

  public function __construct(OrderService $orderService)
  {
    $this->orderService = $orderService;
  }

  /**
   * Сохранить новый расчет / заказ.
   *
   * Принимает полную спецификацию расчета из калькулятора.
   *
   * Возвращает уникальный системный код заказа, дату его создания и готовые публичные ссылки для просмотра (HTML) и печати (PDF) сметного отчета.
   *
   * @param SaveOrderRequest $request Контролирует структуру входящих данных (SaveData)
   * @return JsonResponse Возвращает ID заказа, его код, дату создания и ссылки на PDF/HTML
   */
  public function save(SaveOrderRequest $request): JsonResponse
  {
    $code = $request->input('code');
    $order = $code ? Order::where('code', $code)->first() : null;

    $savedOrder = $this->orderService->storeOrUpdate($request->all(), $order, $request->ip());

    return $this->buildResponse($savedOrder);
  }

  /**
   * Обновить существующий расчет / заказ.
   *
   * Перезаписывает спецификации и калькуляционный стейт для уже зарегистрированного в СУБД документа по его уникальному коду.
   *
   * Возвращает системный код заказа, дату его изменения и готовые публичные ссылки для просмотра (HTML) и печати (PDF) сметного отчета.
   *
   * @param SaveOrderRequest $request Контролирует структуру входящих данных (SaveData)
   * @param string $code Символьный код заказа для обновления (например: O-261-ABCD)
   * @return JsonResponse Возвращает ID заказа, его код, дату изменения и ссылки на PDF/HTML
   */
  public function update(SaveOrderRequest $request, string $code): JsonResponse
  {
    $order = Order::where('code', $code)->firstOrFail();

    $savedOrder = $this->orderService->storeOrUpdate($request->all(), $order, $request->ip());

    return $this->buildResponse($savedOrder);
  }

  /**
   * Получить данные заказа по коду.
   *
   * Возвращает детальную информацию о ранее сохраненном заказе, включая информацию о покупателе, итоговой сумме и calc_state.
   *
   * @param string $code Символьный код заказа (например: O-261-ABCD)
   * @return JsonResponse Данные заказа по коду
   */
  public function get(string $code): JsonResponse
  {
    $order = Order::with('customer')->where('code', $code)->firstOrFail();

    return response()->json([
      'status' => true,
      'data' => [
        'id' => $order->id,
        'code' => $order->code,
        'grand_total' => $order->grand_total,
        'currency' => $order->currency,
        'locale' => $order->locale,
        'calc_state' => $order->calc_state,
        'customer' => $order->customer ? [
          'id' => $order->customer->id,
          'first_name' => $order->customer->first_name,
          'last_name' => $order->customer->last_name,
          'middle_name' => $order->customer->middle_name,
          'phone' => $order->customer->phone,
          'phone_normalized' => $order->customer->phone_normalized,
          'email' => $order->customer->email,
          'address' => $order->customer->address,
        ] : null,
        'created_at' => $order->created_at->toIso8601String(),
        'updated_at' => $order->updated_at->toIso8601String(),
      ]
    ]);
  }

  /**
   * Универсальное форматирование и сборка успешного JSON-ответа на экспорт.
   *
   * @param Order $order Модель сохраненного или обновленного заказа
   * @return JsonResponse Возвращает структурированные данные заказа и ссылки на экспортные файлы
   */
  private function buildResponse(Order $order): JsonResponse
  {
    $pdfUrl = url("/api/v1/orders/{$order->code}/pdf");
    $htmlUrl = url("/api/v1/orders/{$order->code}/html");

    return response()->json([
      'status' => 'success',
      'message' => 'Заказ, спецификации и сметные товары успешно сохранены.',
      'data' => [
        'order_id' => $order->id,
        'code' => $order->code,
        'external_code' => $order->external_code,
        'pdf_url' => $pdfUrl,
        'html_url' => $htmlUrl,
        'created_at' => $order->created_at->toIso8601String(),
        'created_at_formatted' => $order->created_at->format('d.m.Y H:i'),
      ]
    ], $order->wasRecentlyCreated ? 201 : 200);
  }
}
