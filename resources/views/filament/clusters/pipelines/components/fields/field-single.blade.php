@props(['field', 'depth'])

@php
  $children = $field['children'] ?? [];
  $label    = $field['label'] ?? 'Field';

  $displayLabel = is_array($label) ? ($label[app()->getLocale()] ?? ($label['ru'] ?? head($label))) : __($label);
@endphp

<div class="flex flex-col relative my-2 ml-6 mr-3 sm:mr-5">
  <div class="absolute -left-6 -top-4 border-l-2 border-dotted border-gray-300 dark:border-gray-700" style="bottom: calc(100% - 16px);"></div>
  <div class="absolute w-6 top-4 -left-6 border-t-2 border-dotted border-gray-300 dark:border-gray-700"></div>

  @if(empty($children))
    @include('nicole-core::filament.clusters.pipelines.components.tree-node', [
        'node'       => $field,
        'isRoot'     => false,
        'depth'      => $depth + 1,
        'blockTitle' => $displayLabel,
    ])
  @else
    @foreach($children as $childNode)
      @include('nicole-core::filament.clusters.pipelines.components.tree-node', [
          'node'       => $childNode,
          'isRoot'     => false,
          'depth'      => $depth + 1,
          'blockTitle' => $displayLabel,
      ])
    @endforeach
  @endif
</div>
