<?php

namespace App\Policies;

use App\Models\Saving;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SavingPolicy
{
// app/Policies/SavingPolicy.php
public function create(User $user): bool
{
    return in_array($user->role, ['admin','staff']); // sesuaikan
}
public function update(User $user, Saving $saving): bool
{
    return $user->role === 'admin' || $user->id === $saving->user_id;
}
public function delete(User $user, Saving $saving): bool
{
    return $user->role === 'admin' || $user->id === $saving->user_id;
}

}