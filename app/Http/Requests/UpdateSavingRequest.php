<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Saving;

class UpdateSavingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;

        /** @var \App\Models\Saving|null $saving */
        $saving = $this->route('saving') ?? null;
        if (!$saving) return false;

        if ($user->role === 'admin') return true;

        return (int)$saving->user_id === (int)$user->id;
    }

    public function rules(): array
    {
        // Ambil ID record yang sedang diupdate untuk keperluan ignore unique
        $saving = $this->route('saving');
        $savingId = $saving instanceof Saving ? $saving->id : null;

        return [
            // gunakan "sometimes" agar PATCH/partial update nyaman
            'user_id' => ['sometimes','integer','exists:users,id'],

            'type' => ['sometimes', Rule::in(['wajib','pokok','sukarela'])],
            'month' => ['sometimes','date_format:Y-m-d'],

            'amount' => ['sometimes','numeric','min:1'],
            Rule::unique('savings')->ignore($savingId)->where(function ($q) {
                $userId = $this->input('user_id', optional($this->route('saving'))->user_id);
                $type   = $this->input('type', optional($this->route('saving'))->type);
                $month  = $this->input('month', optional($this->route('saving'))->month);
                return $q->where('user_id', $userId)
                         ->where('type', $type)
                         ->where('month', $month);
            }),
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
            'type.in'                 => 'Tipe simpanan harus salah satu dari: wajib, pokok, atau sukarela.',
            'month.date_format'       => 'Format bulan tidak valid. Gunakan format YYYY-MM-DD.',
            'amount.min'              => 'Nominal minimal adalah 1.',
            'savings_unique'          => 'Data simpanan untuk kombinasi karyawan, jenis, dan bulan tersebut sudah ada.',
        ];
    }
}