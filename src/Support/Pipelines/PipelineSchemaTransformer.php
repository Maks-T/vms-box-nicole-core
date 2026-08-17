<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines;

use Illuminate\Support\Str;
use Nicole\Box\Core\DTO\Pipeline\PipelineSlotDto;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\ProductType;

/**
 * Двусторонний трансформер данных схемы пайплайна
 */
class PipelineSchemaTransformer
{
  /**
   * Преобразование JSON из базы данных в плоскую структуру для репитера Filament.
   *
   * @param array|null $state
   * @return array<int, array{parent_code: string, slots: array<int, mixed>}>
   */
  public static function toFormState(?array $state): array
  {
    if (empty($state)) {
      return [];
    }

    // Если данные уже находятся в структуре формы
    if (isset($state[0]) && is_array($state[0])) {
      return array_values(array_filter($state, fn($g) => !empty($g['parent_code']) || !empty($g['slots'])));
    }

    $formatted = [];

    // Преобразование ассоциативного словаря из БД: { "pillar": { "baluster": { ... } } }
    foreach ($state as $parentCode => $slots) {
      if (Str::isUuid((string)$parentCode) || $parentCode === 'slots' || !is_array($slots)) {
        continue;
      }

      $slotsList = [];
      foreach ($slots as $roleKey => $slotData) {
        if (!is_array($slotData) || $roleKey === 'slots' || Str::isUuid((string)$roleKey)) {
          continue;
        }

        $slotDto = PipelineSlotDto::fromArray($slotData, is_string($roleKey) ? $roleKey : null);

        $slotsList[] = [
          'role_code' => $slotDto->roleCode,
          'label_key' => is_array($slotDto->labelKey) ? $slotDto->labelKey : ['ru' => $slotDto->labelKey],
          'target_type' => $slotDto->targetType,
          'target_code' => $slotDto->targetCode,
          'is_required' => $slotDto->isRequired,
          'is_multiple' => $slotDto->isMultiple,
        ];
      }

      $formatted[] = [
        'parent_code' => (string)$parentCode,
        'slots' => $slotsList,
      ];
    }

    return $formatted;
  }

  /**
   * Преобразование структуры формы обратно в ассоциативный JSON для БД
   *
   * @param array|null $state
   * @return array<string, array<string, array{label_key: mixed, target_type: string, target_code: string|null, is_required: bool, is_multiple: bool}>>
   */
  public static function toDatabase(?array $state): array
  {
    if (empty($state)) {
      return [];
    }

    $result = [];

    foreach ($state as $group) {
      $parentCode = $group['parent_code'] ?? null;
      if (!$parentCode || Str::isUuid((string)$parentCode) || $parentCode === 'slots') {
        continue;
      }

      $slotsMap = [];
      foreach ($group['slots'] ?? [] as $slotItem) {
        $roleCode = $slotItem['role_code'] ?? null;
        if (!$roleCode || Str::isUuid((string)$roleCode) || $roleCode === 'slots') {
          continue;
        }

        $slotDto = PipelineSlotDto::fromArray($slotItem, (string)$roleCode);

        $slotsMap[$roleCode] = [
          'label_key' => $slotItem['label_key'] ?? $slotDto->labelKey,
          'target_type' => $slotDto->targetType,
          'target_code' => $slotDto->targetCode,
          'is_required' => $slotDto->isRequired,
          'is_multiple' => $slotDto->isMultiple,
        ];
      }

      $result[$parentCode] = $slotsMap;
    }

    return $result;
  }

  /**
   * Заголовок блока родительской группы.
   */
  public static function resolveGroupLabel(array $state): ?string
  {
    $code = $state['parent_code'] ?? null;
    if (!$code || Str::isUuid((string)$code)) {
      return __('New Parent Group');
    }

    $locale = app()->getLocale();
    $productType = ProductType::where('code', $code)->first();
    $dictionary = ComplexDictionary::where('code', $code)->first();

    $title = $productType?->getTranslation('name', $locale)
      ?? ($dictionary?->getTranslation('name', $locale) ?? ucfirst((string)$code));

    $slotsCount = count($state['slots'] ?? []);
    $slotsText = trans_choice('slots_count', $slotsCount);

    return "{$title} ({$slotsText})";
  }

}
