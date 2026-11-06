<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nicole\Box\Core\Http\Controllers\CalculatorController;
use Nicole\Box\Core\Http\Middleware\ResolveLocale;

Route::get('/calculator/{type?}', [CalculatorController::class, 'show'])
  ->middleware(ResolveLocale::class)
  ->name('calculator.show');
