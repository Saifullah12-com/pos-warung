<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi middleware role:admin
    }

    public function rules(): array
    {
        return [
            'category_id'  => ['required', 'exists:categories,id'],
            'name'         => ['required', 'string', 'max:100'],
            'price'        => ['required', 'integer', 'min:0', 'max:10000000'],
            'photo'        => ['nullable', 'image', 'max:2048'],
            'is_available' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['category_id' => 'kategori', 'name' => 'nama produk', 'price' => 'harga', 'photo' => 'foto'];
    }
}
