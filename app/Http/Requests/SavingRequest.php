<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavingRequest extends FormRequest
{
    /**
     * Hanya admin yang boleh membuat/mengubah data simpanan.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Normalisasi input sebelum validasi.
     * - month: izinkan kirim "YYYY-MM", akan diubah jadi "YYYY-MM-01"
     */
    protected function prepareForValidation(): void
    {
        $month = $this->input('month');

        if (is_string($month)) {
            $month = trim($month);

            // Jika format "YYYY-MM", tambahkan "-01"
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $month = $month . '-01';
            }
        }

        $this->merge([
            'month' => $month,
        ]);
    }
    public function rules(): array
    {
        $isCreate = $this->isMethod('post'); 

        return [
            'user_id' => array_filter([
                $isCreate ? 'required' : 'sometimes',
                'integer',
                'exists:users,id',
            ]),

            'type' => array_filter([
                $isCreate ? 'required' : 'sometimes',
                Rule::in(['wajib','pokok','sukarela']),
            ]),

            // Disimpan sebagai DATE di DB -> gunakan format Y-m-d
            'month' => array_filter([
                $isCreate ? 'required' : 'sometimes',
                'date_format:Y-m-d',
            ]),

            'amount' => array_filter([
                $isCreate ? 'required' : 'sometimes',
                'numeric',
                'min:1',
            ]),
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'karyawan',
            'type'    => 'jenis simpanan',
            'month'   => 'bulan simpanan',
            'amount'  => 'nominal',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'           => 'Tipe simpanan harus salah satu dari: wajib, pokok, atau sukarela.',
            'month.date_format' => 'Format bulan tidak valid. Gunakan YYYY-MM atau YYYY-MM-DD.',
            'amount.min'        => 'Nominal minimal adalah 1.',
        ];
    }
}