<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun awal. GANTI password ini sebelum aplikasi dipakai sungguhan.
        User::create(['name' => 'Pemilik', 'username' => 'admin',  'password' => 'password', 'role' => 'admin']);
        User::create(['name' => 'Kasir 1', 'username' => 'kasir1', 'password' => 'password', 'role' => 'kasir']);

        // Profil usaha (diisi developer; sesuaikan dengan data asli warung)
        Setting::create([
            'business_name'  => 'Warung Djaniman',
            'address'        => '[alamat warung]',
            'phone'          => '[nomor telepon]',
            'opening_hours'  => 'Setiap hari, 08.00 - 21.00',
            'about'          => 'Tulis 2-3 kalimat tentang warung di sini.',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda',
        ]);

        // Data contoh: kategori => [nama produk => harga]
        $menu = [
            'Makanan' => ['Nasi Goreng' => 18000, 'Mie Goreng' => 16000, 'Ayam Geprek' => 20000, 'Soto Ayam' => 17000],
            'Minuman' => ['Es Teh' => 5000, 'Kopi Hitam' => 6000, 'Es Jeruk' => 7000],
            'Camilan' => ['Pisang Goreng' => 8000, 'Tahu Crispy' => 7000],
        ];

        foreach ($menu as $namaKategori => $produk) {
            $kategori = Category::create(['name' => $namaKategori]);

            foreach ($produk as $nama => $harga) {
                Product::create([
                    'category_id' => $kategori->id,
                    'name'        => $nama,
                    'price'       => $harga,
                ]);
            }
        }
    }
}
