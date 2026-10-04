<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Concerns\HasDynamicEavFields;
use Nicole\Box\Core\Filament\Resources\ProductVariants\ProductVariantResource;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Services\Catalog\SkuGeneratorService;

class EditProductVariant extends EditRecord
{
    use HasDynamicEavFields;

    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->loadEavData($this->record, $data);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['sku'])) {
            $product = $this->record->product ?? Product::find($data['product_id'] ?? null);
            $data['sku'] = app(SkuGeneratorService::class)->generate($product);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->saveEavData($this->record, $this->data['eav'] ?? []);
        $this->data['sku'] = $this->record->sku;
    }
}
