<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            // hutang = menambah saldo; bayar dan batal = mengurangi saldo
            $table->enum('type', ['hutang', 'bayar', 'batal']);
            $table->unsignedInteger('amount');
            $table->enum('method', ['tunai', 'qris'])->nullable(); // metode saat pelanggan membayar hutang
            $table->string('note')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // yang mencatat
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_entries');
    }
};
