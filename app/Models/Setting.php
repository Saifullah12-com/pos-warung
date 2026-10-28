<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['business_name', 'logo_path', 'address', 'phone', 'opening_hours', 'about', 'receipt_footer'];

    // Hanya ada satu baris profil usaha; ambil lewat Setting::current()
    public static function current(): ?self
    {
        return static::first();
    }
}
