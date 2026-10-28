<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');                // SALINAN nama saat transaksi
            $table->unsignedInteger('unit_price');         // SALINAN harga saat transaksi
            $table->unsignedSmallInteger('qty');
            $table->unsignedInteger('subtotal');           // unit_price x qty
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
