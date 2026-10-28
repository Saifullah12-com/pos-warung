<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_menambah_meja_dan_kode_dibuat_otomatis(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('tables.store'), ['name' => 'Meja 1'])->assertRedirect();

        $table = DiningTable::first();
        $this->assertSame(24, strlen($table->token));
        $this->assertStringContainsString('/m/' . $table->token, $table->publicUrl());
    }

    public function test_buat_otomatis_tidak_menggandakan_meja(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('tables.bulk'), ['count' => 5]);
        $this->actingAs($admin)->post(route('tables.bulk'), ['count' => 5]);

        $this->assertSame(5, DiningTable::count());
        $this->assertSame(5, DiningTable::distinct('token')->count('token'));
    }

    public function test_ganti_kode_mengubah_token(): void
    {
        $admin = User::factory()->admin()->create();
        $table = DiningTable::create(['name' => 'Meja 1']);
        $old   = $table->token;

        $this->actingAs($admin)->patch(route('tables.regenerate', $table))->assertRedirect();

        $this->assertNotSame($old, $table->fresh()->token);
    }

    public function test_halaman_meja_dan_cetak_menampilkan_qr(): void
    {
        $admin = User::factory()->admin()->create();
        DiningTable::create(['name' => 'Meja 1']);

        $this->actingAs($admin)->get(route('tables.index'))->assertOk()->assertSee('<svg', false);
        $this->actingAs($admin)->get(route('tables.print'))->assertOk()->assertSee('MEJA 1');
    }

    public function test_kasir_tidak_bisa_mengelola_meja(): void
    {
        $kasir = User::factory()->create();

        $this->actingAs($kasir)->get(route('tables.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('tables.print'))->assertForbidden();
        $this->actingAs($kasir)->post(route('tables.store'), ['name' => 'X'])->assertForbidden();
    }
}
