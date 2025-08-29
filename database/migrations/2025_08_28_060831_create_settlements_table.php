<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->dateTime('paid_at');                 // di request kita default-now
            $table->decimal('amount', 14, 2)->nullable(); // opsional
            $table->string('proof_path');                 // bukti wajib

            // ENUM settlement (bukan enum loan)
            $table->enum('status', ['submitted','approved','rejected'])->default('submitted');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['loan_id','status']);
            $table->index(['user_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};