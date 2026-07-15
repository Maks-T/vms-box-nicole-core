<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\Customer;

/**
 * Ресурс детальной информации о покупателе.
 *
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      /**
       * Системный ID покупателя в БД.
       * @var int
       */
      'id' => $this->id,

      /**
       * Полное имя (ФИО) покупателя.
       * @var string
       */
      'full_name' => (string)$this->full_name,

      /**
       * Имя покупателя.
       * @var string
       */
      'first_name' => (string)$this->first_name,

      /**
       * Фамилия покупателя.
       * @var string|null
       */
      'last_name' => $this->last_name ? (string)$this->last_name : null,

      /**
       * Отчество покупателя.
       * @var string|null
       */
      'middle_name' => $this->middle_name ? (string)$this->middle_name : null,

      /**
       * Контактный телефон покупателя.
       * @var string
       */
      'phone' => (string)$this->phone,

      /**
       * Нормализованный номер телефона (только цифры) для внешних интеграций.
       * @var string
       */
      'phone_normalized' => (string)$this->phone_normalized,

      /**
       * Электронная почта покупателя.
       * @var string|null
       */
      'email' => $this->email ? (string)$this->email : null,

      /**
       * Адрес доставки или проведения монтажных работ.
       * @var string|null
       */
      'address' => $this->address ? (string)$this->address : null,
    ];
  }
}