<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    /**
     * GET /loans
     */
    public function index(Request $req)
    {
        $this->authorize('viewAny', Loan::class);

        $user = $req->user();
        $q = Loan::with(['user', 'approvedBy']);

        if ($user->role !== 'admin') {
            $q->where('user_id', $user->id);
        }

        // Optional filter: status (guard dengan enum)
        if ($req->filled('status')) {
            $status = $req->query('status');
            $allowed = ['applied', 'approved', 'rejected', 'paid'];
            abort_unless(in_array($status, $allowed, true), 422, 'Invalid status filter');
            $q->where('status', $status);
        }

        return $q->orderBy('submitted_at', 'desc')->paginate(20);
    }

    /**
     * GET /loans/{loan}
     */
    public function show(Loan $loan)
    {
        $this->authorize('view', $loan);
        return $loan->load(['user', 'approvedBy', 'settlements']);
    }

    /**
     * POST /loans
     * Catatan: Saat ini HANYA karyawan (non-admin) yang boleh create, sesuai kode awalmu.
     * Jika nanti admin juga boleh membuatkan untuk user lain, tinggal longgarkan logika di sini.
     */
    public function store(StoreLoanRequest $req)
    {
        $this->authorize('create', Loan::class);

        $user = $req->user();

        // hanya karyawan (bukan admin)
        abort_unless($user->role !== 'admin', 403, 'Only employees can create loans');

        $data = $req->validated();

        $loan = Loan::create([
            'user_id'          => $user->id,
            'amount'           => $data['amount'],
            'submitted_at'     => $data['submitted_at'],
            'status'           => 'applied',
            'phone_snapshot'   => $data['phone_snapshot'],
            'address_snapshot' => $data['address_snapshot'],
        ]);

        return response()->json($loan->load('user'), 201);
    }

    /**
     * PUT/PATCH /loans/{loan}
     * Contoh: admin mengubah amount/submitted_at/approval/status, user biasa hanya boleh update field non-sensitif (namun kita ban di Request).
     */
    public function update(UpdateLoanRequest $req, Loan $loan)
    {
        $this->authorize('update', $loan);

        $loan->update($req->validated());

        return $loan->fresh()->load(['user', 'approvedBy']);
    }

    /**
     * POST /loans/{loan}/approve
     */
    public function approve(Loan $loan, Request $req)
    {
        $this->authorize('approve', $loan);

        // Hanya bisa approve dari status applied
        abort_unless($loan->status === 'applied', 422, 'Only applied loan can be approved');

        $loan->update([
            'status'      => 'approved',
            'approved_by' => $req->user()->id,
            'approved_at' => now(),
        ]);

        return $loan->fresh()->load(['user', 'approvedBy']);
    }

    /**
     * POST /loans/{loan}/reject
     */
    public function reject(Loan $loan)
    {
        $this->authorize('approve', $loan);

        abort_unless($loan->status === 'applied', 422, 'Only applied loan can be rejected');

        $loan->update([
            'status'      => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return $loan->fresh()->load(['user']);
    }

    
    public function cancel(Loan $loan, Request $req)
    {
        abort_unless($loan->user_id === $req->user()->id, 403, 'You can only cancel your own loan');

        abort_unless($loan->status === 'applied', 422, 'Only applied loan can be canceled');

        $loan->update([
            'status'      => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return $loan->fresh();
    }
}