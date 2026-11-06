<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <title>{{ __('Your estimate No. :code has been received', ['code' => $order->code]) }}</title>
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
    <a href="{{ config('nicole.client_site_url') }}" target="_blank" style="text-decoration: none;">
      <h1 style="margin: 0; color: #00B7C2; font-size: 26px; font-weight: 800; letter-spacing: -0.5px;">
        {{ config('mail.from.name', config('app.name')) }}
      </h1>
    </a>
  </div>

  <h2>{{ __('Hello, :name!', ['name' => $order->customer?->full_name ?: __('Dear customer')]) }}</h2>

  <p>{!! __('Thank you for choosing <strong>:company</strong>. Your preliminary partition estimate has been created and registered in our system under number <strong>No. :code</strong>.', ['company' => e(config('mail.from.name', config('app.name'))), 'code' => e($order->code)]) !!}</p>

  <div class="alert-box">
    <strong>{{ __('What happens next:') }}</strong> {{ __('Our specialist has already reviewed the specification and will contact you shortly to agree on the details and the measurement date.') }}
  </div>

  <div class="summary-box">
    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
      {{ __('Estimate summary:') }}
    </h3>
    <p style="margin: 4px 0;"><strong>{{ __('Estimate number:') }}</strong> {{ $order->code }}</p>
    <p style="margin: 4px 0;"><strong>{{ __('Grand total:') }}</strong> <span style="color: #00B7C2; font-weight: 700;">{{ number_format((float)$order->grand_total, 2, '.', ' ') }} {{ $order->currency }}</span></p>

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
    <a href="{{ config('nicole.client_site_url') }}" target="_blank" style="display: inline-block; padding: 12px 24px; background: #00B7C2; color: #ffffff; text-decoration: none; font-weight: 700; border-radius: 6px; font-size: 13px;">
      {{ __('Go to website') }}
    </a>
  </div>

  <div class="footer">
    <p style="margin: 2px 0;">{{ __('Best regards,') }} <a href="{{ config('nicole.client_site_url') }}" target="_blank" style="color: #00B7C2; text-decoration: none; font-weight: 600;">{{ config('mail.from.name', config('app.name')) }}</a></p>
    <p style="margin: 2px 0;">{{ __('Website:') }} <a href="{{ config('nicole.client_site_url') }}" target="_blank" style="color: #64748b;">{{ config('nicole.client_site_url') }}</a></p>
    <p style="margin: 8px 0 0 0; font-size: 11px;">{{ __('You received this email because you submitted an estimate request in the online calculator.') }}</p>
  </div>
</div>
</body>
</html>