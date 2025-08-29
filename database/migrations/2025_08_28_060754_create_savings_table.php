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
    Schema::create('savings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->enum('type', ['wajib','pokok']);
        $table->date('month'); // simpan tgl awal bulan, mis. 2025-08-01
        $table->decimal('amount', 14, 2);
        $table->timestamps();

        $table->unique(['user_id','type','month']);
        $table->index(['user_id','month']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings');
    }
};