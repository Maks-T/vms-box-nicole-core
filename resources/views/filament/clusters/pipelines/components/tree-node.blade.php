@props(['node', 'isRoot' => false, 'depth' => 0, 'blockTitle' => null])

@php
  $variantId = $node['variant_id'] ?? ($node['child']['id'] ?? '');
  $name      = $node['variant_name'] ?? ($node['child']['name'] ?? __('Not Selected'));
  $slug      = $node['product_slug'] ?? ($node['child']['slug'] ?? null);
  $imageUrl  = $node['image_url'] ?? ($node['child']['image_url'] ?? null);

  $isRequired = $node['is_required'] ?? false;
  $isFilled   = $isRoot ? true : (!empty($variantId));
  $isValid    = $node['is_valid'] ?? false;

  $status = match(true) {
      $isFilled && !$isValid => [
          'color'     => 'var(--danger-500)',
          'bg'        => 'var(--danger-50)',
          'badge'     => 'danger',
          'badgeText' => __('Has Errors'),
          'btnColor'  => 'danger',
          'btnLabel'  => __('Fix'),
      ],
      $isRequired && !$isFilled => [
          'color'     => 'var(--warning-500)',
          'bg'        => 'var(--warning-50)',
          'badge'     => 'warning',
          'badgeText' => __('Requires Link'),
          'btnColor'  => 'warning',
          'btnLabel'  => __('Create Link'),
      ],
      !$isRequired && !$isFilled => [
          'color'     => 'var(--gray-300)',
          'bg'        => 'var(--gray-50)',
          'badge'     => 'gray',
          'badgeText' => __('Optional'),
          'btnColor'  => 'gray',
          'btnLabel'  => __('Create Link'),
      ],
      default => [
          'color'     => 'var(--success-500)',
          'bg'        => 'var(--success-50)',
          'badge'     => 'success',
          'badgeText' => __('Completed'),
          'btnColor'  => 'gray',
          'btnLabel'  => __('Configure'),
      ],
  };

  $roleCode = $node['field_code'] ?? '';

  $title = $blockTitle
    ?? ($node['label']
    ?? (\Nicole\Box\Core\Support\Pipelines\PipelineRoleResolver::getLabel($roleCode)
    ?? ($node['group_name'] ?? __('Unknown Group'))));

  $fields    = $node['fields'] ?? [];
  $hasFields = count($fields) > 0;
@endphp

<div class="relative mb-4 {{ (!$isRoot) ? 'ml-8' : '' }}">

  @if(!$isRoot)
    <div class="absolute -left-6 -top-8 border-l-2 border-dotted border-gray-300 dark:border-gray-700" style="bottom: calc(100% - 24px);"></div>
    <div class="absolute w-6 top-6 -left-6 border-t-2 border-dotted border-gray-300 dark:border-gray-700"></div>
  @endif

  <div x-data="{ isCollapsed: false }" class="bg-white dark:bg-gray-900 rounded-lg shadow-sm overflow-hidden border border-gray-200 dark:border-gray-800 border-l-4" style="border-left-color: {{ $status['color'] }};">

    <div @click="isCollapsed = !isCollapsed" class="flex items-center justify-between p-3 cursor-pointer transition gap-2 hover:bg-gray-100 dark:hover:bg-gray-800 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
      <div class="flex items-center gap-2 min-w-0 pr-2">
        <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 shrink-0 transition-transform duration-200" style="color: {{ $status['color'] }};" x-bind:class="{ 'rotate-90': !isCollapsed }"/>
        <span class="text-sm font-bold truncate" style="color: {{ $status['color'] }};">{{ $title }}</span>
      </div>
      <div class="shrink-0 ml-auto" @click.stop>
        <x-filament::badge :color="$status['badge']">{{ $status['badgeText'] }}</x-filament::badge>
      </div>
    </div>

    <div x-show="!isCollapsed" x-collapse>
      <div class="p-4 flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div class="flex items-start gap-3">
            @if($imageUrl)
              <img src="{{ $imageUrl }}" alt="img" class="w-10 h-10 rounded-md object-cover border border-gray-200 dark:border-gray-700 shrink-0"/>
            @else
              <div class="w-10 h-10 rounded-md shrink-0 flex items-center justify-center border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <x-filament::icon icon="heroicon-m-cube" class="w-6 h-6" style="color: {{ $status['color'] }};"/>
              </div>
            @endif
            <div class="flex flex-col mt-0.5">
              <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wider">{{ __('ID') }}: {{ $variantId }}</span>
              <div class="flex items-start gap-2 mt-0.5">
                <span class="text-sm font-bold leading-tight text-gray-900 dark:text-white">{{ $name }}</span>
                @if($slug)
                  <a href="{{ route('product.show', $slug) }}" target="_blank" class="shrink-0 text-gray-400 hover:text-primary-600 transition">
                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4 mt-0.5"/>
                  </a>
                @endif
              </div>
            </div>
          </div>

          <div class="shrink-0 mt-2 sm:mt-0 flex items-center gap-2">
            @if($isRoot)
              <x-filament::button @click="$wire.mountAction('configureRoot')" size="sm" color="warning">
                {{ __('Change') }}
              </x-filament::button>
            @else
              @if($isFilled)
                @if($hasFields)
                  <x-filament::button @click="$wire.mountAction('configureNode', { variant_id: {{ $variantId }} })" size="sm" :color="$status['btnColor']" :outlined="$status['btnColor'] === 'gray'">
                    {{ $status['btnLabel'] }}
                  </x-filament::button>
                @endif
              @else
                @if(!empty($node['virtual_meta']))
                  <x-filament::button @click="$wire.mountAction('configureNode', { virtual_meta: {{ json_encode($node['virtual_meta']) }} })" size="sm" :color="$status['btnColor']" :outlined="$status['btnColor'] === 'gray'" icon="heroicon-m-plus">
                    {{ __('Create Link') }}
                  </x-filament::button>
                @endif
              @endif

              @if(!empty($node['rule_id']))
                <x-filament::button @click="$wire.mountAction('deleteNode', { rule_id: {{ $node['rule_id'] }} })" size="sm" color="gray" outlined icon="heroicon-m-trash" icon-only title="{{ __('Delete Link') }}" class="hover:!text-danger-600 hover:!border-danger-600 transition duration-150"/>
              @endif
            @endif
          </div>
        </div>

        @if($hasFields)
          <div class="flex flex-col gap-3 mt-1 relative ml-2 pl-4 pr-2 sm:pr-4 pb-2 border-l-2 border-gray-100 dark:border-gray-800">
            @foreach($fields as $childField)
              @if(!empty($childField['is_multiple']))
                @include('nicole-core::filament.clusters.pipelines.components.fields.field-group', ['field' => $childField, 'depth' => $depth])
              @elseif(empty($childField['child']['id']) && !empty($childField['static_meta']))
                @include('nicole-core::filament.clusters.pipelines.components.fields.field-text', ['field' => $childField])
              @else
                @include('nicole-core::filament.clusters.pipelines.components.fields.field-single', ['field' => $childField, 'depth' => $depth])
              @endif
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
