<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация входящего пакета расчета заказа (SaveData).
 *
 * @since 2026-09-06
 */
class SaveOrderRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Правила валидации входящего запроса.
   *
   * @return array<string, mixed>
   */
  public function rules(): array
  {
    return [
      /**
       * Символьный код заказа для обновления существующей записи.
       * @var string|null
       * @example "O-261-ODR6"
       */
      'code' => ['nullable', 'string', 'exists:orders,code'],

      /**
       * Название проекта / расчета.
       * @var string|null
       * @example "Терраса у бассейна"
       * @since 2026-09-06
       */
      'name' => ['nullable', 'string', 'max:255'],

      /**
       * Алиас названия проекта для совместимости.
       * @var string|null
       * @deprecated since 2026-09-06. Рекомендуется передавать name.
       */
      'title' => ['nullable', 'string', 'max:255'],

      /**
       * Сохраненное состояние конфигуратора (принимает структурированный объект или JSON-строку).
       * @var array<string, mixed>|string
       */
      'calc_state' => ['required'],

      /**
       * Международный трехбуквенный ISO-код валюты.
       * @var string
       * @example "RUB"
       */
      'currency' => ['required', 'string', 'max:3'],

      /**
       * Итоговая сумма расчета к оплате.
       * @var float|string
       * @example 145184.00
       */
      'grand_total' => ['required', 'numeric', 'min:0'],

      /**
       * Локаль/язык расчета.
       * @var string|null
       * @example "ru"
       */
      'locale' => ['nullable', 'string', 'max:5'],

      /**
       * Финансовый блок верхнего уровня.
       * @var array{currency?: string, total?: float, grand_total?: float, VAT?: float, VAT_percent?: float, discount?: float, discount_percent?: float}|null
       * @since 2026-09-06
       */
      'price' => ['nullable', 'array'],
      'price.currency' => ['nullable', 'string', 'max:3'],
      'price.total' => ['nullable', 'numeric', 'min:0'],
      'price.grand_total' => ['nullable', 'numeric', 'min:0'],
      'price.VAT' => ['nullable', 'numeric', 'min:0'],
      'price.VAT_percent' => ['nullable', 'numeric', 'min:0'],
      'price.discount' => ['nullable', 'numeric', 'min:0'],
      'price.discount_percent' => ['nullable', 'numeric', 'min:0'],

      /**
       * Данные покупателя.
       * @var array{name?: string|null, phone?: string|null, email?: string|null, city?: string|null, address?: string|null}|null
       */
      'customer' => ['nullable', 'array'],
      'customer.name' => ['nullable', 'string', 'max:255'],
      'customer.phone' => ['nullable', 'string', 'max:50'],
      'customer.email' => ['nullable', 'email', 'max:255'],
      'customer.city' => ['nullable', 'string', 'max:255'],
      'customer.address' => ['nullable', 'string'],

      /**
       * Идентификатор ответственного менеджера.
       * @var int|string|null
       * @example 1
       */
      'manager_id' => ['nullable'],

      /**
       * Комментарий клиента.
       * @var string|null
       * @example "Монтаж планируется на субботу"
       * @since 2026-09-06
       */
      'customer_comment' => ['nullable', 'string'],

      /**
       * Внутренний комментарий менеджера.
       * @var string|null
       * @example "Предоставлена скидка 5%"
       * @since 2026-09-06
       */
      'manager_comment' => ['nullable', 'string'],

      /**
       * Секции (изделия) расчета.
       * @var array<int, mixed>
       */
      'results' => ['required', 'array', 'min:1'],
      'results.*.id' => ['nullable', 'string', 'max:100'],
      'results.*.title' => ['required', 'string', 'max:255'],
      'results.*.type' => ['nullable', 'string', 'max:100'],
      'results.*.draw' => ['nullable', 'array'],
      'results.*.draw.*' => ['string'],
      'results.*.description' => ['nullable', 'array'],

      /**
       * Смета конкретного изделия.
       * @var array<int, mixed>
       */
      'results.*.estimate' => ['required', 'array', 'min:1'],

      /**
       * Финансовые показатели конкретного изделия.
       * @var array<string, mixed>
       */
      'results.*.price' => ['required', 'array'],
      'results.*.price.currency' => ['required', 'string', 'max:3'],
      'results.*.price.total' => ['required', 'numeric', 'min:0'],
      'results.*.price.grand_total' => ['required', 'numeric', 'min:0'],
      'results.*.price.VAT' => ['nullable', 'numeric', 'min:0'],
      'results.*.price.VAT_percent' => ['nullable', 'numeric', 'min:0'],
      'results.*.price.discount' => ['nullable', 'numeric', 'min:0'],
      'results.*.price.discount_percent' => ['nullable', 'numeric', 'min:0'],

      /**
       * Метаданные геометрии и товарных связей.
       * @var array{properties?: array<string, mixed>, items?: array<string, mixed>}|null
       */
      'results.*.meta' => ['nullable', 'array'],
      'results.*.meta.properties' => ['nullable', 'array'],
      'results.*.meta.items' => ['nullable', 'array'],
    ];
  }

}