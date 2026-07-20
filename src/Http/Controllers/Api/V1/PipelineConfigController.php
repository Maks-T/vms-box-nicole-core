<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nicole\Box\Core\Models\Pipeline;
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
   * Получить дерево связей и параметров для конкретного корня (SKU).
   *
   * @param Request $request
   * @param string $pipeline Системный code, ЧПУ-slug или external_code пайплайна
   * @param int $baseVariantId ID корневой модификации товара
   */
  public function show(Request $request, string $pipeline, int $baseVariantId): JsonResponse
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

    $tree = $this->treeService->analyzeTree($baseVariantId, $pipelineModel->code);

    if (!$tree) {
      return response()->json([
        'status' => 'error',
        'message' => __('Configuration tree not found or inactive.')
      ], 404);
    }

    return response()->json([
      'status' => 'success',
      'data' => $tree
    ]);
  }

}
