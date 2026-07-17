@php
  /** @var \Nicole\Box\Core\Models\Order $order */

    $currencySymbol = match($order->currency) {
        'RUB' => 'руб.',
        'USD' => '$',
        'BYN' => 'Br',
        default => $order->currency
    };
@endphp

  <!DOCTYPE html>
<html lang="{{ $order->locale ?? 'ru' }}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>Заказ №{{ $order->id }}</title>
</head>
<body
  style="font-family: 'DejaVu Sans', sans-serif; padding: 40px; color: #1a1916; background-color: #fff; font-size: 14px; line-height: 1.4;">

<table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
  <tr>
    <td style="vertical-align: top; text-align: left;">
      <h2 style="margin: 0 0 10px 0; color: #111827; font-size: 24px;">{{ config('nicole.company.name') }}</h2>
      <div style="font-size: 12px; color: #6b7280; line-height: 16px;">
        Адрес: {{ config('nicole.company.address') }}<br>
        Email: {{ config('nicole.company.email') }} · Тел: {{ config('nicole.company.phone') }}
      </div>
    </td>
    <td style="vertical-align: top; text-align: right; width: 50%;">
      <div style="font-size: 12px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
        Документ
      </div>
      <div style="font-size: 14px; font-weight: bold; color: #111827;">КП № {{ $order->kp_number }}</div>
      <div style="font-size: 12px; color: #4b5563; margin-top: 4px;">
        Дата: {{ $order->created_at ? $order->created_at->format('d.m.Y') : date('d.m.Y') }} г.
      </div>
    </td>
  </tr>
</table>

<hr style="border: 0; border-top: 1px solid #e5e7eb; margin-bottom: 25px;">

<h3 style="font-size: 18px; margin: 0 0 20px 0; color: #111827;">Спецификация заказа</h3>

<table style="width: 100%; border-collapse: collapse; font-size: 12px;">
  <thead>
  <tr style="background-color: #f3f4f6; border-bottom: 2px solid #d1d5db; color: #374151; font-weight: bold;">
    <th style="padding: 10px; text-align: left;">Услуга / Работа</th>
    <th style="padding: 10px; text-align: center; width: 100px;">Кол-во</th>
    <th style="padding: 10px; text-align: right; width: 130px;">Итого</th>
  </tr>
  </thead>
  <tbody>
  @foreach($order->sections as $index => $section)
    <tr
      style="background-color: #e5e7eb; font-weight: bold; border-top: 1px solid #d1d5db; border-bottom: 1px solid #d1d5db;">
      <td colspan="2" style="padding: 10px; color: #111827; text-transform: uppercase; letter-spacing: 0.5px;">
        0{{ $index + 1 }} — {{ $section->title }}
        @php
          $stoneName = collect($section->description)->firstWhere('name', 'Наименование камня')['description'] ?? null;
        @endphp
        @if($stoneName)
          · {{ $stoneName }}
        @endif
      </td>

      <td style="padding: 10px; text-align: right; color: #111827;">
        {{ number_format($section->price_grand_total, 0, '.', ' ') }} {{ $currencySymbol }}
      </td>
    </tr>

    @php
      $flatEstimate = [];
      $flattenTree = function($items) use (&$flattenTree, &$flatEstimate) {
          foreach ($items as $item) {
              if (!empty($item['value'])) {
                  $flatEstimate[] = $item['value'];
              }
              if (!empty($item['children'])) {
                  $flattenTree($item['children']);
              }
          }
      };
      if (!empty($section->estimate)) {
          $flattenTree($section->estimate);
      }
    @endphp

    @foreach($flatEstimate as $cells)
      <tr style="border-bottom: 1px solid #f3f4f6;">
        <td style="padding: 8px 15px; color: #374151;">{{ $cells[0] ?? '—' }}</td>
        <td style="padding: 8px; text-align: center; color: #4b5563;">
          {{ $cells[1] ?? '0' }} {{ $cells[2] ?? '' }}
        </td>
        <td style="padding: 8px 10px; text-align: right; color: #111827; font-family: monospace;">
          {{ $cells[4] ?? ($cells[1] ?? '0') }}
        </td>
      </tr>
    @endforeach
  @endforeach
  </tbody>
</table>

@php
  $sectionsCount = $order->sections->count();
  $sectionsWord = match (true) {
      $sectionsCount === 1 => 'Одно изделие',
      $sectionsCount > 1 && $sectionsCount < 5 => "{$sectionsCount} изделия",
      default => "{$sectionsCount} изделий"
  };
@endphp

<div style="margin-top: 35px; text-align: right; border-top: 2px solid #d1d5db; padding-top: 15px;">
    <span
      style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">
        Итого к оплате по заказу ({{ $sectionsWord }}):
    </span>

  <span style="font-size: 24px; font-weight: bold; color: #10b981; font-family: monospace; display: block;">
        {{ number_format($order->grand_total, 0, '.', ' ') }} {{ $currencySymbol }}
    </span>
  <span style="font-size: 11px; color: #9ca3af; display: block; margin-top: 4px;">
        Все работы, материалы, замер, доставка и установка включены в стоимость.
    </span>
</div>

</body>
</html>
