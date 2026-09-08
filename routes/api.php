<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nicole\Box\Core\Http\Controllers\Api\V1\BootstrapController;
use Nicole\Box\Core\Http\Controllers\Api\V1\PipelineConfigController;
use Nicole\Box\Core\Http\Controllers\Api\V1\CalculatorWebhookController;
use Nicole\Box\Core\Http\Controllers\Api\V1\FilterController;
use Nicole\Box\Core\Http\Controllers\Api\V1\OrderController;
use Nicole\Box\Core\Http\Controllers\Api\V1\PdfExportController;
use Nicole\Box\Core\Http\Controllers\Api\V1\ProductController;

// Инициализация конфигуратора и каталога
Route::get('/bootstrap', [BootstrapController::class, 'index']);
Route::get('/{family}/filters', [FilterController::class, 'index']);
Route::get('/{family}/products', [ProductController::class, 'index']);

// Пайплайны комплектации и связей
Route::get('/pipelines', [PipelineConfigController::class, 'index']);
Route::get('/pipelines/{pipeline}/{baseEntityId?}', [PipelineConfigController::class, 'show']);

// Управление заказами
// @since 2026-09-06: Получение списка проектов/заказов для модального окна "Открыть проект"
Route::get('/orders', [OrderController::class, 'index']);

// Сохранение нового расчета
Route::post('/order/save', [OrderController::class, 'save']);

// Получение одного заказа по коду
Route::get('/orders/{code}', [OrderController::class, 'get']);

// Обновление существующего заказа по коду
Route::put('/orders/{code}', [OrderController::class, 'update']);

// @since 2026-09-06: Удаление заказа по его уникальному коду
Route::delete('/orders/{code}', [OrderController::class, 'destroy']);

// Экспорт документов (PDF / HTML)
Route::get('/orders/{code}/pdf', [PdfExportController::class, 'streamPdf']);
Route::get('/orders/{code}/html', [PdfExportController::class, 'viewHtml']);

// Системные вебхуки
Route::post('webhooks/calculator/deploy', [CalculatorWebhookController::class, 'deploy']);