<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Helpers;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProtectDefaultRecord
{
  /**
   * Конструктор универсального тумблера для форм Filament
   */
  public static function formToggle(string $modelClass, string $label = 'Default'): Toggle
  {
    return Toggle::make('is_default')
      ->label(__($label))
      ->disabled(function (?Model $record) use ($modelClass) {
        if (!$record || !$record->is_default) {
          return false;
        }

        
        return !$modelClass::where($record->getKeyName(), '!=', $record->getKey())
          ->where('is_default', true)
          ->exists();
      })
      ->columnSpanFull();
  }

  /**
   * Защищенная одиночная кнопка удаления в строке таблицы
   */
  public static function tableDeleteAction(string $errorTitle = 'Cannot delete default record'): DeleteAction
  {
    return DeleteAction::make()
      ->before(function (DeleteAction $action, Model $record) use ($errorTitle) {
        if (isset($record->is_default) && $record->is_default) {
          Notification::make()
            ->danger()
            ->title(__($errorTitle))
            ->body(__('This record is set as system default and cannot be deleted.'))
            ->send();

          $action->cancel();
        }
      });
  }

  /**
   * Защищенная кнопка удаления на страницах редактирования (EditRecord)
   */
  public static function pageDeleteAction(string $errorTitle = 'Cannot delete default record'): DeleteAction
  {
    return DeleteAction::make()
      ->before(function (DeleteAction $action, Model $record) use ($errorTitle) {
        if (isset($record->is_default) && $record->is_default) {
          Notification::make()
            ->danger()
            ->title(__($errorTitle))
            ->body(__('This record is set as system default and cannot be deleted.'))
            ->send();

          $action->cancel();
        }
      });
  }

  /**
   * Защищенное массовое удаление с пропуском дефолтных строк
   */
  public static function tableBulkDeleteAction(string $skippedTitle = 'Default records skipped'): DeleteBulkAction
  {
    return DeleteBulkAction::make()
      ->action(function (Collection $records, DeleteBulkAction $action) use ($skippedTitle) {
        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($records as $record) {
          if (isset($record->is_default) && $record->is_default) {
            $skippedCount++;
            continue;
          }
          $record->delete();
          $deletedCount++;
        }

        if ($deletedCount > 0) {
          Notification::make()
            ->success()
            ->title(__('Deleted successfully'))
            ->body(__(':count records were deleted.', ['count' => $deletedCount]))
            ->send();
        }

        if ($skippedCount > 0) {
          Notification::make()
            ->warning()
            ->title(__($skippedTitle))
            ->body(__('Some records were skipped as they are set as system defaults.'))
            ->send();
        }
      })
      ->successNotification(null);
  }
}
