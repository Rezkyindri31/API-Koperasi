<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class LoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalisasi amount dari string berformat ke numeric string
        $amount = $this->input('amount');
        if (is_string($amount)) {
            $normalized = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $amount));
            $this->merge(['amount' => $normalized]);
        }

        // Alias -> snapshot
        $this->merge([
            'phone_snapshot'   => $this->input('phone_snapshot', $this->input('phone')),
            'address_snapshot' => $this->input('address_snapshot', $this->input('address')),
        ]);

        // Default submitted_at saat create jika tidak dikirim
        if ($this->isMethod('post') && !$this->filled('submitted_at')) {
            $this->merge(['submitted_at' => Carbon::now()->toDateTimeString()]);
        }
    }

    public function rules(): array
    {
        $isAdmin  = $this->user()?->role === 'admin';
        $isCreate = $this->isMethod('post');

        $statusEnum = ['applied', 'approved', 'rejected', 'paid'];

        $base = [
            'amount'           => [$isCreate ? 'required' : 'sometimes', 'bail', 'numeric', 'min:0'],
            'submitted_at'     => [$isCreate ? 'required' : 'sometimes', 'date'],
            'phone_snapshot'   => ['sometimes', 'nullable', 'string', 'max:191'],
            'address_snapshot' => ['sometimes', 'nullable', 'string'],
        ];

        if ($isAdmin) {
            // Admin boleh set status, approved_* dan user_id
            $adminRules = [
                'status'      => ['sometimes', Rule::in($statusEnum)],
                'approved_by' => [
                    'sometimes',
                    'nullable',
                    'exists:users,id',
                    Rule::requiredIf(function () {
                        $status = $this->input('status');
                        return in_array($status, ['approved', 'rejected'], true);
                    }),
                ],
                'approved_at' => [
                    'sometimes',
                    'nullable',
                    'date',
                    Rule::requiredIf(function () {
                        $status = $this->input('status');
                        return in_array($status, ['approved', 'rejected'], true);
                    }),
                ],
                'user_id' => [$isCreate ? 'sometimes' : 'sometimes', 'exists:users,id'],
            ];
            $base = array_merge($base, $adminRules);
        } else {
            // Non-admin tidak boleh menyentuh kolom ini; user_id ditetapkan di Controller dari auth()
            $restricted = [
                'status'      => ['prohibited'],
                'approved_by' => ['prohibited'],
                'approved_at' => ['prohibited'],
                'user_id'     => ['prohibited'],
            ];
            $base = array_merge($base, $restricted);
        }

        return $base;
    }

    public function messages(): array
    {
        return [
            'amount.required'        => 'Nominal pinjaman wajib diisi.',
            'amount.numeric'         => 'Nominal pinjaman harus berupa angka.',
            'amount.min'             => 'Nominal pinjaman tidak boleh negatif.',
            'submitted_at.required'  => 'Tanggal pengajuan wajib diisi.',
            'submitted_at.date'      => 'Tanggal pengajuan tidak valid.',
            'status.in'              => 'Status tidak valid.',
            'status.prohibited'      => 'Anda tidak berwenang mengubah status pinjaman.',
            'approved_by.required'   => 'ID penyetuju wajib diisi saat status disetujui/ditolak.',
            'approved_by.exists'     => 'Penyetuju tidak ditemukan.',
            'approved_by.prohibited' => 'Anda tidak berwenang mengatur penyetuju.',
            'approved_at.required'   => 'Waktu persetujuan wajib diisi saat status disetujui/ditolak.',
            'approved_at.date'       => 'Waktu persetujuan tidak valid.',
            'approved_at.prohibited' => 'Anda tidak berwenang mengatur waktu persetujuan.',
            'user_id.prohibited'     => 'Anda tidak berwenang menentukan pemilik pinjaman.',
            'user_id.exists'         => 'Pemilik pinjaman tidak ditemukan.',
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
        $this->replace(collect($this->validated())->except(['phone', 'address'])->all());
    }
}