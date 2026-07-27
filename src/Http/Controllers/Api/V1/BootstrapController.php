<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Nicole\Box\Core\Http\Resources\Api\V1\Bootstrap\BootstrapDataResource;
use Nicole\Box\Core\Http\Resources\Api\V1\ComplexDictionaryResource;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Services\PricingManager;

/**
 * @group Core: Инициализация канала
 */
class BootstrapController extends Controller
{
  /**
   * Инициализация канала (Bootstrap).
   *
   * Единая точка входа для получения конфигурации канала (справочники, семейства, типы товаров, типы цен, базовая валюта и доступные пайплайны).
   */
  public function index(PricingManager $pricingManager): JsonResponse
  {
    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);

    $baseCurrency = $pricingManager->baseCurrency;

    $priceTypes = $pricingManager->channelPriceTypes;

    $dictionaries = ComplexDictionaryResource::collection(
      ComplexDictionary::query()
        ->where('is_active', true)
        ->publicInChannel($channel)
        ->with('records')
        ->orderBy('sort_order')
        ->get()
    );

    $families = ProductFamily::query()
      ->where('is_active', true)
      ->publicInChannel($channel)
      ->with(['types' => function ($q) use ($channel) {
        $q->where('is_active', true)->publicInChannel($channel)->orderBy('sort_order');
      }])
      ->orderBy('sort_order')
      ->get();

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

      'data' => new BootstrapDataResource([
        'base_currency' => $baseCurrency,
        'price_types' => $priceTypes,
        'dictionaries' => $dictionaries,
        'families' => $families,
        'pipelines' => $pipelines,
      ]),
    ]);
  }
}
