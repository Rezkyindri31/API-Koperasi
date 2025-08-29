<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('loans', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->decimal('amount', 14, 2);
        $table->dateTime('submitted_at');
        $table->enum('status', ['applied','approved','rejected','canceled', 'paid'])->default('applied');
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->dateTime('approved_at')->nullable();
        $table->string('phone_snapshot')->nullable();
        $table->string('address_snapshot')->nullable();
        $table->timestamps();

        $table->index(['user_id','status']);
        $table->index(['status','submitted_at']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};