<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // sudah dibatasi middleware role:admin di route
    }

    public function rules(): array
    {
        return [
            'spent_at'           => ['required', 'date', 'before_or_equal:now'],
            'category'           => ['required', Rule::in(Expense::CATEGORIES)],
            'supplier'           => ['nullable', 'string', 'max:100'],
            'receipt'            => ['nullable', 'image', 'max:2048'],
            'items'              => ['required', 'array', 'min:1', 'max:50'],
            'items.*.name'       => ['required', 'string', 'max:100'],
            'items.*.qty'        => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'items.*.unit'       => ['required', 'string', 'max:20'],
            'items.*.unit_price' => ['required', 'integer', 'min:1', 'max:100000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'spent_at.required'          => 'Isi tanggal dan waktu pembelian.',
            'spent_at.before_or_equal'   => 'Waktu pembelian tidak boleh di masa depan.',
            'category.in'                => 'Jenis pengeluaran tidak valid.',
            'receipt.image'              => 'Nota harus berupa gambar.',
            'receipt.max'                => 'Ukuran foto nota maksimal 2 MB.',
            'items.required'             => 'Tambahkan minimal satu barang.',
            'items.min'                  => 'Tambahkan minimal satu barang.',
            'items.max'                  => 'Maksimal 50 barang dalam satu pembelian.',
            'items.*.name.required'      => 'Nama barang wajib diisi.',
            'items.*.qty.required'       => 'Jumlah barang wajib diisi.',
            'items.*.qty.min'            => 'Jumlah barang minimal 0,01.',
            'items.*.unit.required'      => 'Satuan wajib diisi.',
            'items.*.unit_price.required' => 'Harga per satuan wajib diisi.',
            'items.*.unit_price.integer' => 'Harga harus berupa angka bulat.',
            'items.*.unit_price.min'     => 'Harga minimal Rp1.',
        ];
    }
}
