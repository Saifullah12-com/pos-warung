<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\DebtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DebtTest extends TestCase
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
        $this->budi->debtEntries()->create(['type' => 'hutang', 'amount' => 30000, 'user_id' => $this->kasir->id]);
    }

    public function test_pelunasan_sebagian_mengurangi_saldo(): void
    {
        app(DebtService::class)->pay($this->budi, $this->kasir, 10000, 'tunai');

        $this->assertSame(20000, $this->budi->balance());
    }

    public function test_pelunasan_penuh_membuat_saldo_nol(): void
    {
        app(DebtService::class)->pay($this->budi, $this->kasir, 30000, 'qris');

        $this->assertSame(0, $this->budi->balance());
    }

    public function test_pelunasan_melebihi_hutang_ditolak(): void
    {
        $this->expectException(ValidationException::class);
        app(DebtService::class)->pay($this->budi, $this->kasir, 30001, 'tunai');
    }

    public function test_kasir_bisa_mencatat_pelunasan_lewat_halaman(): void
    {
        $this->actingAs($this->kasir)
            ->post(route('customers.pay', $this->budi), ['amount' => 12000, 'method' => 'tunai'])
            ->assertRedirect();

        $this->assertSame(18000, $this->budi->balance());
    }

    public function test_kasir_tidak_bisa_menonaktifkan_pelanggan(): void
    {
        $this->actingAs($this->kasir)->patch(route('customers.toggle', $this->budi))->assertForbidden();
        $this->assertTrue($this->budi->fresh()->is_active);
    }

    public function test_halaman_pelanggan_bisa_dibuka(): void
    {
        $this->actingAs($this->kasir)->get(route('customers.index'))->assertOk()->assertSee('Budi');
        $this->actingAs($this->kasir)->get(route('customers.show', $this->budi))->assertOk()->assertSee('Budi');
    }
}
