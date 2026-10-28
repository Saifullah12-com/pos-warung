<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function payload(array $o = []): array
    {
        return $o + [
            'spent_at' => now()->subMinute()->format('Y-m-d\TH:i'),
            'category' => 'Belanja bahan',
            'supplier' => 'Pasar Condongcatur',
            'items'    => [
                ['name' => 'beras', 'qty' => '25', 'unit' => 'kg', 'unit_price' => '14000'],
                ['name' => 'Telur ayam', 'qty' => '1.5', 'unit' => 'kg', 'unit_price' => '28000'],
            ],
        ];
    }

    public function test_total_dihitung_server_dan_nama_dirapikan(): void
    {
        $admin = User::factory()->admin()->create();

        // 'amount' yang dikirim browser harus diabaikan
        $this->actingAs($admin)->post(route('expenses.store'), $this->payload(['amount' => 1]))->assertRedirect();

        $e = Expense::with('items')->first();
        $this->assertEquals(392000, $e->amount);                // 350.000 + 42.000
        $this->assertCount(2, $e->items);
        $this->assertSame('Beras', $e->items[0]->name);
        $this->assertEquals(350000, $e->items[0]->subtotal);
        $this->assertEquals(42000, $e->items[1]->subtotal);
    }

    public function test_kasir_tidak_bisa_mengakses_pengeluaran(): void
    {
        $kasir = User::factory()->create();

        $this->actingAs($kasir)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('expenses.create'))->assertForbidden();
        $this->actingAs($kasir)->post(route('expenses.store'), $this->payload())->assertForbidden();
        $this->assertSame(0, Expense::count());
    }

    public function test_validasi_menolak_data_yang_salah(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('expenses.store'), $this->payload(['items' => []]))->assertSessionHasErrors('items');
        $this->actingAs($admin)->post(route('expenses.store'), $this->payload(['category' => 'Ngawur']))->assertSessionHasErrors('category');
        $this->actingAs($admin)->post(route('expenses.store'), $this->payload(['spent_at' => now()->addDay()->format('Y-m-d\TH:i')]))->assertSessionHasErrors('spent_at');
        $this->actingAs($admin)->post(route('expenses.store'), $this->payload([
            'items' => [['name' => 'Beras', 'qty' => '0', 'unit' => 'kg', 'unit_price' => '14000']],
        ]))->assertSessionHasErrors('items.0.qty');

        $this->assertSame(0, Expense::count());
    }

    public function test_pembatalan_hanya_admin_dan_tidak_dua_kali(): void
    {
        $kasir = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $e = app(ExpenseService::class)->create($admin, $this->payload());

        $this->actingAs($kasir)->post(route('expenses.cancel', $e), ['reason' => 'Coba batal'])->assertForbidden();
        $this->assertSame('aktif', $e->fresh()->status);

        $this->actingAs($admin)->post(route('expenses.cancel', $e), ['reason' => 'Salah catat'])->assertRedirect();
        $this->assertSame('batal', $e->fresh()->status);

        $this->actingAs($admin)->post(route('expenses.cancel', $e), ['reason' => 'Lagi'])->assertSessionHasErrors('reason');
    }

    public function test_laporan_tidak_menghitung_pembelian_batal(): void
    {
        $admin = User::factory()->admin()->create();
        $svc   = app(ExpenseService::class);

        $svc->create($admin, $this->payload());                                    // 392.000
        $b = $svc->create($admin, $this->payload(['items' => [
            ['name' => 'Gula', 'qty' => '1', 'unit' => 'kg', 'unit_price' => '20000'],
        ]]));
        $svc->cancel($b, $admin, 'Salah catat');

        $res = $this->actingAs($admin)->get(route('reports.index'))->assertOk();

        $this->assertSame(392000, $res->viewData('totalExpense'));
        $this->assertSame(-392000, $res->viewData('netCash'));
    }

    public function test_daftar_menampilkan_ringkasan_barang(): void
    {
        $admin = User::factory()->admin()->create();
        app(ExpenseService::class)->create($admin, $this->payload());

        $this->actingAs($admin)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Beras 25 kg');

        $this->actingAs($admin)->get(route('expenses.create'))->assertOk();
    }
}
