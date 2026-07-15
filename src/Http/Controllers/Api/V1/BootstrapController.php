<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Nicole\Box\Core\Http\Resources\Api\V1\ComplexDictionaryResource;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Support\Constants\SchemaKey;
use Nicole\Box\Core\Support\Constants\SettingKey as SK;

/**
 * @group Core: Инициализация канала
 */
class BootstrapController extends Controller
{
  /**
   * Инициализация канала (Bootstrap).
   *
   * Единая точка входа для получения конфигурации канала (справочники, семейства, типы товаров, типы цен и базовая валюта).
   */
  public function index(PricingManager $pricingManager): JsonResponse
  {
    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);
    $locale = app()->getLocale();

    $baseCurrency = $pricingManager->baseCurrency;

    // Сбор активных типов цен для текущего канала через PricingManager
    $priceTypes = $pricingManager->channelPriceTypes->map(function ($type) use ($locale) {
      return [
        /**
         * Системный идентификатор типа цены (напр., retail).
         * @var string
         */
        'slug' => $type->slug,
        /**
         * Название типа цены.
         * @var string
         */
        'name' => (string)$type->name,
        /**
         * Описание типа цены.
         * @var string|null
         */
        'description' => $type->description ? (string)$type->description : null,
        /**
         * Является ли тип цены основным (дефолтным) в системе.
         * @var bool
         */
        'is_default' => (bool)$type->is_default,
        /**
         * Валюта типа цены.
         */
        'currency' => $type->currency ? [
          /**
           * Международный трехбуквенный ISO-код валюты.
           * @var string
           * @example "RUB"
           */
          'code' => $type->currency->code,

          /**
           * Графический или укороченный символ валюты для компактного UI.
           * @var string
           * @example "₽"
           */
          'symbol' => $type->currency->symbol,

          /**
           * Официальный нативный символ или сокращение валюты для печатных форм и смет.
           * @var string
           * @example "руб."
           */
          'symbol_native' => $type->currency->getTranslation('symbol_native', $locale) ?? $type->currency->symbol,
        ] : null,
      ];
    })->values();
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
      ->get()
      ->map(function ($f) use ($channel, $locale) {

        $isFamilySettingsPublic = $f->settings['channels'][$channel][SK::IS_SETTINGS_PUBLIC] ?? false;
        $schema = [];

        if ($isFamilySettingsPublic && is_array($f->meta_schema)) {
          foreach ($f->meta_schema as $field) {
            $label = is_array($field[SchemaKey::LABEL])
              ? ($field[SchemaKey::LABEL][$locale] ?? $field[SchemaKey::KEY])
              : ($field[SchemaKey::LABEL] ?? $field[SchemaKey::KEY]);

            $schema[] = [
              SchemaKey::KEY => $field[SchemaKey::KEY],
              SchemaKey::TYPE => $field[SchemaKey::TYPE],
              SchemaKey::LABEL => $label,
            ];
          }
        }

        return [
          'code' => $f->code,
          'name' => (string)$f->name,
          'schema' => $isFamilySettingsPublic ? $schema : null,

          'types' => $f->types->map(function ($t) use ($channel) {
            $isTypeSettingsPublic = $t->settings['channels'][$channel][SK::IS_SETTINGS_PUBLIC] ?? false;

            return [
              'code' => $t->code,
              'name' => (string)$t->name,
              'meta' => $isTypeSettingsPublic ? (object)($t->meta ?? []) : (object)[]
            ];
          })->toArray(),
        ];
      });

    return response()->json([
      /**
       * Статус выполнения запроса.
       * @var string
       * @example "success"
       */
      'status' => 'success',

      'data' => [
        /**
         * Список поддерживаемых языков/локалей в системе.
         * @var array<int, string>
         * @example ["ru", "en"]
         */
        'languages' => config('nicole.locales', ['ru', 'en']),

        /**
         * Базовая валюта системы.
         */
        'base_currency' => [
          /**
           * Международный код базовой валюты системы.
           * @var string
           * @example "RUB"
           */
          'code' => $baseCurrency->code,
          /**
           * Графический символ базовой валюты.
           * @var string
           * @example "₽"
           */
          'symbol' => $baseCurrency->symbol,
          /**
           * Официальный нативный символ или сокращение валюты для печатных форм и смет.
           * @var string
           * @example "руб."
           */
          'symbol_native' => $baseCurrency->getTranslation('symbol_native', $locale) ?? $baseCurrency->symbol,
        ],

        /**
         * Доступные типы цен в этом канале продаж.
         * @var array<int, array{slug: string, name: string, description: string|null, is_default: bool, currency: array{code: string, symbol: string, symbol_native: string}|null}>
         */
        'price_types' => $priceTypes,

        /**
         * Умные справочники (матрицы цен, коэффициенты толщин, группы раскроя).
         * @var \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\Nicole\Box\Core\Http\Resources\Api\V1\ComplexDictionaryResource>
         */
        'dictionaries' => $dictionaries,

        /**
         * Дерево каталога: Семейства и вложенные Типы товаров.
         * @var array<int, array{code: string, name: string, schema: array<int, array{key: string, type: string, label: string}>|null, types: array<int, array{code: string, name: string, meta: object}>}>
         */
        'families' => $families,
      ],
    ]);
  }
}
