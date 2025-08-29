<?php

namespace App\Policies;

use App\Models\Settlement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SettlementPolicy
{
    public function viewAny(User $user)
    {
        // Siapa pun yang login boleh akses daftar (controller sudah filter: admin lihat semua, karyawan hanya miliknya)
        return $user
            ? Response::allow()
            : Response::deny('Harus login.');
    }

    public function view(User $user, Settlement $settlement)
    {
        return ($user->role === 'admin' || $settlement->user_id === $user->id)
            ? Response::allow()
            : Response::deny('Anda hanya dapat melihat pelunasan milik Anda.');
    }

    public function create(User $user)
    {
        return $user->role === 'karyawan'
            ? Response::allow()
            : Response::deny('Hanya karyawan yang dapat mengunggah bukti pelunasan.');
    }

    // Custom ability untuk approve/reject settlement
    public function approve(User $user, Settlement $settlement)
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Hanya admin yang dapat me-review bukti pelunasan.');
    }

    public function update(User $user, Settlement $settlement)
    {
        // Tidak dipakai di flow ini; biarkan admin only
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Tidak diizinkan.');
    }

    public function delete(User $user, Settlement $settlement)
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Tidak diizinkan.');
    }

    public function restore(User $user, Settlement $settlement)
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Tidak diizinkan.');
    }

    public function forceDelete(User $user, Settlement $settlement)
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Tidak diizinkan.');
    }
}