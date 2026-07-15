<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SaveOrderRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'code' => ['nullable', 'string', 'exists:orders,code'],
      'calc_state' => ['required'],

      'currency' => ['required', 'string', 'max:3'],
      'grand_total' => ['required', 'numeric', 'min:0'],
      'locale' => ['nullable', 'string', 'max:5'],

      'customer' => ['nullable', 'array'],
      'customer.name' => ['nullable', 'string', 'max:255'],
      'customer.phone' => ['nullable', 'string', 'max:50'],
      'customer.email' => ['nullable', 'email', 'max:255'],
      'customer.city' => ['nullable', 'string', 'max:255'],
      'customer.address' => ['nullable', 'string'],

      'manager_id' => ['nullable', 'integer', 'exists:users,id'],
      'customer_comment' => ['nullable', 'string'],
      'manager_comment' => ['nullable', 'string'],

      'results' => ['required', 'array', 'min:1'],
      'results.*.title' => ['required', 'string', 'max:255'],
      'results.*.draw' => ['nullable', 'array'],
      'results.*.draw.*' => ['string'],

      'results.*.description' => ['nullable', 'array'],
      'results.*.description.*.name' => ['required_with:results.*.description', 'string', 'max:255'],

      'results.*.description.*.description' => ['nullable', 'string'],
      'results.*.description.*.tooltip' => ['nullable', 'string'],

      'results.*.estimate' => ['required', 'array', 'min:1'],

      'results.*.price.currency' => ['required', 'string', 'max:3'],
      'results.*.price.total' => ['required', 'numeric', 'min:0'],
      'results.*.price.grand_total' => ['required', 'numeric', 'min:0'],
      'results.*.price.VAT' => ['required', 'numeric', 'min:0'],
      'results.*.price.VAT_percent' => ['required', 'numeric', 'min:0'],
      'results.*.price.discount' => ['required', 'numeric', 'min:0'],
      'results.*.price.discount_percent' => ['required', 'numeric', 'min:0'],

      'results.*.meta.properties' => ['nullable', 'array'],
      'results.*.meta.properties.form' => ['nullable', 'string', 'max:50'],
      'results.*.meta.items' => ['required', 'array'],
    ];
  }
}
