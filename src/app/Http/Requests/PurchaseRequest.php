<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'payment_type' => ['required', 'in:card,convenience'],
            'address_mode' => ['nullable', 'in:saved,new'],
            'postal_code' => ['required_if:address_mode,new', 'regex:/^\d{3}-\d{4}$/'],
            'address' => ['required_if:address_mode,new', 'string'],
            'building' => ['nullable', 'string'],
        ];
    }

    public function messages(): array {
        return [
            'payment_type.required' => '支払い方法を選択してください',
            'payment_type.in' => '支払い方法の選択が不正です',
            'postal_code.required_if' => '新しい配送先の郵便番号を入力してください',
            'postal_code.regex' => '郵便番号はハイフンあり ８文字(例:123-4567)で入力してください',
            'address.required_if' => '新しい配送先の住所を入力してください'
        ];
    }
}
