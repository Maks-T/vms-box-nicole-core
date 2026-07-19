<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;

/**
 * @group Core: Конфигурация калькулятора
 */
class CalculatorConfigController extends Controller
{
  protected PipelineTreeService $treeService;

  public function __construct(PipelineTreeService $treeService)
  {
    $this->treeService = $treeService;
  }

  /**
   * Получить дерево связей и параметров для конкретного корня (SKU).
   */
  public function show(Request $request, int $baseVariantId): JsonResponse
  {
    $type = $request->query('type', 'terrace');
    $pipelineCode = $type === 'fence' ? 'pl_fence' : 'pl_terrace';

    $tree = $this->treeService->analyzeTree($baseVariantId, $pipelineCode);

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
