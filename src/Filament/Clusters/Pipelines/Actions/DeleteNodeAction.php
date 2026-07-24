<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Nicole\Box\Core\Models\BindingRule;

class DeleteNodeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'deleteNode';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation()
            ->modalHeading(__('Delete Connection?'))
            ->modalDescription(__('Are you sure you want to delete this linked item?'))
            ->action(function (array $arguments) {
                $rule = BindingRule::find($arguments['rule_id'] ?? 0);
                if ($rule) {
                    $rule->delete();
                    Notification::make()->title(__('Connection deleted successfully'))->success()->send();
                }
            });
    }
}
