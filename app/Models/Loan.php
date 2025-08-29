<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Loan extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'amount',
        'submitted_at',
        'status',
        'approved_by',
        'approved_at',
        'phone_snapshot',
        'address_snapshot',
    ];

    /**
     * Cast kolom ke tipe data tertentu
     */
    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
        'amount'       => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }
}