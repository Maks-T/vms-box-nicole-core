<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Новая заявка #{{ $order->code }}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.5; color: #0f172a; background-color: #f8fafc; padding: 32px 16px; margin: 0; }
    .container { max-width: 560px; margin: 0 auto; background: #ffffff; padding: 36px 32px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .header-tag { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
    h1 { margin: 0 0 20px 0; color: #0f172a; font-size: 20px; font-weight: 700; }
    .price-row { display: flex; justify-content: space-between; align-items: baseline; padding: 16px 0; border-top: 1px solid #0f172a; border-bottom: 1px solid #e2e8f0; margin-bottom: 24px; }
    .price-label { font-size: 13px; color: #475569; font-weight: 500; }
    .price-value { font-size: 24px; font-weight: 700; color: #0f172a; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .actions { margin: 20px 0 28px 0; padding-bottom: 24px; border-bottom: 1px solid #e2e8f0; }
    .btn-primary { display: inline-block; padding: 10px 18px; background-color: #0f172a; color: #ffffff !important; text-decoration: none; font-weight: 600; font-size: 13px; border-radius: 6px; }
    .btn-link { display: inline-block; margin-left: 14px; font-size: 13px; font-weight: 600; color: #2563eb; text-decoration: none; }
    .btn-link:hover { text-decoration: underline; }
    .section-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #64748b; margin: 24px 0 12px 0; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .data-table td { padding: 6px 0; vertical-align: top; }
    .data-table .label { color: #64748b; width: 130px; font-weight: 500; }
    .data-table .val { color: #0f172a; font-weight: 600; }
    .data-table .val a { color: #0f172a; text-decoration: none; }
    .specs-list { margin: 0; padding: 0; list-style: none; font-size: 13px; color: #334155; }
    .specs-list li { padding: 4px 0; border-bottom: 1px dashed #f1f5f9; }
    .specs-list li:last-child { border-bottom: none; }
    .footer { text-align: left; margin-top: 36px; padding-top: 16px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8; }
  </style>
</head>
<body>
<div class="container">
  <div class="header-tag">Конфигуратор душевых ограждений</div>
  <h1>Заказ #{{ $order->code }}</h1>

  <div class="price-row">
    <span class="price-label">Сумма расчета</span>
    <span class="price-value">{{ number_format((float)$order->grand_total, 2, '.', ' ') }} {{ $order->currency }}</span>
  </div>

  <div class="actions">
    <a href="{{ url('/admin/orders/' . $order->id . '/edit') }}" class="btn-primary" target="_blank">
      Перейти в панель CRM →
    </a>
    <a href="{{ url('/api/v1/orders/' . $order->code . '/html') }}" class="btn-link" target="_blank">Смета HTML</a>
    <a href="{{ url('/api/v1/orders/' . $order->code . '/pdf') }}" class="btn-link" target="_blank">Скачать PDF</a>
    <a href="{{ url('/calculator?code=' . $order->code) }}" class="btn-link" target="_blank">Открыть 3D</a>
  </div>

  <div class="section-title">Контакты покупателя</div>
  <table class="data-table">
    <tr><td class="label">Клиент:</td><td class="val">{{ $order->customer?->full_name ?: 'Не указано' }}</td></tr>
    @if($order->customer?->phone)
      <tr><td class="label">Телефон:</td><td class="val"><a href="tel:{{ $order->customer->phone }}">{{ $order->customer->phone }}</a></td></tr>
    @endif
    @if($order->customer?->email)
      <tr><td class="label">Email:</td><td class="val"><a href="mailto:{{ $order->customer->email }}">{{ $order->customer->email }}</a></td></tr>
    @endif
  </table>

  @if($order->sections->isNotEmpty())
    <div class="section-title">Параметры конструкции</div>
    @foreach($order->sections as $section)
      @if(!empty($section->description) && is_array($section->description))
        <ul class="specs-list">
          @foreach($section->description as $spec)
            @if(is_array($spec) && !empty($spec['name']) && !empty($spec['description']))
              <li>
                <span style="color: #64748b;">{{ $spec['name'] }}:</span>
                <strong style="color: #0f172a; float: right;">{{ $spec['description'] }}</strong>
              </li>
            @endif
          @endforeach
        </ul>
      @endif
    @endforeach
  @endif

  <div class="footer">
    Уведомление платформы VMS-NC
  </div>
</div>
</body>
</html>