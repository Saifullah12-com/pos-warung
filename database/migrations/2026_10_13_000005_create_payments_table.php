<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete(); // 1 pesanan = 1 pembayaran
            $table->enum('method', ['tunai', 'qris', 'hutang']);
            $table->unsignedInteger('amount_received');
            $table->unsignedInteger('change_amount')->default(0);
            $table->unsignedInteger('debt_amount')->default(0); // bagian yang dihutang
            $table->string('reference_no')->nullable();     // opsional, untuk QRIS
            $table->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
