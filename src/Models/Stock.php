<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nicole\Box\Core\Traits\HasSettings;

class Stock extends Model
{
    use HasSettings;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'reserved',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'reserved' => 'float',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    protected static function booted(): void
    {
        $syncVariantStock = function (Stock $stock) {
            $availableStock =
              $stock->variant
                  ->stocks()
                  ->selectRaw('SUM(quantity - reserved) as available')
                  ->value('available') ?? 0;

            $stock->variant->updateQuietly([
                'stock' => max(0, (float) $availableStock),
            ]);
        };

        static::saved($syncVariantStock);
        static::deleted($syncVariantStock);
    }
}
