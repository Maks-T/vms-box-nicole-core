@props(['field'])

@php
  $label    = $field['label'] ?? __('Property');
  $value    = !empty($field['static_meta']) ? head($field['static_meta']) : null;
  $isFilled = $field['is_filled'] ?? false;
@endphp

<div class="flex items-center gap-2 py-1.5 ml-6 pl-2 relative">
  <div class="absolute -left-6 -top-3 border-l-2 border-dotted border-gray-300 dark:border-gray-700" style="bottom: calc(100% - 14px);"></div>
  <div class="absolute w-6 top-3 -left-6 border-t-2 border-dotted border-gray-300 dark:border-gray-700"></div>

  <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}:</span>

  @if(!$isFilled)
    <x-filament::badge color="danger" size="sm">{{ __('Requires Link') }}</x-filament::badge>
  @else
    <span class="text-sm font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">
        {{ $value }}
    </span>
  @endif
</div>
