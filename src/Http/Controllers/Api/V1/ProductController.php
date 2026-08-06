<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Dedoc\Scramble\Attributes\IgnoreParam;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Nicole\Box\Core\Http\Resources\Api\V1\ProductResource;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Support\CatalogCache;

/**
 * @group Core: Каталог
 */
class ProductController extends Controller
{
  /**
   * Получить товары или услуги по коду семейства.
   *
   * Возвращает список активных товаров или услуг для указанного семейства (или `all` для поиска по всем семействам каталога).
   * Поддерживает пагинацию, сквозной полнотекстовый поиск по всем полям/атрибутам и динамическую EAV-фильтрацию.
   *
   * @param Request $request Объект HTTP-запроса
   * @param string $family Символьный код семейства (например: decking_system, fence_system, stone, sink, faucet, accessory, all)
   *
   * @response \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\Nicole\Box\Core\Http\Resources\Api\V1\ProductResource>
   * @return AnonymousResourceCollection<ProductResource>|Response|JsonResponse
   */
  #[IgnoreParam('q', 'query')]
  #[IgnoreParam('per_page', 'query')]
  #[QueryParameter(name: 'search', description: 'Поисковая строка (поддерживается также алиас `q`). Сквозной поиск по всем языкам, коду товара, артикулам (SKU) и EAV-атрибутам.', type: 'string', default: null, example: 'про')]
  #[QueryParameter(name: 'limit', description: 'Количество элементов на странице (поддерживается также алиас `per_page`). Передайте `all` или `-1` для получения всех результатов поиска без пагинации.', type: 'string', default: '12', example: '12')]
  #[QueryParameter(name: 'page', description: 'Номер страницы пагинации.', type: 'int', default: 1, example: 1)]
  #[QueryParameter(name: 'id', description: 'Уникальный ID товара для получения карточки конкретной позиции.', type: 'int', default: null, example: 187)]
  #[QueryParameter(name: 'product_type', description: 'Символьный код типа товара для фильтрации (например: `terraceBoard` или `acrylic_stone`).', type: 'string', default: null, example: 'terraceBoard')]
  #[QueryParameter(name: 'catalog_type', description: 'Тип сущности в каталоге: `product` (физический товар) или `service` (услуга обработки).', type: 'string', default: null, example: 'product')]
  #[QueryParameter(name: 'attr', description: 'Фильтрация по динамическим EAV-характеристикам (например: `attr[color]=white,gray`).', type: 'string', default: null, example: 'white,gray')]
  public function index(Request $request, string $family): AnonymousResourceCollection|Response|JsonResponse
  {
    $limitInput = $request->input('limit', $request->input('per_page', 12));
    $familyCode = Str::singular($family);

    $id = $request->input('id');
    $productTypeCode = $request->input('product_type');
    $catalogType = $request->input('catalog_type');
    $search = trim((string)$request->input('search', $request->input('q', '')));

    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);
    $locale = app()->getLocale();
    $page = (int) $request->input('page', 1);

    $attributes = $request->input('attr', []);

    // При вызове EAV-фильтров или поиска выполняем прямой поиск по всей базе БД
    if (!empty($attributes) || !empty($search)) {
      $query = $this->buildBaseQuery($familyCode, $channel, $id, $catalogType, $productTypeCode, $attributes, $search);

      // Поддержка получения всех найденных результатов без ограничения страницы (limit=all или limit=-1)
      if ($limitInput === 'all' || (int)$limitInput === -1) {
        return ProductResource::collection($query->get());
      }

      $limit = (int) $limitInput;
      return ProductResource::collection($query->paginate($limit));
    }

    $limit = (int) $limitInput;
    $filterState = [
      'id' => $id,
      'product_type' => $productTypeCode,
      'catalog_type' => $catalogType,
    ];

    $cacheKey = "catalog_products_{$familyCode}_{$channel}_{$locale}_p{$page}_l{$limit}_" . md5(json_encode($filterState));

    $jsonResponse = CatalogCache::remember($cacheKey, 86400, function () use ($limit, $familyCode, $id, $catalogType, $productTypeCode, $channel) {
      $query = $this->buildBaseQuery($familyCode, $channel, $id, $catalogType, $productTypeCode);
      return json_encode(ProductResource::collection($query->paginate($limit))->response()->getData(true));
    });

    return response($jsonResponse)->header('Content-Type', 'application/json');
  }

  private function buildBaseQuery(
    string $familyCode,
    string $channel,
    mixed $id = null,
    mixed $catalogType = null,
    mixed $productTypeCode = null,
    array $attributes = [],
    ?string $search = null
  ) {
    return Product::query()
      ->where('is_active', true)
      ->publicInChannel($channel)
      // Если передано семейство 'all' — ищем сквозняком по ВСЕМ семействам
      ->when($familyCode !== 'all', function ($q) use ($familyCode) {
        $q->whereHas('type.family', fn($f) => $f->where('code', $familyCode));
      })
      ->when($id, fn($q) => $q->where('id', $id))
      ->when($catalogType, fn($q) => $q->where('catalog_type', $catalogType))
      ->when($productTypeCode, fn($q) => $q->whereHas('type', fn($t) => $t->where('code', $productTypeCode)))
      ->search($search)
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