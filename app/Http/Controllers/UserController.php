<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Saving;
use App\Models\Loan;

class UserController extends Controller
{
    public function index(Request $req)
    {
        abort_unless($req->user()->role === 'admin', 403);
        $q = User::query();
        if ($role = $req->query('role')) {
            $q->where('role', $role);
        }
        return $q->select('id','name')->orderBy('name')->get();
    }

    public function summary(Request $req)
    {
        abort_unless($req->user()->role === 'admin', 403);

        $roleCounts = User::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $totalSavings = Saving::sum('amount');

        $totalLoans = Loan::sum('amount');

        return response()->json([
            'roles' => $roleCounts,
            'total_savings' => $totalSavings,
            'total_loans' => $totalLoans,
        ]);
    }
}