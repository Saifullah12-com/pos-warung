<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\DebtReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;
    private Customer $budi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->kasir = User::factory()->create();
        $this->budi  = Customer::create(['name' => 'Budi']);
    }

    private function catat(string $type, int $amount): void
    {
        $this->budi->debtEntries()->create(['type' => $type, 'amount' => $amount, 'user_id' => $this->kasir->id]);
    }

    public function test_rincian_menghitung_saldo_awal_dan_akhir_per_periode(): void
    {
        $this->travelTo(Carbon::create(2026, 1, 10, 9));
        $this->catat('hutang', 30000);

        $this->travelTo(Carbon::create(2026, 1, 20, 9));
        $this->catat('bayar', 10000);

        $data = app(DebtReportService::class)->statement($this->budi, Carbon::create(2026, 1, 15));

        $this->assertSame(30000, $data['opening']);
        $this->assertSame(10000, $data['totals']['bayar']);
        $this->assertSame(20000, $data['closing']);
        $this->assertCount(1, $data['rows']);
    }

    public function test_kasir_bisa_mencetak_rincian_pelanggan_sebagai_pdf(): void
    {
        $this->catat('hutang', 18000);

        $this->actingAs($this->kasir)
            ->get(route('customers.debt-pdf', $this->budi))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_daftar_piutang_hanya_untuk_admin(): void
    {
        $this->catat('hutang', 18000);

        $this->actingAs($this->kasir)->get(route('reports.receivables-pdf'))->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get(route('reports.receivables-pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
