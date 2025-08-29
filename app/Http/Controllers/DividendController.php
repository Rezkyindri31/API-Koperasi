<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Saving;

class DividendController extends Controller
{
    public function show(Request $req)
    {
        $data = $req->validate([
            'year'    => 'nullable|integer|min:2000|max:2100',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $year = (int)($data['year'] ?? now()->year);
        $auth = $req->user();

        $targetUserId = $auth->id;
        if ($auth->role === 'admin' && !empty($data['user_id'])) {
            $targetUserId = (int)$data['user_id'];
        }

        $sum = (float) Saving::query()
            ->where('type', 'wajib')
            ->where('user_id', $targetUserId)
            ->whereYear('month', $year)
            ->sum('amount'); 

        $dividend = ((($sum * 0.93) * 0.1) / 12) * 0.6;
        $dividend = round($dividend, 2);

        return response()->json([
            'year'        => $year,
            'user_id'     => $targetUserId,
            'base_wajib'  => $sum,
            'dividend'    => $dividend,
        ]);
    }
}