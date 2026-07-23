{{-- packages/box/nicole/core/resources/views/filament/infolists/estimate-table.blade.php --}}
@php
  $estimate = $getRecord()->estimate ?? [];
@endphp

@if(empty($estimate))
  <div class="text-sm text-gray-500 italic py-4">
    {{ __('Смета пуста') }}
  </div>
@else
  <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl">
    <table class="w-full text-left text-sm border-collapse">
      <thead class="bg-gray-50 dark:bg-gray-800">
      <tr>
        @foreach(($estimate[0]['value'] ?? []) as $index => $colName)
          <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 font-bold text-gray-700 dark:text-gray-300 {{ $index > 0 ? 'text-right' : 'text-left' }}">
            {{ $colName }}
          </th>
        @endforeach
      </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
      @foreach(array_slice($estimate, 1) as $item)
        @php
          $parentCells = $item['value'] ?? [];
          $children = $item['children'] ?? [];
        @endphp

          <!-- Родительская строка группы (например, Материалы и комплектующие) -->
        <tr class="bg-gray-100/50 dark:bg-gray-800/30 font-semibold">
          @foreach($parentCells as $index => $cellValue)
            <td class="px-4 py-2.5 text-gray-900 dark:text-white {{ $index > 0 ? 'text-right' : 'text-left' }}">
              {{ $cellValue }}
            </td>
          @endforeach
        </tr>

        <!-- Дочерние строки элементов сметы -->
        @foreach($children as $child)
          @php
            $childCells = $child['value'] ?? [];
          @endphp
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/20">
            @foreach($childCells as $index => $cellValue)
              <td class="px-4 py-2 border-b border-gray-100 dark:border-gray-800 {{ $index === 0 ? 'text-gray-800 dark:text-gray-200 font-medium' : 'text-gray-600 dark:text-gray-400' }} {{ $index > 0 ? 'text-right' : 'text-left' }}">
                {{ $cellValue }}
              </td>
            @endforeach
          </tr>
        @endforeach
      @endforeach
      </tbody>
    </table>
  </div>
@endif
