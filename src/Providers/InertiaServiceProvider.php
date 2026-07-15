<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class InertiaServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    Inertia::share([
      'auth' => function () {
        $user = auth()->user();

        return [
          'client' => null,
          'employee' => $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => method_exists($user, 'getRoleNames')
              ? $user->getRoleNames()->toArray()
              : [],
          ] : null,
        ];
      }
    ]);
  }
}
