<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use App\Models\User;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\ServiceProvider;
use Dedoc\Scramble\Scramble;
use Nicole\Box\Core\Support\Scramble\Extensions\GlobalApiExtension;

class FilamentServiceProvider extends ServiceProvider
{
  public function boot(GateContract $gate): void
  {
    $gate->define('viewApiDocs', function (?User $user = null) {
      return true;
    });

    if (class_exists(Scramble::class)) {
      Scramble::registerExtension(GlobalApiExtension::class);
    }

    LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
      $switch->locales(config('nicole.locales', ['ru', 'en']))
        ->visible(outsidePanels: true);
    });

    SpatieMediaLibraryFileUpload::configureUsing(function (SpatieMediaLibraryFileUpload $component) {
      $component->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']);
    });
  }
}
