<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // halaman memakai @vite; di test tidak perlu build aset
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/kasir')->assertRedirect(route('login'));
    }

    public function test_login_dengan_username_berhasil(): void
    {
        $user = User::factory()->create(['username' => 'rina']);

        $this->post('/login', ['username' => 'rina', 'password' => 'password'])
            ->assertRedirect(route('pos.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        User::factory()->inactive()->create(['username' => 'lama']);

        $this->post('/login', ['username' => 'lama', 'password' => 'password'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_kasir_tidak_bisa_membuka_halaman_admin(): void
    {
        $kasir = User::factory()->create();

        foreach (['/laporan', '/products', '/categories', '/akun'] as $url) {
            $this->actingAs($kasir)->get($url)->assertForbidden();
        }
    }

    public function test_admin_bisa_membuka_halaman_admin(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['/laporan', '/products', '/categories', '/akun', '/riwayat', '/kasir'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_hanya_admin_yang_bisa_membatalkan_transaksi(): void
    {
        $kasir = User::factory()->create();
        $cat   = Category::create(['name' => 'Minuman']);
        $teh   = Product::create(['category_id' => $cat->id, 'name' => 'Es Teh', 'price' => 5000]);

        $order = app(OrderService::class)->create($kasir, [
            'order_type' => 'takeaway',
            'items' => [$teh->id => 1],
            'method' => 'qris',
        ]);

        $this->actingAs($kasir)->post(route('orders.cancel', $order), ['reason' => 'Coba batal'])->assertForbidden();
        $this->assertSame('selesai', $order->fresh()->status);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('orders.cancel', $order), ['reason' => 'Pelanggan batal'])->assertRedirect();
        $this->assertSame('batal', $order->fresh()->status);
    }

    public function test_alasan_pembatalan_wajib(): void
    {
        $admin = User::factory()->admin()->create();
        $cat   = Category::create(['name' => 'Minuman']);
        $teh   = Product::create(['category_id' => $cat->id, 'name' => 'Es Teh', 'price' => 5000]);
        $order = app(OrderService::class)->create($admin, [
            'order_type' => 'takeaway',
            'items' => [$teh->id => 1],
            'method' => 'qris',
        ]);

        $this->actingAs($admin)->post(route('orders.cancel', $order), ['reason' => ''])->assertSessionHasErrors('reason');
    }

    public function test_kasir_bisa_membuat_transaksi_lewat_halaman_kasir(): void
    {
        $kasir = User::factory()->create();
        $cat   = Category::create(['name' => 'Minuman']);
        $teh   = Product::create(['category_id' => $cat->id, 'name' => 'Es Teh', 'price' => 5000]);

        $this->actingAs($kasir)->post('/kasir', [
            'order_type' => 'dine_in',
            'items' => [$teh->id => 2],
            'method' => 'tunai',
            'amount_received' => 20000,
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['total' => 10000, 'queue_no' => 1]);
        $this->assertDatabaseHas('payments', ['change_amount' => 10000]);
    }
    public function test_kasir_menambah_pelanggan_dan_mencatat_hutang(): void
    {
        $kasir = User::factory()->create();
        $cat   = Category::create(['name' => 'Minuman']);
        $teh   = Product::create(['category_id' => $cat->id, 'name' => 'Es Teh', 'price' => 5000]);

        $res = $this->actingAs($kasir)->postJson('/pelanggan', ['name' => 'Budi', 'phone' => '0812']);
        $res->assertCreated()->assertJsonPath('balance', 0);
        $customerId = $res->json('id');

        $this->actingAs($kasir)->post('/kasir', [
            'order_type' => 'dine_in',
            'items' => [$teh->id => 2],
            'method' => 'hutang',
            'customer_id' => $customerId,
        ])->assertRedirect();

        $this->assertDatabaseHas('debt_entries', ['customer_id' => $customerId, 'type' => 'hutang', 'amount' => 10000]);
    }
    public function test_halaman_awal_menampilkan_menu_produk_aktif(): void
    {
        $cat = Category::create(['name' => 'Minuman']);
        Product::create(['category_id' => $cat->id, 'name' => 'Es Teh', 'price' => 5000]);
        Product::create(['category_id' => $cat->id, 'name' => 'Produk Nonaktif', 'price' => 9000, 'is_active' => false]);

        $this->get('/')->assertOk()->assertSee('Es Teh')->assertDontSee('Produk Nonaktif');
    }
}
