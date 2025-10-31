<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_name' => ['required'],
            'description' => ['required', 'max:255'],
            'image' => ['required', 'mimes:jpeg,png'],
            'categories' => ['required'],
            'condition' => ['required'],
            'price' => ['required', 'numeric','min:0'],
        ];
    }

    public function messages():array {
        return [
            'product_name.required' => '商品名は必ず入力してください',
            'description.required' => '商品説明は必ず入力してください',
            'description.max' => '商品説明は255文字以内で入力してください',
            'image.required' => '商品画像は必ずアップロードしてください',
            'image.mimes' => '商品画像は.jpegまたは.png形式を指定してください',
            'categories.required' => '商品のカテゴリーを選択してください',
            'condition.required' => '商品の状態を選択してください',
            'price.required' => '商品価格は必ず入力してください',
            'price.numeric' => '商品価格は数値で入力してください',
            'price.min' => '商品価格は0円以上で入力してください',
        ];
    }
}
