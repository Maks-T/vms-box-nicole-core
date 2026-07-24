@props(['field', 'depth'])

@php
  $children   = $field['children'] ?? [];
  $childCount = count($children);
  $label      = $field['label'] ?? 'Element Group';

  $displayLabel = is_array($label) ? ($label[app()->getLocale()] ?? ($label['ru'] ?? head($label))) : __($label);

  $isFilled   = $field['is_filled'] ?? false;
  $isRequired = $field['is_required'] ?? false;

  $status = match(true) {
      collect($children)->contains(fn($c) => !($c['is_valid'] ?? false)) => [
          'color'     => 'var(--danger-500)',
          'bg'        => 'var(--danger-50)',
          'badge'     => 'danger',
          'badgeText' => __('Errors Inside'),
      ],
      $isRequired && !$isFilled => [
          'color'     => 'var(--warning-500)',
          'bg'        => 'var(--warning-50)',
          'badge'     => 'warning',
          'badgeText' => __('Requires Link'),
      ],
      !$isRequired && !$isFilled => [
          'color'     => 'var(--gray-300)',
          'bg'        => 'var(--gray-50)',
          'badge'     => 'gray',
          'badgeText' => __('Optional'),
      ],
      default => [
          'color'     => 'var(--success-500)',
          'bg'        => 'var(--success-50)',
          'badge'     => 'success',
          'badgeText' => __('Completed'),
      ],
  };

  $btnColor   = $isFilled ? 'gray' : 'primary';
  $isOutlined = $isFilled;
@endphp

<div class="relative mt-2 mb-4 ml-6 mr-2 sm:mr-4">
  <div class="absolute -left-6 -top-5 border-l-2 border-dotted border-gray-300 dark:border-gray-700" style="bottom: calc(100% - 20px);"></div>
  <div class="absolute w-6 top-5 -left-6 border-t-2 border-dotted border-gray-300 dark:border-gray-700"></div>

  <div x-data="{ isGroupOpen: true }" class="bg-white dark:bg-gray-900 rounded-lg shadow-sm overflow-hidden border border-gray-200 dark:border-gray-800 border-l-4" style="border-left-color: {{ $status['color'] }};">

    <div @click="isGroupOpen = !isGroupOpen" class="flex items-center justify-between p-2.5 cursor-pointer transition-colors border-b border-gray-200 dark:border-gray-800" style="background-color: {{ $status['bg'] }};">
      <div class="flex items-center gap-2">
        <x-filament::icon icon="heroicon-m-folder-open" class="w-5 h-5" style="color: {{ $status['color'] }};" x-show="isGroupOpen"/>
        <x-filament::icon icon="heroicon-m-folder" class="w-5 h-5" style="color: {{ $status['color'] }};" x-show="!isGroupOpen"/>
        <span class="text-sm font-bold uppercase tracking-wider" style="color: {{ $status['color'] }};">{{ $displayLabel }}</span>
        <span class="text-xs px-2 py-0.5 rounded-full bg-white dark:bg-gray-800 border font-bold shadow-sm" style="color: {{ $status['color'] }}; border-color: {{ $status['color'] }};">{{ $childCount }} {{ __('pcs.') }}</span>
      </div>
      <div class="flex items-center gap-3">
        <x-filament::badge :color="$status['badge']" size="sm">{{ $status['badgeText'] }}</x-filament::badge>
        <x-filament::icon icon="heroicon-m-chevron-down" class="w-4 h-4 transition-transform duration-200" style="color: {{ $status['color'] }};" x-bind:class="{ '-rotate-90': !isGroupOpen }"/>
      </div>
    </div>

    <div x-show="isGroupOpen" x-collapse>
      <div class="p-3 sm:p-4 pb-4 flex flex-col gap-4 relative bg-gray-50 dark:bg-gray-950">
        @foreach($children as $childIndex => $childNode)
          <div class="pl-4 pb-2 mr-2 border-l-2 border-gray-200 dark:border-gray-800 relative">
            @include('nicole-core::filament.clusters.pipelines.components.tree-node', [
                'node'       => $childNode,
                'isRoot'     => false,
                'depth'      => $depth + 1,
                'blockTitle' => $displayLabel . " #" . ($childIndex + 1),
            ])
          </div>
        @endforeach

        @if(!empty($field['virtual_meta']))
          <div class="flex justify-center mt-2">
            <x-filament::button @click="$wire.mountAction('configureNode', { virtual_meta: {{ json_encode($field['virtual_meta']) }} })" size="sm" :color="$btnColor" :outlined="$isOutlined" icon="heroicon-m-plus">
              {{ __('Add Component') }}
            </x-filament::button>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
