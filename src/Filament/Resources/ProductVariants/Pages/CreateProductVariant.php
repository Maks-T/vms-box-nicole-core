<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Concerns\HasDynamicEavFields;
use Nicole\Box\Core\Filament\Resources\ProductVariants\ProductVariantResource;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Services\Catalog\SkuGeneratorService;

class CreateProductVariant extends CreateRecord
{
    use HasDynamicEavFields;

    protected static string $resource = ProductVariantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['sku'])) {
            $product = Product::find($data['product_id'] ?? null);
            $data['sku'] = app(SkuGeneratorService::class)->generate($product);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveEavData($this->record, $this->data['eav'] ?? []);
    }
}
