<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavingRequest extends FormRequest
{
  public function authorize(): bool
        {
            return (bool) $this->user(); 
        }

protected function prepareForValidation(): void
{
    $user = $this->user();
    if ($user && $user->role !== 'admin') {
        // paksa user_id = diri sendiri
        $this->merge(['user_id' => $user->id]);
    }
}

    public function rules(): array
    {
        return [
            'user_id' => ['required','integer','exists:users,id'],
            'type'    => ['required', Rule::in(['wajib','pokok','sukarela'])],
            'month'   => ['required','date_format:Y-m-d'], 
            'amount'  => ['required','numeric','min:1'],
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
            'type.in' => 'Tipe simpanan harus salah satu dari: wajib, pokok, atau sukarela.',
        ];
    }
}