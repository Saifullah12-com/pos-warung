<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use App\Models\Setting;
use App\Services\QrCodeService;
use Illuminate\Http\Request;

class DiningTableController extends Controller
{
    public function index(QrCodeService $qr)
    {
        $tables = DiningTable::orderBy('id')->get()->map(fn($t) => [
            'table' => $t,
            'qr'    => $qr->svg($t->publicUrl(), 200),
        ]);

        return view('tables.index', compact('tables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:dining_tables,name'],
        ]);

        DiningTable::create($data);

        return back()->with('success', 'Meja ditambahkan.');
    }

    // Buat "Meja 1" sampai "Meja N" sekaligus; yang sudah ada dilewati
    public function bulk(Request $request)
    {
        $data = $request->validate(['count' => ['required', 'integer', 'min:1', 'max:50']]);

        $made = 0;
        for ($i = 1; $i <= $data['count']; $i++) {
            $table = DiningTable::firstOrCreate(['name' => 'Meja ' . $i]);
            $made += $table->wasRecentlyCreated ? 1 : 0;
        }

        return back()->with('success', "{$made} meja ditambahkan.");
    }

    public function toggle(DiningTable $table)
    {
        $table->update(['is_active' => ! $table->is_active]);

        return back()->with('success', 'Status meja diperbarui.');
    }

    public function regenerate(DiningTable $table)
    {
        $table->newToken();

        return back()->with('success', "Kode QR {$table->name} diganti. Cetak ulang QR-nya.");
    }

    public function print(QrCodeService $qr)
    {
        $cards = DiningTable::where('is_active', true)->orderBy('id')->get()
            ->map(fn($t) => ['name' => $t->name, 'qr' => $qr->svg($t->publicUrl(), 300)]);

        return view('tables.print', [
            'cards' => $cards,
            'brand' => Setting::current()?->business_name ?? config('app.name'),
        ]);
    }
}
