<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Scramble\Extensions;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

class GlobalApiExtension extends OperationExtension
{
  public function handle(Operation $operation, RouteInfo $routeInfo): void
  {

    $uri = ltrim($routeInfo->route->uri(), '/');

    if (str_starts_with($uri, 'api/v1') || str_starts_with($uri, 'api/')) {
      $operation->addParameters([
        Parameter::make('X-Sales-Channel', 'header')
          ->description('Код канала продаж для контекста настроек (например, widget или catalog).')
          ->required(true)
          ->setSchema(Schema::fromType((new StringType)->default('widget'))),

        Parameter::make('Accept-Language', 'header')
          ->description('Язык локализации текстовых полей (ru/en).')
          ->required(false)
          ->setSchema(Schema::fromType((new StringType)->default('ru'))),
      ]);
    }
  }
}