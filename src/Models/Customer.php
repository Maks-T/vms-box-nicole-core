<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Support\PhoneHelper;
use Nicole\Box\Core\Support\CustomerHelper;

class Customer extends Model
{
  protected $fillable = [
    'first_name',
    'last_name',
    'middle_name',
    'full_name', //виртуальное поле
    'phone',
    'phone_normalized',
    'email',
    'address',
    'admin_notes',
    'last_ip',
  ];

  protected static function booted(): void
  {
    static::saving(function (Customer $customer) {
      if ($customer->isDirty('phone') && !empty($customer->phone)) {
        $customer->phone_normalized = PhoneHelper::normalize($customer->phone);
      }
    });
  }

  /**
   * Умный поиск покупателя по телефону (significant part) или по email.
   *
   * @param string|null $phone
   * @param string|null $email
   * @return self|null
   */
  public static function findByPhoneOrEmail(?string $phone, ?string $email): ?self
  {
    if ($phone) {
      $phoneSearch = PhoneHelper::forSearch($phone);
      $customer = self::where('phone_normalized', 'LIKE', "%{$phoneSearch}")->first();
      if ($customer) {
        return $customer;
      }
    }

    if ($email) {
      return self::where('email', trim(strtolower($email)))->first();
    }

    return null;
  }

  /**
   * Мутатор для автоматического разбора ФИО при установке свойства full_name.
   *
   * @param string|null $value
   * @return void
   */
  public function setFullNameAttribute(?string $value): void
  {
    $nameParts = CustomerHelper::splitFullName($value);

    $this->attributes['first_name'] = $nameParts['first_name'];
    $this->attributes['last_name'] = $nameParts['last_name'];
    $this->attributes['middle_name'] = $nameParts['middle_name'];
  }

  public function orders(): HasMany
  {
    return $this->hasMany(Order::class, 'customer_id');
  }

  /**
   * Аксессор для сборки ФИО из полей БД.
   */
  public function getFullNameAttribute(): string
  {
    return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
  }
}