<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Biarkan Policy/Controller menentukan boleh tidaknya.
        return (bool) $this->user();
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        if (is_string($amount)) {
            // buang simbol non angka, ganti koma -> titik
            $normalized = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $amount));
            $this->merge(['amount' => $normalized]);
        }
    }

    public function rules(): array
    {
        $isAdmin = $this->user()?->role === 'admin';

        // rules dasar (semua optional karena partial update)
        $rules = [
            'paid_at' => ['sometimes', 'date'],
            'amount'  => ['sometimes', 'numeric', 'min:0'],
            // izinkan ganti bukti jika memang diperlukan (hapus jika tidak ingin)
            'proof'   => ['sometimes', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],

            // Lindungi field sensitif agar tidak bisa diutak-atik via client
            'reviewed_by'  => ['prohibited'],
            'reviewed_at'  => ['prohibited'],
            'user_id'      => ['prohibited'],
            'loan_id'      => ['prohibited'],
            'proof_path'   => ['prohibited'],
        ];

        if ($isAdmin) {
            // Admin boleh mengubah status
            $rules['status'] = ['sometimes', Rule::in(['submitted','approved','rejected'])];
        } else {
            // Non-admin tidak boleh mengubah status
            $rules['status'] = ['prohibited'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'paid_at.date'      => 'Tanggal bayar tidak valid.',
            'amount.numeric'    => 'Nominal bayar harus angka.',
            'amount.min'        => 'Nominal bayar minimal 0.',
            'proof.file'        => 'Bukti harus berupa file.',
            'proof.mimes'       => 'Format bukti harus JPG, JPEG, PNG, atau PDF.',
            'proof.max'         => 'Ukuran bukti maksimal 2MB.',
            'status.in'         => 'Status tidak valid.',
            'status.prohibited' => 'Anda tidak berwenang mengubah status.',
        ];
    }

    protected function passedValidation(): void
    {
        // pastikan hanya field tervalidasi yang dipakai controller
        $this->replace($this->validated());
    }
}