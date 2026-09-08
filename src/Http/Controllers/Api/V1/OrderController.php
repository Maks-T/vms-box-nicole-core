<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Dedoc\Scramble\Attributes\IgnoreParam;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Nicole\Box\Core\Http\Requests\Api\V1\SaveOrderRequest;
use Nicole\Box\Core\Http\Resources\Api\V1\OrderListItemResource;
use Nicole\Box\Core\Http\Resources\Api\V1\OrderResource;
use Nicole\Box\Core\Models\Order;
use Nicole\Box\Core\Models\OrderProduct;
use Nicole\Box\Core\Models\OrderSection;
use Nicole\Box\Core\Services\OrderService;

/**
 * @group Core: Заказы
 *
 * Управление расчетами, сохранение смет, привязка покупателей и экспорт документов.
 */
class OrderController extends Controller
{
  /**
   * Сервис для управления бизнес-логикой сохранения заказов.
   */
  protected OrderService $orderService;

  public function __construct(OrderService $orderService)
  {
    $this->orderService = $orderService;
  }

  /**
   * Получить список сохраненных заказов / проектов.
   *
   * Возвращает пагинированный список заказов для текущего канала продаж с поддержкой поиска по коду/клиенту и фильтрации по менеджеру и статусу.
   *
   * @param Request $request
   * @return AnonymousResourceCollection<OrderListItemResource>
   *
   * @since 2026-09-06
   */
  #[IgnoreParam('q', 'query')]
  #[IgnoreParam('per_page', 'query')]
  #[QueryParameter(name: 'search', description: 'Поисковая строка (поддерживается также алиас `q`). Поиск по коду заказа, названию проекта, ФИО и телефону клиента.', type: 'string', default: null, example: 'O-261')]
  #[QueryParameter(name: 'limit', description: 'Количество элементов на странице (поддерживается также алиас `per_page`).', type: 'int', default: 12, example: 12)]
  #[QueryParameter(name: 'page', description: 'Номер страницы пагинации.', type: 'int', default: 1, example: 1)]
  #[QueryParameter(name: 'manager_id', description: 'Фильтр по уникальному ID ответственного менеджера.', type: 'int', default: null, example: 1)]
  #[QueryParameter(name: 'status', description: 'Символьный код (slug) статуса заказа (например: `new`, `draft`).', type: 'string', default: null, example: 'draft')]
  public function index(Request $request): AnonymousResourceCollection
  {
    $limit = (int) $request->input('limit', $request->input('per_page', 12));
    $search = trim((string) $request->input('search', $request->input('q', '')));
    $managerId = $request->input('manager_id');
    $statusSlug = $request->input('status');

    $query = Order::query()
      ->with(['customer', 'status', 'manager'])
      ->when($managerId, fn ($q) => $q->where('manager_id', $managerId))
      ->when($statusSlug, fn ($q) => $q->whereHas('status', fn ($s) => $s->where('slug', $statusSlug)))
      ->when($search, function ($q) use ($search) {
        $searchTerm = '%' . $search . '%';
        $q->where(function ($sub) use ($searchTerm) {
          $sub->where('code', 'ILIKE', $searchTerm)
            ->orWhere('name', 'ILIKE', $searchTerm)
            ->orWhereHas('customer', function ($cQ) use ($searchTerm) {
              $cQ->where('first_name', 'ILIKE', $searchTerm)
                ->orWhere('last_name', 'ILIKE', $searchTerm)
                ->orWhere('phone', 'ILIKE', $searchTerm)
                ->orWhere('email', 'ILIKE', $searchTerm);
            });
        });
      })
      ->orderBy('created_at', 'desc');

    return OrderListItemResource::collection($query->paginate($limit));
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
  #[PathParameter('code', description: 'Символьный код заказа для обновления.', type: 'string', example: 'O-261-ABCD')]
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
  #[PathParameter('code', description: 'Символьный код заказа.', type: 'string', example: 'O-261-ABCD')]
  public function get(string $code): OrderResource
  {
    $order = Order::with('customer')->where('code', $code)->firstOrFail();

    return new OrderResource($order);
  }

  /**
   * Удалить заказ по уникальному коду.
   *
   * Выполняет транзакционное удаление заказа, каскадно удаляя привязанные секции, чертежи и складские позиции.
   *
   * @param string $code Символьный код заказа для удаления (например: O-261-ABCD)
   * @return JsonResponse
   *
   * @since 2026-09-06
   */
  #[PathParameter('code', description: 'Символьный код заказа для удаления.', type: 'string', example: 'O-261-ABCD')]
  public function destroy(string $code): JsonResponse
  {
    $order = Order::where('code', $code)->firstOrFail();

    DB::transaction(function () use ($order) {
      // Удаляем складские связи
      OrderProduct::where('order_id', $order->id)->delete();

      // Очищаем медиаколлекции чертежей и удаляем секции
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

      // Удаляем сам заказ
      $order->delete();
    });

    return response()->json([
      /**
       * Статус выполнения операции.
       * @var string
       * @example "success"
       */
      'status' => 'success',

      /**
       * Сообщение о результате удаления.
       * @var string
       * @example "Заказ успешно удален."
       */
      'message' => 'Заказ успешно удален.',
    ]);
  }

  /**
   * Вспомогательный метод для обогащения ресурса статусными мета-данными.
   *
   * @param Order $order Модель сохраненного или обновленного заказа
   * @return OrderResource
   */
  private function buildResponse(Order $order): OrderResource
  {
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
       * @example "Заказ, спецификации и сметные товары успешно сохранены."
       */
      'message' => 'Заказ, спецификации и сметные товары успешно сохранены.',
    ]);

    $response->response()->setStatusCode($order->wasRecentlyCreated ? 201 : 200);

    return $response;
  }
}