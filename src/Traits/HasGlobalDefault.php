<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasGlobalDefault
{
  public static function bootHasGlobalDefault(): void
  {
    // Перед сохранением: не даем снять галочку с единственной дефолтной записи
    static::saving(function (Model $model) {
      if (isset($model->is_default) && !$model->is_default) {
        $otherDefaultExists = static::where($model->getKeyName(), '!=', $model->getKey())
          ->where('is_default', true)
          ->exists();

        if (!$otherDefaultExists) {
          $model->is_default = true;
        }
      }
    });

    // После сохранения: автоматически сбрасываем флаг у всех остальных записей таблицы
    static::saved(function (Model $model) {
      if (isset($model->is_default) && $model->is_default) {
        static::where($model->getKeyName(), '!=', $model->getKey())
          ->update(['is_default' => false]);
      }
    });

    // Перед удалением: прерываем операцию, если запись является дефолтной
    static::deleting(function (Model $model) {
      if (isset($model->is_default) && $model->is_default) {
        throw new \Exception(
          __('System default record cannot be deleted.')
        );
      }
    });
  }
}