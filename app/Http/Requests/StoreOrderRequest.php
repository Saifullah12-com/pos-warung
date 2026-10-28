<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // sudah dibatasi middleware auth
    }
    protected function prepareForValidation(): void
    {
        // Samakan format: "Hutang", " hutang " dan "HUTANG" semuanya menjadi "hutang"
        if ($this->has('method')) {
            $this->merge(['method' => strtolower(trim((string) $this->input('method')))]);
        }
    }
    public function rules(): array
    {
        return [
            'order_type'      => ['required', 'in:dine_in,takeaway'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*'         => ['integer', 'min:1', 'max:99'],
            'method'          => ['required', 'in:tunai,qris,hutang'],
            'customer_id'     => ['required_if:method,hutang', 'nullable', 'exists:customers,id'],
            'amount_received' => ['required_if:method,tunai', 'nullable', 'integer', 'min:0'],
            'reference_no'    => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_type.in'               => 'Jenis pesanan tidak valid.',
            'items.required'              => 'Pesanan masih kosong. Pilih menu terlebih dahulu.',
            'items.min'                   => 'Pesanan masih kosong. Pilih menu terlebih dahulu.',
            'method.in'                   => 'Metode pembayaran tidak valid.',
            'customer_id.required_if'     => 'Pilih pelanggan untuk transaksi hutang.',
            'customer_id.exists'          => 'Pelanggan tidak ditemukan.',
            'amount_received.required_if' => 'Isi uang yang diterima.',
            'amount_received.integer'     => 'Uang yang diterima harus berupa angka.',
        ];
    }
}
