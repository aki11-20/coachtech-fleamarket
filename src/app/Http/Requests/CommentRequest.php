<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array {
        return [
            'body.required' => '商品コメントは必ず入力してください',
            'body.max' => '商品コメントは255文字以内で入力してください',
        ];
    }

    public function attributes(): array {
        return [
            'body' => '商品コメント',
        ];
    }
}
