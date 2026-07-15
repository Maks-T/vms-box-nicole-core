<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Nicole\Box\Core\Models\Channel;

class EnforceChannelContext
{
    public function handle(Request $request, Closure $next)
    {
        $channelCode = $request->header('X-Sales-Channel', 'widget');
        $lang = $request->header('Accept-Language', 'ru');

        if (! $channelCode) {
            return response()->json(
                ['error' => 'Header X-Sales-Channel is required'],
                400,
            );
        }

        $channel = Channel::where('code', $channelCode)
            ->where('is_active', true)
            ->first();

        if (! $channel) {
            return response()->json(
                ['error' => 'Invalid or inactive sales channel'],
                403,
            );
        }

        // Устанавливаем язык глобально для всего Laravel
        App::setLocale(substr($lang, 0, 2));

        // Сохраняем канал в конфиг для ресурсов
        config(['app.channel' => $channelCode]);

        return $next($request);
    }
}
