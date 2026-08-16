<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Relations\Relation;
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
      $entityId = (int)($arguments['entity_id'] ?? ($arguments['variant_id'] ?? 0));
      $entityType = (string)($arguments['entity_type'] ?? 'product_variant');
      $action = $arguments['action'] ?? 'activate';
      $status = $action === 'activate';

      $morphClass = Relation::getMorphedModel($entityType) ?? $entityType;
      if (class_exists($morphClass)) {
        $entity = $morphClass::find($entityId);
        if ($entity) {
          $entity->update(['is_active' => $status]);

          if ($entity instanceof ProductVariant && $entity->product) {
            $entity->product->update(['is_active' => $status]);
          }

          Notification::make()
            ->title($status ? __('Chain published on site') : __('Chain hidden from site'))
            ->success()
            ->send();
        }
      }
    });
  }
}
