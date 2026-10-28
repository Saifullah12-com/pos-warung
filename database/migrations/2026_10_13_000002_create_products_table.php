<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('price');            // rupiah utuh, bukan desimal
            $table->string('photo_path')->nullable();
            $table->boolean('is_available')->default(true); // false = "Habis"
            $table->boolean('is_active')->default(true);    // false = disembunyikan (pengganti hapus)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
