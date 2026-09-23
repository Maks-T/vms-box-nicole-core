<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers;

use Illuminate\Http\Request;
use Nicole\Box\Core\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class CalculatorController
{
  /**
   * Отображение страницы калькулятора
   *
   * @param Request $request
   * @param string|null $type Режим/тип калькулятора
   * @return Response
   */
  public function show(Request $request, ?string $type = null): Response
  {

    $widgetSlug = $request->query('widget')
      ?? config('nicole.active_widget')
      ?? env('VMS_ACTIVE_WIDGET', 'widget');

    $order = null;
    if ($request->filled('code')) {
      $order = Order::where('code', $request->input('code'))->first();
    } elseif ($request->filled('orderId')) {
      $order = Order::find($request->input('orderId'));
    }

    $user = auth()->user();
    $baseUrl = rtrim((string)config('app.url', url('/')), '/');

    $initialData = [
      'apiUrl' => "{$baseUrl}/api/v1",
      'assetsUrl' => "{$baseUrl}/storage/{$widgetSlug}/",
      'baseUrl' => $baseUrl,
      'policyLink' => config('nicole.policy_link', '#'),
      'ofertaLink' => config('nicole.oferta_link', '#'),
      'state' => $order ? $order->calc_state : null,
      'auth' => [
        'client' => null,
        'employee' => $user ? [
          'id' => (int)$user->id,
          'name' => (string)$user->name,
          'email' => (string)$user->email,
          'roles' => method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->values()->toArray()
            : (isset($user->roles) ? collect($user->roles)->pluck('name')->filter()->values()->toArray() : []),
        ] : null,
      ],
      'type' => $type,
      'widget' => $widgetSlug,
    ];

    $embedUrl = $this->resolveEmbedUrl($baseUrl, $widgetSlug);

    return Inertia::render('Calculator/Show', [
      'embedUrl' => $embedUrl,
      'widgetSlug' => $widgetSlug,
      'initialData' => $initialData,
      'currentType' => $type,
    ]);
  }

  /**
   * Разрешение физического пути к embed.js для виджета
   */
  protected function resolveEmbedUrl(string $baseUrl, string $widgetSlug): string
  {
    if (file_exists(public_path("storage/{$widgetSlug}/embed.js"))) {
      return "{$baseUrl}/storage/{$widgetSlug}/embed.js";
    }

    if (file_exists(public_path("{$widgetSlug}/embed.js"))) {
      return "{$baseUrl}/{$widgetSlug}/embed.js";
    }

    if (file_exists(public_path('widget/embed.js'))) {
      return "{$baseUrl}/widget/embed.js";
    }

    return "{$baseUrl}/storage/{$widgetSlug}/embed.js";
  }

}