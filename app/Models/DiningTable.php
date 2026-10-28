<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DiningTable extends Model
{
    protected $fillable = ['name', 'token', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Setiap meja baru otomatis mendapat kode acak yang tidak bisa ditebak
        static::creating(function (DiningTable $table) {
            $table->token ??= Str::random(24);
        });
    }

    // Alamat yang dimasukkan ke dalam QR
    public function publicUrl(): string
    {
        return url('/m/' . $this->token);
    }

    // Ganti kode, misalnya bila QR lama sudah menyebar ke orang yang tidak seharusnya
    public function newToken(): void
    {
        $this->update(['token' => Str::random(24)]);
    }
}
