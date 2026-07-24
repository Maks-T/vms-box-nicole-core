<x-filament-panels::page>
    {{-- Нативный стилизованный селект Filament (input.wrapper + input.select) --}}
    <div class="mb-6 bg-white dark:bg-gray-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <label class="text-sm font-semibold text-gray-700 dark:text-gray-200 whitespace-nowrap">
                {{ __('Select Configuration Pipeline:') }}
            </label>

            <div class="w-full sm:max-w-md">
                <x-filament::input.wrapper prefix-icon="heroicon-m-adjustments-horizontal">
                    <x-filament::input.select wire:model.live="pipeline_code">
                        @foreach(\Nicole\Box\Core\Models\Pipeline::where('is_active', true)->orderBy('sort_order')->get() as $pl)
                            <option value="{{ $pl->code }}">{{ $pl->name }} ({{ $pl->code }})</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </div>

    @if($base_variant_id)
        @php
            $report = app(\Nicole\Box\Core\Services\Calculator\PipelineTreeService::class)->analyzeTree($base_variant_id, $pipeline_code);
            $rootVariant = \Nicole\Box\Core\Models\ProductVariant::find($base_variant_id);
            $isRootActive = $rootVariant?->product?->is_active ?? false;
        @endphp

        <div class="mb-6">
            <div class="flex items-center justify-between mb-4 bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800">
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-semibold">{{ __('Configuring Chain') }}</span>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $rootVariant?->product?->name }} <span class="font-mono text-gray-500">({{ $rootVariant?->sku }})</span>
                    </h2>
                </div>
                <x-filament::button wire:click="closeTree" color="gray" icon="heroicon-m-arrow-left">
                    {{ __('Back to List') }}
                </x-filament::button>
            </div>

            @if($report)
                @include('nicole-core::filament.clusters.pipelines.components.tree-status-panel', [
                    'variantId' => $base_variant_id,
                    'isValid' => $report['is_valid'] ?? false,
                    'isRootActive' => $isRootActive,
                ])

                @include('nicole-core::filament.clusters.pipelines.components.tree-node', [
                    'node' => $report,
                    'isRoot' => true,
                ])
            @endif
        </div>
    @else
        {{ $this->table }}
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
