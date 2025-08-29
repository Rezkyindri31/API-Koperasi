<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'user_id',
        'paid_at',
        'amount',
        'proof_path',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'paid_at'     => 'datetime',
        'reviewed_at' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    protected $appends = ['proof_url'];

    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_path ? Storage::url($this->proof_path) : null;
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}