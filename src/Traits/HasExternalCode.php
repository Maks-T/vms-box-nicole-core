<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasExternalCode
{
    public static function bootHasExternalCode(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->external_code)) {
                $model->external_code = (string) Str::uuid();
            }
        });
    }
}
