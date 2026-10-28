<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel lama (satu angka per catatan) hanya berisi data uji, jadi dibuang dan dibuat ulang
        Schema::dropIfExists('expenses');

        // Satu baris = satu kali belanja
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->dateTime('spent_at');                       // tanggal dan waktu pembelian
            $table->string('category', 50);                     // jenis: Belanja bahan, Gaji, dan seterusnya
            $table->string('supplier', 100)->nullable();        // toko atau pemasok
            $table->unsignedInteger('amount');                  // TOTAL pembelian (jumlah semua subtotal)
            $table->string('receipt_path')->nullable();         // foto nota
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // Admin yang mencatat
            $table->enum('status', ['aktif', 'batal'])->default('aktif');

            $table->text('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->index('spent_at');
        });

        // Satu baris = satu barang di dalam belanja itu
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('qty', 10, 2);                      // boleh pecahan, misalnya 1,5 kg
            $table->string('unit', 20);
            $table->unsignedInteger('unit_price');              // harga per satuan, rupiah utuh
            $table->unsignedInteger('subtotal');                // qty x harga, dihitung server
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
        Schema::dropIfExists('expenses');
    }
};
