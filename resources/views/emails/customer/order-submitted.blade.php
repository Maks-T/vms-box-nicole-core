<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Ваш расчет #{{ $order->code }} принят</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f8fafc; padding: 24px; margin: 0; }
    .container { max-width: 580px; margin: 0 auto; background: #ffffff; padding: 32px; border-radius: 12px; border-top: 5px solid #00B7C2; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    h2 { margin: 0 0 16px 0; color: #0f172a; font-size: 20px; }
    p { color: #475569; font-size: 14px; margin: 8px 0; }
    .alert-box { background: #f0fdfa; border-left: 4px solid #00B7C2; padding: 14px 18px; border-radius: 6px; margin: 20px 0; font-size: 14px; color: #134e4a; }
    .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 20px 0; }
    .footer { margin-top: 32px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 20px; }
  </style>
</head>
<body>
<div class="container">
  <div style="text-align: center; margin-bottom: 24px;">
    <a href="{{ env('CLIENT_SITE_URL', 'http://amigrupp.ru/') }}" target="_blank" style="text-decoration: none;">
      <h1 style="margin: 0; color: #00B7C2; font-size: 26px; font-weight: 800; letter-spacing: -0.5px;">
        {{ config('mail.from.name', 'АМИГРУПП') }}
      </h1>
    </a>
  </div>

  <h2>Здравствуйте, {{ $order->customer?->full_name ?: 'Уважаемый клиент' }}!</h2>

  <p>Благодарим вас за выбор компании <strong>{{ config('mail.from.name', 'АМИГРУПП') }}</strong>. Ваш предварительный расчет перегородки успешно сформирован и зарегистрирован в системе под номером <strong>№{{ $order->code }}</strong>.</p>

  <div class="alert-box">
    <strong>Что происходит сейчас:</strong> Наш специалист уже ознакомился со спецификацией и свяжется с вами в ближайшее время для согласования деталей и уточнения даты замера.
  </div>

  <div class="summary-box">
    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
      Сводка по расчету:
    </h3>
    <p style="margin: 4px 0;"><strong>Номер расчета:</strong> {{ $order->code }}</p>
    <p style="margin: 4px 0;"><strong>Итоговая сумма:</strong> <span style="color: #00B7C2; font-weight: 700;">{{ number_format((float)$order->grand_total, 2, '.', ' ') }} {{ $order->currency }}</span></p>

    @if($order->sections->isNotEmpty())
      <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #cbd5e1; font-size: 13px;">
        @foreach($order->sections as $section)
          <div>• <strong>{{ $section->title }}</strong></div>
          @if(!empty($section->description) && is_array($section->description))
            <div style="color: #64748b; font-size: 12px; margin-left: 12px;">
              @foreach($section->description as $spec)
                @if(is_array($spec) && !empty($spec['name']) && !empty($spec['description']))
                  - {{ $spec['name'] }}: {{ $spec['description'] }}<br>
                @endif
              @endforeach
            </div>
          @endif
        @endforeach
      </div>
    @endif
  </div>

  <div style="text-align: center; margin: 24px 0;">
    <a href="{{ env('CLIENT_SITE_URL', 'http://amigrupp.ru/') }}" target="_blank" style="display: inline-block; padding: 12px 24px; background: #00B7C2; color: #ffffff; text-decoration: none; font-weight: 700; border-radius: 6px; font-size: 13px;">
      Перейти на сайт amigrupp.ru
    </a>
  </div>

  <div class="footer">
    <p style="margin: 2px 0;">С уважением, производственная компания <a href="{{ env('CLIENT_SITE_URL', 'http://amigrupp.ru/') }}" target="_blank" style="color: #00B7C2; text-decoration: none; font-weight: 600;">{{ config('mail.from.name', 'АМИГРУПП') }}</a></p>
    <p style="margin: 2px 0;">Сайт: <a href="{{ env('CLIENT_SITE_URL', 'http://amigrupp.ru/') }}" target="_blank" style="color: #64748b;">{{ env('CLIENT_SITE_URL', 'http://amigrupp.ru/') }}</a></p>
    <p style="margin: 8px 0 0 0; font-size: 11px;">Вы получили это письмо, так как оставили заявку на расчет изделия в онлайн-калькуляторе.</p>
  </div>
</div>
</body>
</html>