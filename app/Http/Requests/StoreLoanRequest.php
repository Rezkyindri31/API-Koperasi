<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['admin','karyawan'], true);
    }

 
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        if (is_string($amount)) {
            $normalized = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $amount));
            $this->merge(['amount' => $normalized]);
        }

        // Alias ke snapshot
        $this->merge([
            'phone_snapshot'   => $this->input('phone_snapshot', $this->input('phone')),
            'address_snapshot' => $this->input('address_snapshot', $this->input('address')),
        ]);

        // submitted_at default now jika kosong
        if (!$this->filled('submitted_at')) {
            $this->merge(['submitted_at' => Carbon::now()->toDateTimeString()]);
        }
    }

    public function rules(): array
    {
        $isAdmin = $this->user()?->role === 'admin';
        $statusEnum = ['applied', 'approved', 'rejected', 'paid'];

        // Kolom yang benar-benar ada di tabel loans
        $rules = [
            'amount'           => ['required','bail','numeric','min:0'],
            'submitted_at'     => ['required','date'],
            'phone_snapshot'   => ['required','string','max:30'],
            'address_snapshot' => ['required','string','max:255'],
        ];

        if ($isAdmin) {
            // Admin boleh set ini saat create
            $rules = array_merge($rules, [
                'status'      => ['sometimes', Rule::in($statusEnum)],
                'approved_by' => ['sometimes','nullable','exists:users,id'],
                'approved_at' => ['sometimes','nullable','date'],
                'user_id'     => ['sometimes','exists:users,id'], // untuk membuatkan atas nama user lain
            ]);
        } else {
            // Non-admin dilarang menyentuh field berikut (akan diisi default di controller)
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
            'amount.required'           => 'Nominal pinjaman wajib diisi.',
            'amount.numeric'            => 'Nominal pinjaman harus berupa angka.',
            'amount.min'                => 'Nominal pinjaman tidak boleh negatif.',
            'submitted_at.required'     => 'Tanggal pengajuan wajib diisi.',
            'submitted_at.date'         => 'Tanggal pengajuan tidak valid.',
            'phone_snapshot.required'   => 'Nomor telepon wajib diisi.',
            'address_snapshot.required' => 'Alamat wajib diisi.',
            'status.in'                 => 'Status tidak valid.',
            'status.prohibited'         => 'Anda tidak berwenang mengatur status pinjaman.',
            'approved_by.prohibited'    => 'Anda tidak berwenang mengatur penyetuju.',
            'approved_at.prohibited'    => 'Anda tidak berwenang mengatur waktu persetujuan.',
            'user_id.prohibited'        => 'Anda tidak berwenang menentukan pemilik pinjaman.',
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