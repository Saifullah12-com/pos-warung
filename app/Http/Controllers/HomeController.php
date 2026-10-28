<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Yang sudah login langsung ke halaman kasir
        if (Auth::check()) {
            return redirect()->route('pos.index');
        }

        // Hanya kategori dan produk yang aktif, dan kategori yang tidak kosong
        $categories = Category::where('is_active', true)
            ->with(['products' => fn($q) => $q->active()->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->filter(fn($c) => $c->products->isNotEmpty())
            ->values();

        return view('welcome', [
            'setting'    => Setting::current(),
            'categories' => $categories,
            'featured'   => $categories->flatMap(fn($c) => $c->products)->where('is_available', true)->take(3)->values(),
            'showPrice'  => true, // ubah ke false untuk menyembunyikan harga di halaman publik
        ]);
    }
}
