<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeUiSetting extends Model
{
  protected $table = 'attribute_ui_settings';

  protected $fillable = [
    'attribute_id',
    'show_in_variant_grid',
    'show_in_product_grid',
    'grid_component_type',
    'grid_sort_order',
    'is_inline_editable',
    'is_filterable',
    'is_searchable',
    'view_roles',
    'edit_roles',
    'ui_config',
  ];

  protected function casts(): array
  {
    return [
      'show_in_variant_grid' => 'boolean',
      'show_in_product_grid' => 'boolean',
      'is_inline_editable'   => 'boolean',
      'is_filterable'        => 'boolean',
      'is_searchable'        => 'boolean',
      'grid_sort_order'      => 'integer',
      'view_roles'           => 'array',
      'edit_roles'           => 'array',
      'ui_config'            => 'array',
    ];
  }

  public function attribute(): BelongsTo
  {
    return $this->belongsTo(Attribute::class);
  }

  /**
   * Проверка: разрешено ли пользователю видеть эту колонку (по ролям Shield)
   */
  public function canViewByUser(?object $user): bool
  {
    if (empty($this->view_roles)) {
      return true;
    }

    if (!$user || !method_exists($user, 'hasAnyRole')) {
      return false;
    }

    return $user->hasAnyRole($this->view_roles);
  }

  /**
   * Проверка: разрешено ли пользователю инлайн-редактировать значение в ячейке
   */
  public function canEditByUser(?object $user): bool
  {
    if (!$this->is_inline_editable) {
      return false;
    }

    if (empty($this->edit_roles)) {
      return true;
    }

    if (!$user || !method_exists($user, 'hasAnyRole')) {
      return false;
    }

    return $user->hasAnyRole($this->edit_roles);
  }

}