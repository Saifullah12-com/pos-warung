<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;
    private User $kasir;
    private Product $nasi;
    private Product $teh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OrderService();
        $this->kasir   = User::factory()->create();

        $cat        = Category::create(['name' => 'Makanan']);
        $this->nasi = Product::create(['category_id' => $cat->id, 'name' => 'Nasi Goreng', 'price' => 18000]);
        $this->teh  = Product::create(['category_id' => $cat->id, 'name' => 'Es Teh', 'price' => 5000]);
    }

    private function bayar(array $items, string $method = 'tunai', ?int $received = 100000): Order
    {
        return $this->service->create($this->kasir, [
            'order_type'      => 'dine_in',
            'items'           => $items,
            'method'          => $method,
            'amount_received' => $received,
        ]);
    }

    public function test_total_dan_kembalian_dihitung_di_server(): void
    {
        $order = $this->bayar([$this->nasi->id => 2, $this->teh->id => 1], 'tunai', 50000);

        $this->assertSame(41000, $order->total);               // 2x18.000 + 5.000
        $this->assertSame(9000, $order->payment->change_amount);
        $this->assertCount(2, $order->items);
    }

    public function test_riwayat_menyimpan_salinan_harga_saat_transaksi(): void
    {
        $order = $this->bayar([$this->nasi->id => 1]);
        $this->nasi->update(['price' => 99000]);

        $this->assertEquals(18000, $order->items()->first()->unit_price);
    }

    public function test_nomor_antrian_bertambah_lalu_reset_besok(): void
    {
        $this->assertSame(1, $this->bayar([$this->teh->id => 1])->queue_no);
        $this->assertSame(2, $this->bayar([$this->teh->id => 1])->queue_no);

        $this->travelTo(now()->addDay());
        $this->assertSame(1, $this->bayar([$this->teh->id => 1])->queue_no);
    }

    public function test_uang_kurang_ditolak_dan_tidak_menyimpan_apa_pun(): void
    {
        try {
            $this->bayar([$this->nasi->id => 1], 'tunai', 10000);
            $this->fail('Seharusnya ditolak.');
        } catch (ValidationException) {
            $this->assertSame(0, Order::count());
        }
    }

    public function test_produk_habis_ditolak(): void
    {
        $this->teh->update(['is_available' => false]);

        $this->expectException(ValidationException::class);
        $this->bayar([$this->teh->id => 1]);
    }

    public function test_qris_dianggap_pas_dan_laporan_tidak_menghitung_batal(): void
    {
        $a = $this->bayar([$this->nasi->id => 1], 'qris', null);
        $b = $this->bayar([$this->teh->id => 1]);

        $this->assertSame(0, $a->payment->change_amount);

        $admin = User::factory()->admin()->create();
        $this->service->cancel($b, $admin, 'Salah pesan');

        $this->assertSame(18000, (int) Order::completed()->sum('total'));
        $this->assertSame('batal', $b->fresh()->status);
        $this->assertSame('Salah pesan', $b->fresh()->cancel_reason);
    }

    public function test_transaksi_tidak_bisa_dibatalkan_dua_kali(): void
    {
        $order = $this->bayar([$this->teh->id => 1]);
        $admin = User::factory()->admin()->create();
        $this->service->cancel($order, $admin, 'Pertama');

        $this->expectException(ValidationException::class);
        $this->service->cancel($order, $admin, 'Kedua');
    }
    private function transaksi(array $items, string $method, ?Customer $customer, ?int $received = null): Order
    {
        return $this->service->create($this->kasir, [
            'order_type'      => 'dine_in',
            'items'           => $items,
            'method'          => $method,
            'amount_received' => $received,
            'customer_id'     => $customer?->id,
        ]);
    }

    public function test_hutang_penuh_menambah_saldo_pelanggan(): void
    {
        $budi  = Customer::create(['name' => 'Budi']);
        $order = $this->transaksi([$this->nasi->id => 1], 'hutang', $budi);

        $this->assertEquals(18000, $order->payment->debt_amount);
        $this->assertEquals(0, $order->payment->amount_received);
        $this->assertSame(18000, $budi->balance());
    }

    public function test_bayar_sebagian_sisanya_jadi_hutang(): void
    {
        $budi  = Customer::create(['name' => 'Budi']);
        $order = $this->transaksi([$this->nasi->id => 1], 'tunai', $budi, 10000);

        $this->assertEquals(8000, $order->payment->debt_amount);
        $this->assertEquals(0, $order->payment->change_amount);
        $this->assertSame(8000, $budi->balance());
    }

    public function test_hutang_berikutnya_dihitung_dari_hutang_sebelumnya(): void
    {
        $budi = Customer::create(['name' => 'Budi']);

        $this->transaksi([$this->nasi->id => 1], 'hutang', $budi);   // 18.000
        $this->transaksi([$this->teh->id => 2], 'hutang', $budi);    // 10.000

        $this->assertSame(28000, $budi->balance());
    }

    public function test_hutang_tanpa_pelanggan_ditolak(): void
    {
        $this->expectException(ValidationException::class);
        $this->transaksi([$this->nasi->id => 1], 'hutang', null);
    }

    public function test_membatalkan_transaksi_hutang_mengurangi_saldo(): void
    {
        $budi  = Customer::create(['name' => 'Budi']);
        $admin = User::factory()->admin()->create();
        $order = $this->transaksi([$this->nasi->id => 1], 'hutang', $budi);

        $this->service->cancel($order, $admin, 'Salah catat');

        $this->assertSame(0, $budi->balance());
    }
}
