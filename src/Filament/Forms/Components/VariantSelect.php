<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use Illuminate\Support\HtmlString;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\PricingManager;

class VariantSelect extends Select
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->allowHtml()
      ->searchable()
      ->getOptionLabelUsing(function ($value): ?string {
        $variant = ProductVariant::with(['product.media', 'media'])->find($value);

        return static::renderVariantOption($variant);
      })
      ->getSearchResultsUsing(function (string $search) {
        return ProductVariant::query()
          ->with(['product.media', 'media'])
          ->where(function ($query) use ($search) {
            $query->where('sku', 'ilike', "%{$search}%")
              ->orWhere('external_code', 'ilike', "%{$search}%")
              ->orWhereHas('product', function ($q) use ($search) {
                $q->where('name->ru', 'ilike', "%{$search}%")
                  ->orWhere('name->en', 'ilike', "%{$search}%");
              });
          })
          ->limit(15)
          ->get()
          ->mapWithKeys(fn($v) => [$v->id => static::renderVariantOption($v)]);
      });
  }

  public static function renderVariantOption(?ProductVariant $variant): string
  {
    if (!$variant) {
      return '';
    }

    $productName = $variant->product
      ? $variant->product->getTranslation('name', app()->getLocale())
      : __('Unknown Product');

    $sku = $variant->sku;
    $price = app(PricingManager::class)->getVariantPrice($variant);

    $pricingManager = app(PricingManager::class);
    $currencySymbol = $pricingManager->baseCurrency->symbol_native ?? ($pricingManager->baseCurrency->symbol ?? '₽');
    $formattedPrice = number_format($price, 0, '.', ' ') . ' ' . $currencySymbol;

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#f3f4f6"/><text x="50" y="54" font-family="sans-serif" font-size="40" font-weight="600" fill="#9ca3af" dominant-baseline="middle" text-anchor="middle">' . mb_strtoupper(mb_substr($productName, 0, 1)) . '</text></svg>';
    $fallbackImage = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $imageUrl = $variant->getPreviewUrl()
      ?? ($variant->product ? $variant->product->getPreviewUrl() : null)
      ?? $fallbackImage;

    return "
          <div style='display: flex; align-items: center; gap: 12px; padding: 4px 0;'>
              <img src='{$imageUrl}'
                   style='width: 40px; height: 40px; min-width: 40px; border-radius: 6px; object-fit: cover; border: 1px solid rgba(0,0,0,0.1); background-color: #f9fafb;'
                   alt=''
              />
              <div style='display: flex; align-items: center; gap: 12px; padding: 4px 0; overflow: hidden;'>
                  <span style='font-size: 0.875rem; font-weight: 500; color: inherit; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>
                      {$productName} · <span style='color: #6b7280; font-family: monospace;'>{$sku}</span>
                  </span>
                  <div style='display: flex; gap: 8px; margin-top: 4px; align-items: center; font-size: 0.7rem;'>
                       <span style='color: #0284c7; font-weight: 700; text-transform: uppercase;'>
                          {$formattedPrice}
                       </span>
                       <span style='color: #6b7280;'>
                          ID: {$variant->id}
                       </span>
                  </div>
              </div>
          </div>
        ";
  }
}
