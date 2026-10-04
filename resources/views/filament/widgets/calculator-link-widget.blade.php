<div class="col-span-full w-full mb-3">
  <a href="{{ route('calculator.show') }}" target="_blank"
     class="group block p-5 sm:p-6 rounded-2xl shadow-sm transition-all duration-300 hover:shadow-md hover:scale-[1.002] active:scale-[0.998]"
     style="background: linear-gradient(135deg, var(--color-primary-600, var(--primary-600, #0d9488)) 0%, var(--color-primary-800, var(--primary-800, #115e59)) 100%);">

    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4 text-white">
        <div class="p-3 bg-white/15 rounded-xl flex items-center justify-center shrink-0 border border-white/20">
          <x-filament::icon icon="heroicon-o-calculator" class="w-8 h-8 text-white" />
        </div>
        <div class="flex flex-col gap-1 text-left">
          <h2 class="text-lg font-bold text-white leading-tight">
            {{ __('Interactive Calculator') }}
          </h2>
          <p class="text-xs text-white/80 leading-normal">
            {{ __('Open the CPQ widget in a new tab') }}
          </p>
        </div>
      </div>

      <div class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 transition-colors border border-white/20">
        <span>{{ __('Go to App') }}</span>
        <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4 text-white" />
      </div>
    </div>
  </a>
</div>