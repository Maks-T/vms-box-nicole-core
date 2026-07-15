<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Concerns\HasDynamicEavFields;
use Nicole\Box\Core\Filament\Resources\ProductVariants\ProductVariantResource;

class CreateProductVariant extends CreateRecord
{
    use HasDynamicEavFields;

    protected static string $resource = ProductVariantResource::class;

    protected function afterCreate(): void
    {
        $this->saveEavData($this->record, $this->data['eav'] ?? []);
    }
}
