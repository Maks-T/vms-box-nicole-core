<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Scramble\Extensions;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Nicole\Box\Core\Http\Controllers\Api\V1\ProductController;

class GlobalApiExtension extends OperationExtension
{
  public function handle(Operation $operation, RouteInfo $routeInfo): void
  {
    // 1. Глобальные заголовки для всех API роутов
    if (str_starts_with($routeInfo->route->uri(), 'api/v1')) {
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

    // 2. Специфичные query-параметры для ProductController@index
    if ($routeInfo->methodName() === 'index' && $routeInfo->className() === ProductController::class) {

      // Конфигурация для переопределения и обогащения автоматически выведенных параметров
      $customParams = [
        'id' => [
          'description' => 'Уникальный ID товара для получения карточки конкретной позиции.',
          'type' => new IntegerType(),
          'example' => 187
        ],
        'product_type' => [
          'description' => 'Символьный код типа товара для фильтрации (например, `acrylic_stone` или `kitchen_sink`).',
          'type' => new StringType(),
          'example' => 'kitchen_sink'
        ],
        'catalog_type' => [
          'description' => 'Тип сущности в каталоге: `product` (физический товар) или `service` (услуга обработки).',
          'type' => new StringType(),
          'example' => 'product'
        ],

        'attr' => [
          'description' => "**Фильтрация по динамическим характеристикам (EAV).**\n\nПередайте ассоциативный массив, где ключом является код EAV-атрибута, а значением - фильтруемые опции.\nРазрешено передавать несколько значений через запятую (например, для множественного выбора).\n\n*Пример запроса:* `?attr[color]=white,gray&attr[collection]=omoikiri_collection`",
          'type' => new StringType(),
          'example' => 'white,gray'
        ]
      ];

      foreach ($customParams as $name => $meta) {
        // Ищем, не добавил ли Scramble этот параметр автоматически
        /** @var \Dedoc\Scramble\Support\Generator\Parameter|null $parameter */
        $parameter = collect($operation->parameters)->first(function ($p) use ($name) {
          return $p->name === $name && $p->in === 'query';
        });

        if ($parameter) {
          // Если параметр уже был выведен - принудительно переопределяем его описание, тип и пример
          $parameter->description($meta['description']);
          $parameter->setSchema(Schema::fromType($meta['type']));
          $parameter->example($meta['example']);
        } else {
          // Если параметра не было - создаем его
          $operation->addParameters([
            Parameter::make($name, 'query')
              ->description($meta['description'])
              ->required(false)
              ->setSchema(Schema::fromType($meta['type']))
              ->example($meta['example'])
          ]);
        }
      }

    }
  }

}
