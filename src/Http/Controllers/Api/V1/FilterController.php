<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductAttributeValue;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Http\Resources\Api\V1\FilterResource;
use Nicole\Box\Core\Support\Constants\CatalogType;

/**
 * @group Core: Фильтры
 */
class FilterController extends Controller
{
  /**
   * Получить список фильтров.
   *
   * Возвращает список доступных атрибутов для фильтрации (с их настройками UI и опциями)
   * для указанного семейства товаров. Отсекает опции, которых нет в наличии.
   *
   * @param string $family Символьный код семейства (например: stone, sink, faucet, accessory)
   */
  public function index(Request $request, string $family): AnonymousResourceCollection
  {
    $familyCode = Str::singular($family);
    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);

    // ID активных и публичных товаров
    $productIds = Product::query()
      ->where('catalog_type', CatalogType::PRODUCT)
      ->where('is_active', true)
      ->publicInChannel($channel)
      ->whereHas('type.family', fn ($q) => $q->where('code', $familyCode))
      ->pluck('id');

    // ID активных вариаций
    $variantIds = ProductVariant::whereIn('product_id', $productIds)
      ->where('is_active', true)
      ->pluck('id');

    // ID реально используемых опций
    $usedOptionIds = ProductAttributeValue::query()
      ->usedInCatalog($productIds, $variantIds)
      ->distinct()
      ->pluck('value_option_id')
      ->toArray();

    // Загружаем публичные и фильтруемые атрибуты
    $attributes = Attribute::query()
      ->whereHas('productTypes.family', fn ($q) => $q->where('code', $familyCode))
      ->where('is_active', true)
      ->publicInChannel($channel)
      ->where("settings->channels->{$channel}->is_filterable", true)
      ->with(['options'])
      ->get();

    // Оставляем только те, у которых есть опции в наличии
    $filtered = $attributes->filter(function ($attr) use ($usedOptionIds) {
      if ($attr->type === Attribute::TYPE_DICTIONARY) {
        $validOptions = $attr->options->whereIn('id', $usedOptionIds)->values();
        $attr->setRelation('options', $validOptions);
        return $validOptions->isNotEmpty();
      }
      return true;
    });

    // Сортируем
    $sorted = $filtered->values()->sortBy(function ($attr) use ($channel) {
      return $attr->settings['channels'][$channel]['sort_order'] ?? $attr->sort_order;
    });

    return FilterResource::collection($sorted);
  }
}
