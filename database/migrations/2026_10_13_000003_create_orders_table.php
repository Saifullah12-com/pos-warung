<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();                 // TRX-0001, permanen
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // kasir pencatat
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete(); // pelanggan (diisi bila ada hutang)
            $table->enum('order_type', ['dine_in', 'takeaway']);
            $table->date('queue_date');                            // tanggal antrian
            $table->unsignedSmallInteger('queue_no');              // reset tiap hari
            $table->enum('status', ['selesai', 'batal'])->default('selesai');
            $table->unsignedInteger('total');

            // Data pembatalan (terisi hanya bila status = batal)
            $table->text('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Dua pesanan di hari yang sama tidak boleh punya nomor antrian sama
            $table->unique(['queue_date', 'queue_no']);
            $table->index('created_at'); // mempercepat laporan per tanggal
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
