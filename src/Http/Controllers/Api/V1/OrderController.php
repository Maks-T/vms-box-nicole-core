<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Nicole\Box\Core\Http\Requests\Api\V1\SaveOrderRequest;
use Nicole\Box\Core\Http\Resources\Api\V1\OrderResource;
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
   * Сохранить новый расчет / заказ или обновить (если передан код заказа).
   *
   * Принимает полную спецификацию расчета из калькулятора.
   *
   * @param SaveOrderRequest $request Контролирует структуру входящих данных (SaveData)
   * @return OrderResource Возвращает данные созданного заказа и ссылки на экспортные файлы
   */
  public function save(SaveOrderRequest $request): OrderResource
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
   * @param SaveOrderRequest $request Контролирует структуру входящих данных (SaveData)
   * @param string $code Символьный код заказа для обновления (например: O-261-ABCD)
   * @return OrderResource Возвращает данные измененного заказа и ссылки на экспортные файлы
   */
  public function update(SaveOrderRequest $request, string $code): OrderResource
  {
    $order = Order::where('code', $code)->firstOrFail();

    $savedOrder = $this->orderService->storeOrUpdate($request->all(), $order, $request->ip());

    return $this->buildResponse($savedOrder);
  }

  /**
   * Получить данные заказа по коду (номеру).
   *
   * Возвращает детальную информацию о сохраненном заказе, включая информацию о покупателе, итоговой сумме и стейте калькулятора.
   *
   * @param string $code Символьный код заказа (например: O-261-ABCD)
   * @return OrderResource Детальные данные заказа по его коду
   */
  public function get(string $code): OrderResource
  {
    $order = Order::with('customer')->where('code', $code)->firstOrFail();

    return new OrderResource($order);
  }

  /**
   * Вспомогательный метод для обогащения ресурса экспортными мета-данными.
   *
   * @param Order $order Модель сохраненного или обновленного заказа
   * @return OrderResource
   */
  private function buildResponse(Order $order): OrderResource
  {
    $pdfUrl = url("/api/v1/orders/{$order->code}/pdf");
    $htmlUrl = url("/api/v1/orders/{$order->code}/html");

    $order->loadMissing('customer');

    $response = new OrderResource($order);

    $response->additional([
      /**
       * Статус выполнения операции.
       * @var string
       * @example "success"
       */
      'status' => 'success',

      /**
       * Сообщение о результате выполнения операции.
       * @var string
       */
      'message' => 'Заказ, спецификации и сметные товары успешно сохранены.',

      'data' => [
        'pdf_url' => $pdfUrl,
        'html_url' => $htmlUrl,
        'created_at_formatted' => $order->created_at->format('d.m.Y H:i'),
      ]
    ]);

    $response->response()->setStatusCode($order->wasRecentlyCreated ? 201 : 200);

    return $response;
  }

}