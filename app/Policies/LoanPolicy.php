<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LoanPolicy
{
    public function viewAny(User $user) {
        return in_array($user->role, ['admin','karyawan'])
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar pinjaman.');
    }

    public function view(User $user, Loan $loan) {
        return ($user->role === 'admin' || $loan->user_id === $user->id)
            ? Response::allow()
            : Response::deny('Anda hanya dapat melihat pinjaman Anda sendiri.');
    }

    public function create(User $user) {
        return in_array($user->role, ['karyawan','admin'])
            ? Response::allow()
            : Response::deny('Hanya karyawan atau admin yang dapat membuat pinjaman.');
    }

    public function approve(User $user, Loan $loan) {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Hanya admin yang dapat menyetujui pinjaman.');
    }

    public function update(User $user, Loan $loan) {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Hanya admin yang dapat mengubah pinjaman.');
    }

    public function delete(User $user, Loan $loan) {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Hanya admin yang dapat menghapus pinjaman.');
    }
}