<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Nicole\Box\Core\Models\ProductVariant;

class ActivateTreeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'activateTree';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->action(function (array $arguments) {
            $variantId = (int) ($arguments['variant_id'] ?? 0);
            $action = $arguments['action'] ?? 'activate';
            $status = $action === 'activate';

            $variant = ProductVariant::find($variantId);
            if ($variant) {
                $variant->update(['is_active' => $status]);
                $variant->product?->update(['is_active' => $status]);

                Notification::make()
                    ->title($status ? __('Chain published on site') : __('Chain hidden from site'))
                    ->success()
                    ->send();
            }
        });
    }
}
