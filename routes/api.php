<?php

use Illuminate\Support\Facades\Route;
use Nicole\Box\Core\Http\Controllers\Api\V1\BootstrapController;
use Nicole\Box\Core\Http\Controllers\Api\V1\PipelineConfigController;
use Nicole\Box\Core\Http\Controllers\Api\V1\CalculatorWebhookController;
use Nicole\Box\Core\Http\Controllers\Api\V1\FilterController;
use Nicole\Box\Core\Http\Controllers\Api\V1\OrderController;
use Nicole\Box\Core\Http\Controllers\Api\V1\PdfExportController;
use Nicole\Box\Core\Http\Controllers\Api\V1\ProductController;

Route::get('/bootstrap', [BootstrapController::class, 'index']);
Route::get('/{family}/filters', [FilterController::class, 'index']);
Route::get('/{family}/products', [ProductController::class, 'index']);

// Сохранение заказа
Route::post('/order/save', [OrderController::class, 'save']);

// Получение заказа по коду
Route::get('/orders/{code}', [OrderController::class, 'get']);

// Работа с PDF/HTML
Route::get('/orders/{code}/pdf', [PdfExportController::class, 'streamPdf']);
Route::get('/orders/{code}/html', [PdfExportController::class, 'viewHtml']);

// Обновление существующего заказа по его коду
Route::put('/orders/{code}', [OrderController::class, 'update']);

Route::post('webhooks/calculator/deploy', [CalculatorWebhookController::class, 'deploy']);

// Универсальный эндпоинт получения дерева связей
Route::get('/pipelines/{pipeline}/{baseVariantId}', [PipelineConfigController::class, 'show']);
