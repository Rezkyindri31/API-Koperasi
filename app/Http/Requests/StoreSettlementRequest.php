<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreSettlementRequest extends FormRequest
{
    /**
     * Hanya karyawan yang boleh mengunggah bukti pelunasan.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'karyawan';
    }

    /**
     * Normalisasi input sebelum validasi:
     * - amount: buang pemisah, koma -> titik
     * - paid_at: default sekarang jika tidak dikirim
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        if (is_string($amount)) {
            $normalized = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $amount));
            $this->merge(['amount' => $normalized]);
        }

        if (!$this->filled('paid_at')) {
            $this->merge(['paid_at' => Carbon::now()->toDateTimeString()]);
        }
    }

    /**
     * Aturan validasi untuk membuat settlement (upload bukti).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'loan_id' => ['required', 'exists:loans,id'],
            'paid_at' => ['sometimes', 'date'],
            'amount'  => ['sometimes', 'numeric', 'min:0'],
            'proof'   => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],

            // Proteksi field sensitif agar tidak bisa diisi dari client
            'status'       => ['prohibited'],
            'reviewed_by'  => ['prohibited'],
            'reviewed_at'  => ['prohibited'],
            'user_id'      => ['prohibited'],
            'proof_path'   => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'loan_id.required' => 'Loan wajib dipilih.',
            'loan_id.exists'   => 'Loan tidak ditemukan.',
            'paid_at.date'     => 'Tanggal bayar tidak valid.',
            'amount.numeric'   => 'Nominal bayar harus angka.',
            'amount.min'       => 'Nominal bayar minimal 0.',
            'proof.required'   => 'Bukti pembayaran wajib diunggah.',
            'proof.file'       => 'Bukti harus berupa file.',
            'proof.mimes'      => 'Format bukti harus JPG, JPEG, PNG, atau PDF.',
            'proof.max'        => 'Ukuran bukti maksimal 2MB.',
        ];
    }

    /**
     * Rapikan payload setelah validasi agar hanya field yang diizinkan yang tersisa.
     */
    protected function passedValidation(): void
    {
        $this->replace(
            collect($this->validated())
                ->only(['loan_id', 'paid_at', 'amount', 'proof'])
                ->all()
        );
    }
}