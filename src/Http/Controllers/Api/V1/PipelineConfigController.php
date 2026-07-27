<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nicole\Box\Core\Http\Resources\Api\V1\Pipeline\PipelineConfigShowResource;
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
  protected PipelineTreeService $treeService;

  public function __construct(PipelineTreeService $treeService)
  {
    $this->treeService = $treeService;
  }

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
      ->get()
      ->map(function (Pipeline $p) {
        return [
          'id' => $p->id,
          'code' => $p->code,
          'slug' => $p->slug,
          'name' => (string)$p->name,
          'description' => $p->description ? (string)$p->description : null,
          'schema' => $this->treeService->getPipelineSchema($p->code, $p),
        ];
      });

    return response()->json([
      'status' => 'success',
      'data' => $pipelines,
    ]);
  }

  /**
   * Получить схему пайплайна, список корневых элементов или дерево связей.
   *
   * Если baseVariantId не передан - возвращает информацию о пайплайне и список доступных корневых SKU (root_variants).
   * Если baseVariantId передан - возвращает информацию о пайплайне и вычисленное дерево связей (tree).
   *
   * @param Request $request
   * @param string $pipeline Системный code, ЧПУ-slug или external_code пайплайна
   * @param int|null $baseVariantId ID корневой модификации товара (необязательно)
   */
  public function show(Request $request, string $pipeline, ?int $baseVariantId = null): JsonResponse
  {
    $pipelineModel = Pipeline::where('code', $pipeline)
      ->orWhere('slug', $pipeline)
      ->orWhere('external_code', $pipeline)
      ->first();

    if (!$pipelineModel) {
      return response()->json([
        'status' => 'error',
        'message' => __('Pipeline not found.')
      ], 404);
    }

    $schema = $this->treeService->getPipelineSchema($pipelineModel->code, $pipelineModel);

    $pipelineData = [
      'id' => $pipelineModel->id,
      'code' => $pipelineModel->code,
      'slug' => $pipelineModel->slug,
      'name' => (string)$pipelineModel->name,
      'description' => $pipelineModel->description ? (string)$pipelineModel->description : null,
      'schema' => $schema,
    ];

    if (!$baseVariantId) {
      $configuredVariantIds = BindingRule::query()
        ->where('pipeline_id', $pipelineModel->id)
        ->where('parent_type', (new ProductVariant())->getMorphClass())
        ->pluck('parent_id')
        ->unique();

      $rootVariants = ProductVariant::query()
        ->whereIn('id', $configuredVariantIds)
        ->where('is_active', true)
        ->whereHas('product', fn($q) => $q->where('is_active', true))
        ->with(['product.media', 'media'])
        ->get();

      return response()->json([
        'status' => 'success',
        'data' => [
          'pipeline' => $pipelineData,
          /**
           * Список доступных корневых элементов (SKU) для старта расчета.
           * @var \Illuminate\Http\Resources\Json\AnonymousResourceCollection<PipelineRootVariantResource>
           */
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
      /**
       * Статус выполнения операции.
       * @var string
       * @example "success"
       */
      'status' => 'success',

      'data' => new PipelineConfigShowResource([
        'pipeline_model' => $pipelineModel,
        'bindings' => $bindings,
        'tree' => $tree,
      ]),
    ]);
  }
}
