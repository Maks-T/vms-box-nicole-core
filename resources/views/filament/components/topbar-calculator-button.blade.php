@if (Route::has('calculator.show'))
  <div class="flex items-center mx-3">
    <x-filament::button
        tag="a"
        href="{{ route('calculator.show') }}"
        target="_blank"
        icon="heroicon-m-calculator"
        color="primary"
        size="sm"
    >
      <span class="flex items-center gap-1.5 font-bold">
        <span>{{ __('Calculator') }}</span>
        <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-3.5 h-3.5 opacity-80" />
      </span>
    </x-filament::button>
  </div>
@endif