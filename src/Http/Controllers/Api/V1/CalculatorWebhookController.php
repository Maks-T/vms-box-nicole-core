<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Nicole\Box\Core\Support\CatalogCache;

/**
 * @group Core: Вебхуки
 *
 * Обработка системных событий, деплоя и интеграционных хуков виджета калькулятора.
 */
class CalculatorWebhookController extends Controller
{
  private const string CACHE_KEY = 'calc_remote_info_v2';

  /**
   * Обновление сборки виджета (Деплой).
   *
   * Инициирует выполнение bash-скрипта на сервере для деплоя свежих статических файлов виджета из ветки сборки.
   * Запрос должен содержать валидный секретный токен в заголовке или параметрах запроса.
   *
   * @queryParam token string Токен доступа (как альтернатива заголовку Authorization)
   * @header Authorization string Bearer токен для авторизации запроса
   *
   * @response 200 {
   *   "status": "success",
   *   "message": "Калькулятор успешно обновлен",
   *   "data": {
   *     "output": "Загрузка и обновление виджета...\nВиджет успешно добавлен."
   *   }
   * }
   * @response 401 {
   *   "status": "error",
   *   "message": "Unauthorized"
   * }
   * @response 500 {
   *   "status": "error",
   *   "message": "Ошибка выполнения скрипта деплоя",
   *   "error_output": "Permission denied"
   * }
   */
  public function deploy(Request $request): JsonResponse
  {
    $secret = config('services.calculator.webhook_secret');
    $providedToken = $request->bearerToken() ?? $request->input('token');

    if (empty($secret) || !hash_equals($secret, (string)$providedToken)) {
      Log::warning('Unauthorized attempt to deploy calculator via webhook.', [
        'ip' => $request->ip()
      ]);

      return response()->json([
        'status' => 'error',
        'message' => 'Unauthorized'
      ], 401);
    }

    $scriptName = config('nicole.deploy_script_name', 'deploy-hook.sh');
    $wrapperPath = config('nicole.deploy_widget_wrapper', '/usr/bin/sudo /var/www/scripts/deploy-wrapper.sh');

    $branch = $request->input('branch', 'deploy/build');
    $safeBranch = preg_replace('/[^a-zA-Z0-9\/\.\-_]/', '', $branch);

    $absoluteHookPath = base_path($scriptName);

    try {
      $result = Process::run("{$wrapperPath} {$absoluteHookPath} {$safeBranch}");

      if ($result->successful()) {
        cache()->forget(self::CACHE_KEY);
        clearstatcache();
        CatalogCache::invalidate();
        clearstatcache();

        Log::info('Calculator deployed successfully via webhook.');

        return response()->json([
          'status' => 'success',
          'message' => 'Калькулятор успешно обновлен',
          'data' => [
            'output' => $result->output(),
          ]
        ]);
      }

      Log::error("Calculator Webhook Deploy Error: " . $result->errorOutput());

      return response()->json([
        'status' => 'error',
        'message' => 'Ошибка выполнения скрипта деплоя',
        'error_output' => $result->errorOutput()
      ], 500);

    } catch (\Throwable $e) {
      Log::error("Calculator Webhook Deploy Exception: " . $e->getMessage());

      return response()->json([
        'status' => 'error',
        'message' => 'Внутренняя ошибка сервера',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
