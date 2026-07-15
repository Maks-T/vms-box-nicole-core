<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Http\Resources\Api\V1\ProductResource;
use Nicole\Box\Core\Support\CatalogCache;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Core: Каталог
 */
class ProductController extends Controller
{
  /**
   * Получить товары или услуги по коду семейства.
   *
   * Возвращает список активных товаров или услуг для указанного семейства.
   * Поддерживает пагинацию и динамическую фильтрацию.
   *
   * @param string $family Символьный код семейства (например: stone, sink, faucet, accessory).
   */
  public function index(Request $request, string $family): Response
  {
    $limit = (int)$request->input('limit', $request->input('per_page', 12));
    $familyCode = Str::singular($family);

    $id = $request->input('id');
    $productTypeCode = $request->input('product_type');
    $catalogType = $request->input('catalog_type');

    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);
    $locale = app()->getLocale();
    $page = $request->input('page', 1);

    $attributes = $request->input('attr', []);

    // Если применили EAV-фильтры, выполняем быстрый запрос к БД напрямую
    if (!empty($attributes)) {
      $query = $this->buildBaseQuery($familyCode, $channel, $id, $catalogType, $productTypeCode, $attributes);
      return response()->json(ProductResource::collection($query->paginate($limit))->response()->getData(true));
    }

    $filterState = [
      'id' => $id,
      'product_type' => $productTypeCode,
      'catalog_type' => $catalogType,
    ];

    $cacheKey = "catalog_products_{$familyCode}_{$channel}_{$locale}_p{$page}_l{$limit}_" . md5(json_encode($filterState));

    $jsonResponse = CatalogCache::remember($cacheKey, 86400, function () use ($limit, $familyCode, $id, $catalogType, $productTypeCode, $channel) {
      $query = $this->buildBaseQuery($familyCode, $channel, $id, $catalogType, $productTypeCode, []);
      return json_encode(ProductResource::collection($query->paginate($limit))->response()->getData(true));
    });

    return response($jsonResponse)->header('Content-Type', 'application/json');
  }

  private function buildBaseQuery(string $familyCode, string $channel, $id, $catalogType, $productTypeCode, array $attributes)
  {
    return Product::query()
      ->where('is_active', true)
      ->publicInChannel($channel)
      ->whereHas('type.family', fn($q) => $q->where('code', $familyCode))
      ->when($id, fn($q) => $q->where('id', $id))
      ->when($catalogType, fn($q) => $q->where('catalog_type', $catalogType))
      ->when($productTypeCode, fn($q) => $q->whereHas('type', fn($t) => $t->where('code', $productTypeCode)))
      ->filterByEav($attributes)
      ->with([
        'unit',
        'type',
        'media',
        'attributeValues.attribute.complexDictionary',
        'attributeValues.attribute.productTypes',
        'attributeValues.option',
        'attributeValues.complexRecord.dictionary',
        'variants' => fn($v) => $v->where('is_active', true),
        'variants.product.type',
        'variants.media',
        'variants.attributeValues.attribute.productTypes',
        'variants.attributeValues.option',
        'variants.attributeValues.complexRecord.dictionary',
        'variants.prices.type.currency',
      ])
      ->orderBy('sort_order')
      ->orderBy('created_at', 'desc');
  }

}
