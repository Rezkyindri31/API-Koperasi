<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSavingRequest;
use App\Http\Requests\UpdateSavingRequest;
use Illuminate\Http\Request;
use App\Models\Saving;

class SavingController extends Controller
{
    public function index(Request $req)
    {
        $user  = $req->user();
        $query = Saving::query()->with('user');

        if ($req->filled('type')) {
            $query->where('type', $req->type);
        }

        if ($req->filled('month')) {
            $month = (string) $req->month;

            if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                // Format YYYY-MM
                [$y, $m] = explode('-', $month);
                $start = sprintf('%04d-%02d-01', $y, $m);
                $end   = date('Y-m-d', strtotime("$start +1 month"));

                // Rentang setengah terbuka: [start, end)
                $query->where('month', '>=', $start)
                      ->where('month', '<',  $end);
            } elseif (preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $month)) {
                // Format YYYY-MM-DD
                $query->whereDate('month', $month);
            }
        }

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        } else {
            if ($req->filled('user_id')) {
                $query->where('user_id', $req->user_id);
            }
        }

        return $query->orderBy('month', 'desc')
                     ->orderBy('id', 'desc')
                     ->paginate(20);
    }

    public function store(StoreSavingRequest $req)
    {

        $saving = Saving::create($req->validated());
        return response()->json($saving, 201);
    }

    public function update(UpdateSavingRequest $req, Saving $saving): Saving
    {
        $saving->update($req->validated());
        return $saving;
    }

   
    public function destroy(Request $req, Saving $saving)
    {
        $user = $req->user();
        if ($user->role !== 'admin' && (int)$saving->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $saving->delete();
        return response()->json(['message' => 'deleted']);
    }
    public function summary(Request $req)
{
    $user = $req->user();
    $query = Saving::query();

    if ($user->role !== 'admin') {
        $query->where('user_id', $user->id);
    } elseif ($req->filled('user_id')) {
        $query->where('user_id', $req->user_id);
    }

    if ($req->filled('year')) {
        $query->whereYear('month', $req->year);
    }

    $totals = $query->selectRaw("
        SUM(CASE WHEN type = 'wajib' THEN amount ELSE 0 END) as total_wajib,
        SUM(CASE WHEN type = 'pokok' THEN amount ELSE 0 END) as total_pokok,
        SUM(CASE WHEN type = 'sukarela' THEN amount ELSE 0 END) as total_sukarela
    ")->first();

    return response()->json($totals);
}

}