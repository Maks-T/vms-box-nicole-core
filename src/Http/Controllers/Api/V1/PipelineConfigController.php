<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineConfigShowResource;
use Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineDetailResource;
use Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineRootVariantResource;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;

/**
 * @group Core: Цепочки конфигурации связей
 */
class PipelineConfigController extends Controller
{
  public function __construct(
    protected PipelineTreeService $treeService
  ) {}

  /**
   * Список всех доступных пайплайнов конфигураторов.
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
      'status' => 'success',
      'data' => PipelineDetailResource::collection($pipelines),
    ]);
  }

  /**
   * Получить схему пайплайна, список корневых элементов или дерево связей.
   *
   * @param Request $request
   * @param string $pipeline Системный code, ЧПУ-slug или external_code пайплайна
   * @param int|null $baseVariantId ID корневой модификации товара
   */
  public function show(Request $request, string $pipeline, ?int $baseVariantId = null): JsonResponse
  {
    $pipelineModel = Pipeline::query()
      ->where('code', $pipeline)
      ->orWhere('slug', $pipeline)
      ->orWhere('external_code', $pipeline)
      ->first();

    if (!$pipelineModel) {
      return response()->json([
        'status' => 'error',
        'message' => __('Pipeline not found.')
      ], 404);
    }

    if (!$baseVariantId) {
      $rootTypeCode = $this->treeService->resolveRootTypeCode($pipelineModel);

      $configuredVariantIds = BindingRule::query()
        ->where('pipeline_id', $pipelineModel->id)
        ->where('parent_type', (new ProductVariant())->getMorphClass())
        ->pluck('parent_id')
        ->unique();

      $rootVariantsQuery = ProductVariant::query()
        ->whereIn('id', $configuredVariantIds)
        ->where('is_active', true)
        ->whereHas('product', fn ($q) => $q->where('is_active', true));

      if ($rootTypeCode) {
        $rootVariantsQuery->whereHas('product.type', fn ($q) => $q->where('code', $rootTypeCode));
      }

      $rootVariants = $rootVariantsQuery
        ->with(['product.media', 'media'])
        ->get();

      return response()->json([
        'status' => 'success',
        'data' => [
          'pipeline' => new PipelineDetailResource($pipelineModel),
          'root_variants' => PipelineRootVariantResource::collection($rootVariants),
        ]
      ]);
    }

    $tree = $this->treeService->analyzeTree($baseVariantId, $pipelineModel->code);

    if (!$tree) {
      return response()->json([
        'status' => 'error',
        'message' => __('Configuration tree not found or inactive.')
      ], 404);
    }

    $bindings = $this->treeService->extractBindings($tree);

    return response()->json([
      'status' => 'success',
      'data' => new PipelineConfigShowResource([
        'pipeline_model' => $pipelineModel,
        'bindings' => $bindings,
        'tree' => $tree,
      ]),
    ]);
  }
}
