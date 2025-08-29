<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Settlement;
use App\Http\Requests\StoreSettlementRequest;
use App\Http\Requests\UpdateSettlementRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettlementController extends Controller
{
    /**
     * GET /settlements
     * - Admin: lihat semua
     * - Karyawan: hanya miliknya
     * - Filter opsional: status (submitted|approved|rejected), loan_id, user_id (admin), limit (non-paginated)
     */
    public function index(Request $req)
    {
        $this->authorize('viewAny', Settlement::class);

        $user = $req->user();
        $q = Settlement::with(['loan.user', 'user', 'reviewedBy']);

        if ($user->role !== 'admin') {
            $q->where('user_id', $user->id);
        }

        if ($req->filled('status')) {
            $allowed = ['submitted', 'approved', 'rejected'];
            abort_unless(in_array($req->status, $allowed, true), 422, 'Invalid status filter.');
            $q->where('status', $req->status);
        }

        if ($req->filled('loan_id')) {
            $q->where('loan_id', $req->loan_id);
        }

        if ($user->role === 'admin' && $req->filled('user_id')) {
            $q->where('user_id', $req->user_id);
        }

        $q->orderByDesc('paid_at')->orderByDesc('created_at');

        // Non-paginated (limit) path
        if ($req->filled('limit') && !$req->filled('page')) {
            $limit = max(1, min((int) $req->input('limit', 20), 100));
            return $q->limit($limit)->get();
        }

        return $q->paginate(20);
    }

    /**
     * POST /settlements
     * Karyawan upload bukti untuk loan miliknya yang sudah approved (bukan paid).
     * Settlements: status = submitted.
     */
    public function store(StoreSettlementRequest $req)
    {
        $this->authorize('create', Settlement::class);

        $user = $req->user();
        $data = $req->validated();

        $loan = Loan::with('user')->findOrFail($data['loan_id']);

        // pemilik saja
        abort_if((int) $loan->user_id !== (int) $user->id, 403, 'Not your loan.');

        // wajib approved & belum paid
        abort_unless($loan->status === 'approved', 422, 'Loan must be approved.');
        abort_if($loan->status === 'paid', 422, 'Loan already paid.');

        // block pending submitted ganda
        $hasPending = Settlement::where('loan_id', $loan->id)
            ->where('status', 'submitted')
            ->exists();
        abort_if($hasPending, 422, 'A settlement is already submitted and pending review.');

        // simpan file bukti
        $path = $req->file('proof')->store("settlement_proofs/loan_{$loan->id}", 'public');

        $settlement = Settlement::create([
            'loan_id'     => $loan->id,
            'user_id'     => $user->id,
            'paid_at'     => $data['paid_at'] ?? now(),
            'amount'      => $data['amount'] ?? null,
            'proof_path'  => $path,
            'status'      => 'submitted',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        return response()->json($settlement->load(['loan.user', 'user']), 201);
    }

    /**
     * POST /settlements/{settlement}/approve
     * Admin menyetujui settlement → settlement.approved & loan.paid
     */
    public function approve(Settlement $settlement, Request $req)
    {
        $this->authorize('approve', $settlement);

        abort_unless($settlement->status === 'submitted', 422, 'Only submitted settlement can be approved.');

        $loan = $settlement->loan()->firstOrFail();

        // loan harus approved & belum paid
        abort_unless($loan->status === 'approved', 422, 'Approve the loan first.');
        abort_if($loan->status === 'paid', 422, 'Loan already paid.');

        DB::transaction(function () use ($settlement, $loan, $req) {
            $settlement->update([
                'status'      => 'approved',
                'reviewed_by' => $req->user()->id,
                'reviewed_at' => now(),
            ]);

            $loan->update(['status' => 'paid']);
        });

        return $settlement->fresh()->load(['loan.user', 'reviewedBy']);
    }

    /**
     * POST /settlements/{settlement}/reject
     * Admin menolak settlement → settlement.rejected (loan tetap approved)
     */
    public function reject(Settlement $settlement, Request $req)
    {
        $this->authorize('approve', $settlement);

        abort_unless($settlement->status === 'submitted', 422, 'Only submitted settlement can be rejected.');

        $settlement->update([
            'status'      => 'rejected',
            'reviewed_by' => $req->user()->id,
            'reviewed_at' => now(),
        ]);

        return $settlement->fresh()->load(['loan.user', 'reviewedBy']);
    }

    public function proof(Settlement $settlement, Request $req): StreamedResponse
{
    $this->authorize('view', $settlement);

    $path = $settlement->proof_path;
    abort_unless($path, 404, 'No proof uploaded.');

    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk('public');

    // pastikan file ada sebelum akses mime/stream
    abort_unless($disk->exists($path), 404, 'File not found.');

    $filename    = basename($path);
    $mime        = $disk->mimeType($path) ?: 'application/octet-stream';
    $disposition = $req->boolean('download') ? 'attachment' : 'inline';

    $stream = $disk->readStream($path);

    return response()->stream(function () use ($stream) {
        fpassthru($stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }, 200, [
        'Content-Type'              => $mime,
        'Content-Disposition'       => $disposition . '; filename="' . $filename . '"',
        'Cache-Control'             => 'private, max-age=0, no-store, must-revalidate',
        'X-Content-Type-Options'    => 'nosniff',
    ]);
}

}