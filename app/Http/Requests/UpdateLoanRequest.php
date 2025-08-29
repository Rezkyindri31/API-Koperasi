<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth check dasar; otorisasi detail pakai Policy di controller
        return (bool) $this->user();
    }

    /**
     * Normalisasi input sebelum validasi:
     * - Map 'phone'/'address' -> '*_snapshot'
     * - Normalisasi 'amount' (hapus simbol, koma->titik)
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        if (is_string($amount)) {
            $normalized = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $amount));
            $this->merge(['amount' => $normalized]);
        }

        $this->merge([
            'phone_snapshot'   => $this->input('phone_snapshot', $this->input('phone')),
            'address_snapshot' => $this->input('address_snapshot', $this->input('address')),
        ]);
    }

    public function rules(): array
    {
        $isAdmin = $this->user()?->role === 'admin';

        $statusEnum = ['applied', 'approved', 'rejected', 'paid'];

        // Partial update → pakai 'sometimes'
        $rules = [
            'amount'           => ['sometimes','bail','numeric','min:0'],
            'submitted_at'     => ['sometimes','date'],
            'phone_snapshot'   => ['sometimes','nullable','string','max:30'],
            'address_snapshot' => ['sometimes','nullable','string'],
        ];

        if ($isAdmin) {
            $rules = array_merge($rules, [
                'status'      => ['sometimes', Rule::in($statusEnum)],
                'approved_by' => [
                    'sometimes','nullable','exists:users,id',
                    Rule::requiredIf(fn () => in_array($this->input('status'), ['approved','rejected'], true)),
                ],
                'approved_at' => [
                    'sometimes','nullable','date',
                    Rule::requiredIf(fn () => in_array($this->input('status'), ['approved','rejected'], true)),
                ],
                'user_id'     => ['sometimes','exists:users,id'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'status'      => ['prohibited'],
                'approved_by' => ['prohibited'],
                'approved_at' => ['prohibited'],
                'user_id'     => ['prohibited'],
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'amount.numeric'            => 'Nominal pinjaman harus berupa angka.',
            'amount.min'                => 'Nominal pinjaman tidak boleh negatif.',
            'submitted_at.date'         => 'Tanggal pengajuan tidak valid.',
            'phone_snapshot.max'        => 'Nomor telepon maksimal 30 karakter.',
            'status.in'                 => 'Status tidak valid.',
            'status.prohibited'         => 'Anda tidak berwenang mengubah status pinjaman.',
            'approved_by.required'      => 'ID penyetuju wajib diisi saat status disetujui/ditolak.',
            'approved_by.exists'        => 'Penyetuju tidak ditemukan.',
            'approved_by.prohibited'    => 'Anda tidak berwenang mengatur penyetuju.',
            'approved_at.required'      => 'Waktu persetujuan wajib diisi saat status disetujui/ditolak.',
            'approved_at.date'          => 'Waktu persetujuan tidak valid.',
            'approved_at.prohibited'    => 'Anda tidak berwenang mengatur waktu persetujuan.',
            'user_id.prohibited'        => 'Anda tidak berwenang menentukan pemilik pinjaman.',
            'user_id.exists'            => 'Pemilik pinjaman tidak ditemukan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'amount'           => 'nominal pinjaman',
            'submitted_at'     => 'tanggal pengajuan',
            'phone_snapshot'   => 'nomor telepon',
            'address_snapshot' => 'alamat',
        ];
    }

 
    protected function passedValidation(): void
    {
        $this->replace(
            collect($this->validated())->except(['phone','address'])->all()
        );
    }
}