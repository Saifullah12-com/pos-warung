<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\DebtService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $nasi;
    private Product $teh;
    private Customer $budi;
    private OrderService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->admin = User::factory()->admin()->create();
        $cat         = Category::create(['name' => 'Makanan']);
        $this->nasi  = Product::create(['category_id' => $cat->id, 'name' => 'Nasi Goreng', 'price' => 18000]);
        $this->teh   = Product::create(['category_id' => $cat->id, 'name' => 'Es Teh', 'price' => 5000]);
        $this->budi  = Customer::create(['name' => 'Budi']);
        $this->svc   = app(OrderService::class);
    }

    private function buatTransaksi(): Order
    {
        // A: hutang penuh 18.000
        $a = $this->svc->create($this->admin, [
            'order_type' => 'dine_in',
            'items' => [$this->nasi->id => 1],
            'method' => 'hutang',
            'customer_id' => $this->budi->id,
        ]);

        // B: tunai lunas 5.000 (uang 20.000, kembali 15.000)
        $this->svc->create($this->admin, [
            'order_type' => 'dine_in',
            'items' => [$this->teh->id => 1],
            'method' => 'tunai',
            'amount_received' => 20000,
        ]);

        // C: bayar sebagian, 10.000 tunai, sisa 8.000 jadi hutang
        $this->svc->create($this->admin, [
            'order_type' => 'takeaway',
            'items' => [$this->nasi->id => 1],
            'method' => 'tunai',
            'amount_received' => 10000,
            'customer_id' => $this->budi->id,
        ]);

        // Pelunasan hutang Budi 6.000 lewat QRIS
        app(DebtService::class)->pay($this->budi, $this->admin, 6000, 'qris');

        return $a;
    }

    public function test_laporan_memisahkan_penjualan_dan_uang_masuk(): void
    {
        $this->buatTransaksi();

        $res = $this->actingAs($this->admin)->get(route('reports.index'))->assertOk();

        $this->assertEquals(41000, $res->viewData('summary')->sales);   // 18.000 + 5.000 + 18.000
        $this->assertSame(26000, $res->viewData('newDebt'));            // 18.000 + 8.000
        $this->assertSame(21000, $res->viewData('totalIn'));            // tunai 5.000 + 10.000, QRIS pelunasan 6.000
        $this->assertSame(20000, $res->viewData('totalReceivable'));    // 26.000 - 6.000
    }

    public function test_penjualan_sama_dengan_uang_masuk_penjualan_ditambah_hutang_baru(): void
    {
        $this->buatTransaksi();

        $res = $this->actingAs($this->admin)->get(route('reports.index'));
        $in  = $res->viewData('moneyIn');

        $this->assertEquals(
            $res->viewData('summary')->sales,
            $in['tunai']['sales'] + $in['qris']['sales'] + $res->viewData('newDebt')
        );
    }

    public function test_transaksi_hutang_yang_dibatalkan_tidak_dihitung(): void
    {
        $a = $this->buatTransaksi();
        $this->svc->cancel($a, $this->admin, 'Salah catat');

        $res = $this->actingAs($this->admin)->get(route('reports.index'));

        $this->assertEquals(23000, $res->viewData('summary')->sales);
        $this->assertSame(8000, $res->viewData('newDebt'));
        $this->assertSame(2000, $res->viewData('totalReceivable'));     // 8.000 - 6.000
    }

    public function test_kasir_tidak_bisa_membuka_laporan(): void
    {
        $this->actingAs(User::factory()->create())->get(route('reports.index'))->assertForbidden();
    }
}
