<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Dedoc\Scramble\Attributes\PathParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineConfigShowResource;
use Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineDetailResource;
use Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineRootEntityResource;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;

/**
 * @group Core: Пайплайны и конфигураторы
 *
 * Управление схемами графов, слотами зависимостей и деревьями связей конфигуратора.
 */
class PipelineConfigController extends Controller
{
  public function __construct(
    protected PipelineTreeService $treeService
  )
  {
  }

  /**
   * Список всех доступных пайплайнов конфигураторов.
   *
   * Возвращает коллекцию активных пайплайнов с их мета-схемами слотов для текущего канала продаж.
   *
   * @response array{
   *   status: "success",
   *   data: \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineDetailResource>
   * }
   */
  public function index(): JsonResponse
  {
    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);

    $pipelines = Pipeline::query()
      ->where('is_active', true)
      ->publicInChannel($channel)
      ->orderBy('sort_order')
      ->get();

    return response()->json([
      /**
       * Статус выполнения запроса.
       * @var string
       * @example "success"
       */
      'status' => 'success',

      /**
       * Список активных пайплайнов со схемами.
       */
      'data' => PipelineDetailResource::collection($pipelines),
    ]);
  }

  /**
   * Получить схему пайплайна, список корневых элементов или дерево связей.
   *
   * 1. **Без `baseEntityId`**: возвращает метаданные пайплайна и список всех доступных стартовых сущностей (`root_entities`), с которых можно начать конфигурацию.
   * 2. **С `baseEntityId`**: возвращает готовую карту связей (`bindings`) и полное дерево зависимостей (`tree`) для выбранного стартового элемента.
   *
   * @param Request $request
   * @param string $pipeline Системный code, ЧПУ-slug или external_code пайплайна (например: `pl_terrace`)
   * @param int|null $baseEntityId ID корневой сущности (например, ID модификации SKU или ID записи справочника)
   *
   * @response 200 array{
   *   status: "success",
   *   data: array{
   *     pipeline: \Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineDetailResource,
   *     root_entities?: \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineRootEntityResource>,
   *     bindings?: object,
   *     tree?: \Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineTreeResource
   *   }
   * }
   * @response 404 array{status: "error", message: "Пайплайн не найден."}
   */
  #[PathParameter('pipeline', description: 'Системный код (code), slug или external_code пайплайна.', type: 'string', example: 'pl_terrace')]
  #[PathParameter('baseEntityId', description: 'Уникальный ID корневой сущности (SKU / записи). Если не передан, отдается список всех корневых сущностей.', type: 'int', default: null, example: 154)]
  public function show(Request $request, string $pipeline, ?int $baseEntityId = null): JsonResponse
  {
    $pipelineModel = $this->findPipeline($pipeline);

    if (!$pipelineModel) {
      return response()->json([
        'status' => 'error',
        'message' => __('Pipeline not found.')
      ], 404);
    }

    if (!$baseEntityId) {
      return $this->showRootEntities($pipelineModel);
    }

    $entityType = (string)$request->query('entity_type', (new ProductVariant())->getMorphClass());

    return $this->showPipelineTree($pipelineModel, $baseEntityId, $entityType);
  }

  /**
   * Режим 1: Отдача схемы пайплайна и списка всех доступных стартовых сущностей.
   */
  private function showRootEntities(Pipeline $pipeline): JsonResponse
  {
    $rootTypeCode = $this->treeService->resolveRootTypeCode($pipeline);

    $configuredVariantIds = BindingRule::query()
      ->where('pipeline_id', $pipeline->id)
      ->where('parent_type', (new ProductVariant())->getMorphClass())
      ->pluck('parent_id')
      ->unique();

    $rootVariantsQuery = ProductVariant::query()
      ->whereIn('id', $configuredVariantIds)
      ->where('is_active', true)
      ->whereHas('product', fn($q) => $q->where('is_active', true));

    if ($rootTypeCode) {
      $rootVariantsQuery->whereHas('product.type', fn($q) => $q->where('code', $rootTypeCode));
    }

    $rootEntities = $rootVariantsQuery
      ->with(['product.media', 'media'])
      ->get();

    return response()->json([
      /**
       * Статус ответа.
       * @var string
       * @example "success"
       */
      'status' => 'success',

      'data' => [
        /**
         * Метаданные и схема пайплайна.
         */
        'pipeline' => new PipelineDetailResource($pipeline),

        /**
         * Список корневых сущностей (стартовых элементов) для старта расчета.
         * @var \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineRootEntityResource>
         */
        'root_entities' => PipelineRootEntityResource::collection($rootEntities),
      ]
    ]);
  }

  /**
   * Режим 2: Анализ графа зависимостей и отдача карты связей (bindings) и дерева (tree).
   */
  private function showPipelineTree(Pipeline $pipeline, int $baseEntityId, string $entityType = 'product_variant'): JsonResponse
  {
    $tree = $this->treeService->analyzeTree($baseEntityId, $pipeline->code, $entityType);

    if (!$tree) {
      return response()->json([
        'status' => 'error',
        'message' => __('Configuration tree not found or inactive.')
      ], 404);
    }

    $bindings = $this->treeService->extractBindings($tree);

    return response()->json([
      /**
       * Статус ответа.
       * @var string
       * @example "success"
       */
      'status' => 'success',

      'data' => new PipelineConfigShowResource([
        'pipeline_model' => $pipeline,
        'bindings' => $bindings,
        'tree' => $tree,
      ]),
    ]);
  }

  /**
   * Поиск модели пайплайна по code, slug или external_code.
   */
  private function findPipeline(string $identifier): ?Pipeline
  {
    return Pipeline::query()
      ->where('code', $identifier)
      ->orWhere('slug', $identifier)
      ->orWhere('external_code', $identifier)
      ->first();
  }
}
