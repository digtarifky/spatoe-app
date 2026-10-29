<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkshopCheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'max:25'],
            'full_name' => ['required', 'string', 'max:100'],
            'default_address' => ['required', 'string', 'max:500'],
            'campus_cluster' => ['nullable', 'string', 'max:50'],
            'payment_status' => ['required', Rule::in(['UNPAID', 'PAID'])],
            'payment_method' => ['required', Rule::in(['COD', 'QRIS', 'BANK_TRANSFER', 'SPATOE_PASS'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.shoe_brand_model' => ['required', 'string', 'max:100'],
            'items.*.shoe_material' => ['required', 'string', 'max:50'],
            'items.*.shoe_color' => ['nullable', 'string', 'max:50'],
            'items.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'items.*.storage_shelf_number' => ['nullable', 'string', 'max:20'],
            'items.*.defect_notes' => ['nullable', 'string', 'max:1000'],
            'items.*.before_photo' => ['nullable', 'file', 'mimes:webp,jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Custom validation messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.required' => 'Nomor WhatsApp pelanggan wajib diisi.',
            'full_name.required' => 'Nama lengkap pelanggan wajib diisi.',
            'default_address.required' => 'Alamat kos/pengiriman pelanggan wajib diisi.',
            'items.required' => 'Minimal harus menambahkan 1 pasang sepatu.',
            'items.min' => 'Minimal harus menambahkan 1 pasang sepatu.',
            'items.*.shoe_brand_model.required' => 'Merk & seri sepatu wajib diisi.',
            'items.*.shoe_material.required' => 'Jenis material sepatu wajib dipilih.',
            'items.*.service_id.required' => 'Paket layanan wajib dipilih.',
            'items.*.service_id.exists' => 'Layanan yang dipilih tidak valid.',
            'items.*.before_photo.mimes' => 'Format foto harus berupa webp, jpg, jpeg, atau png.',
            'items.*.before_photo.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
