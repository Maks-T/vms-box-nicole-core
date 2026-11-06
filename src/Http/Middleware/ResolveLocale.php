<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class ResolveLocale
{
  private const int COOKIE_MINUTES = 525600;

  public function handle(Request $request, Closure $next): Response
  {
    $supported = self::supported();

    $fromQuery = self::normalize($request->query('lang'), $supported);

    $isApi = $request->is('api/*');

    $locale = $fromQuery
      ?? ($isApi ? self::fromBrowser($request, $supported) : null)
      ?? self::normalize($request->cookie(self::cookieName()), $supported)
      ?? (!$isApi && config('nicole.locale_from_browser') ? self::fromBrowser($request, $supported) : null)
      ?? self::fallback($supported);

    App::setLocale($locale);

    $response = $next($request);

    $response->headers->set('Content-Language', $locale);

    if ($fromQuery !== null && !$isApi && $request->cookie(self::cookieName()) !== $fromQuery) {
      $response->headers->setCookie(self::makeCookie($fromQuery));
    }

    return $response;
  }

  public static function supported(): array
  {
    $locales = array_values(array_filter(array_map(
      static fn($l) => strtolower(trim((string)$l)),
      (array)config('nicole.locales', ['ru', 'en'])
    )));

    return $locales !== [] ? $locales : ['en'];
  }

  public static function cookieName(): string
  {
    return (string)config('nicole.locale_cookie', 'vms_locale');
  }

  public static function normalize(mixed $value, array $supported): ?string
  {
    if (!is_string($value) || trim($value) === '') {
      return null;
    }

    $code = strtolower(substr(trim($value), 0, 2));

    return in_array($code, $supported, true) ? $code : null;
  }

  private static function fromBrowser(Request $request, array $supported): ?string
  {
    foreach ($request->getLanguages() as $language) {
      $code = self::normalize($language, $supported);
      if ($code !== null) {
        return $code;
      }
    }

    return null;
  }

  private static function fallback(array $supported): string
  {
    return self::normalize(config('app.locale'), $supported)
      ?? self::normalize(config('app.fallback_locale'), $supported)
      ?? $supported[0];
  }

  public static function makeCookie(string $locale): Cookie
  {
    return Cookie::create(
      name: self::cookieName(),
      value: $locale,
      expire: time() + self::COOKIE_MINUTES * 60,
      path: '/',
      secure: (bool)config('session.secure', false),
      httpOnly: false,
      raw: true,
      sameSite: Cookie::SAMESITE_LAX,
    );
  }
}
