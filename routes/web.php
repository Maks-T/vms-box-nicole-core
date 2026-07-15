<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nicole\Box\Core\Http\Controllers\CalculatorController;

Route::get('/calculator/{type?}', [CalculatorController::class, 'show'])
  ->name('calculator.show');
