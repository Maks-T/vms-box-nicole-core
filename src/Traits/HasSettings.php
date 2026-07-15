<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\SettingSchema;
use Nicole\Box\Core\Support\Constants\SettingKey as SK;

trait HasSettings
{
  protected function initializeHasSettings(): void
  {
    $this->casts['settings'] = 'array';
    if (! in_array('settings', $this->fillable)) {
      $this->fillable[] = 'settings';
    }
  }

  public static function bootHasSettings(): void
  {
    static::creating(function (Model $model) {
      if (!empty($model->settings)) {
        return;
      }

      
      $schemaRecord = SettingSchema::where('entity_type', $model->getMorphClass())->first();
      $channels = Channel::where('is_active', true)->get();

      $defaultSettings = ['channels' => []];

      foreach ($channels as $channel) {
        // По умолчанию все публично, если иного не сказано в схеме
        $defaultSettings['channels'][$channel->code][SK::IS_PUBLIC] = true;

        
        if ($schemaRecord && is_array($schemaRecord->meta_schema)) {
          foreach ($schemaRecord->meta_schema as $field) {
            if (isset($field['default'])) {
              $defaultSettings['channels'][$channel->code][$field['key']] = $field['default'];
            }
          }
        }
      }

      $model->settings = $defaultSettings;
    });
  }

  public function getChannelSettings(string $channelCode): array
  {
    return $this->settings['channels'][$channelCode] ?? [];
  }

  public function isEnabledInChannel(string $channelCode): bool
  {
    return (bool) ($this->settings['channels'][$channelCode][SK::IS_PUBLIC] ?? true);
  }

  /**
   * Сущность должна быть опубликована в указанном канале
   */
  public function scopePublicInChannel($query, string $channelCode)
  {
    return $query->where("settings->channels->{$channelCode}->" . SK::IS_PUBLIC, true);
  }

}
