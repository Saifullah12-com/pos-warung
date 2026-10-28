# Aplikasi Kasir (POS) Warung Djaniman

Aplikasi kasir berbasis website untuk satu outlet warung makan.
Dibuat dengan Laravel, MySQL, Blade, dan Tailwind CSS.

## Fitur

- Kasir: dine-in dan takeaway, nomor antrian harian, pembayaran tunai, QRIS manual, dan hutang (termasuk bayar sebagian)
- Struk untuk printer 58 mm
- Riwayat transaksi dan pembatalan oleh Admin (dengan alasan)
- Pelanggan, pencatatan hutang, pelunasan, dan cetak rincian hutang (PDF)
- Produk dan kategori (foto, status habis, nonaktif)
- Pengeluaran per pembelian (barang, jumlah, satuan, harga, total)
- Laporan penjualan, uang masuk, pengeluaran, kas bersih, dan piutang
- Meja dan kode QR untuk melihat menu
- Peran: Admin (pemilik) dan Kasir

## Kebutuhan

- PHP 8.2 atau lebih baru (ekstensi: mbstring, dom, xmlwriter)
- Composer
- MySQL
- Node.js dan npm

## Pemasangan di komputer lokal

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

Buat database kosong, lalu isi pengaturan database di file `.env`.

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Selama pengembangan, jalankan `npm run dev` sebagai pengganti `npm run build`.

## Akun awal

Dibuat oleh seeder. **Ganti password sebelum aplikasi dipakai sungguhan.**

| Peran | Username |
| ----- | -------- |
| Admin | `admin`  |
| Kasir | `kasir1` |

## Pengujian

```bash
php artisan test
```

## Peringatan penting

- **Jangan jalankan `php artisan migrate:fresh` di server yang sudah berisi data.** Perintah itu menghapus seluruh tabel. Di server, gunakan `php artisan migrate --force`.
- File `.env` berisi kunci dan kata sandi dan **tidak boleh** diunggah ke repo.
- Foto produk dan nota tersimpan di server (bukan di repo) dan harus dibackup terpisah, bersama database.

## Pengaturan produksi

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://alamat-domain-anda`
- Gunakan HTTPS
