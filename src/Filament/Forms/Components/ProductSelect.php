<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use Nicole\Box\Core\Models\Product;

class ProductSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->allowHtml()
            ->searchable()
            ->getOptionLabelUsing(function ($value): ?string {
                $product = Product::with('media')->find($value);

                return static::renderProductOption($product);
            })
            ->getSearchResultsUsing(function (string $search) {
                return Product::query()
                    ->with('media')
                    ->where('name->ru', 'ilike', "%{$search}%")
                    ->limit(15)
                    ->get()
                    ->mapWithKeys(fn ($p) => [$p->id => static::renderProductOption($p)]);
            });
    }

    public static function renderProductOption(?Product $product): string
    {
        if (! $product) {
            return '';
        }

        $name = is_string($product->name) ? $product->name : 'Без названия';
        $price = number_format((float) $product->min_price, 0, '.', ' ');
        $initial = mb_strtoupper(mb_substr($name, 0, 1));

        $svg =
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#f3f4f6"/><text x="50" y="54" font-family="sans-serif" font-size="40" font-weight="600" fill="#9ca3af" dominant-baseline="middle" text-anchor="middle">'.
          $initial.
          '</text></svg>';
        $fallbackImage = 'data:image/svg+xml;base64,'.base64_encode($svg);

        $imageUrl = $product->getPreviewUrl() ?: $fallbackImage;

        return "
      <div style='display: flex; align-items: center; gap: 12px; padding: 4px 0;'>
          <img src='{$imageUrl}'
               style='width: 40px; height: 40px; min-width: 40px; border-radius: 6px; object-fit: cover; border: 1px solid rgba(0,0,0,0.1); background-color: #f9fafb;'
               alt=''
          />
          <div style='display: flex; flex-direction: column; line-height: 1.2;'>
              <span style='font-size: 0.875rem; font-weight: 500; color: inherit;'>
                  {$name}
              </span>
              <div style='display: flex; gap: 8px; margin-top: 4px; align-items: center;'>
                   <span style='color: #0284c7; font-weight: 700; font-size: 0.7rem; text-transform: uppercase;'>
                      от {$price} ₽
                   </span>
                   <span style='color: #6b7280; font-size: 0.7rem; font-family: monospace;'>
                      ID: {$product->id}
                   </span>
              </div>
          </div>
      </div>
    ";
    }
}
