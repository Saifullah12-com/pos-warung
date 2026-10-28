<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from  = Carbon::parse($request->query('from', today()->toDateString()))->startOfDay();
        $to    = Carbon::parse($request->query('to', $from->toDateString()))->endOfDay();
        $range = [$from, $to];

        $purchases = Purchase::with(['user', 'items'])
            ->whereBetween('purchased_at', $range)
            ->orderByDesc('purchased_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $active    = Purchase::active()->whereBetween('purchased_at', $range);
        $total     = (int) (clone $active)->sum('total');
        $count     = (clone $active)->count();
        $cancelled = Purchase::where('status', Purchase::STATUS_BATAL)->whereBetween('purchased_at', $range)->count();

        return view('purchases.index', compact('purchases', 'from', 'to', 'total', 'count', 'cancelled'));
    }

    public function create()
    {
        // Saran isian dari pembelian sebelumnya, supaya penulisan nama dan satuan konsisten
        $names = PurchaseItem::select('name')->distinct()->orderBy('name')->limit(300)->pluck('name');
        $units = PurchaseItem::select('unit')->distinct()->pluck('unit')
            ->merge(['kg', 'gram', 'liter', 'ml', 'butir', 'bungkus', 'pak', 'karung', 'botol', 'tabung', 'buah', 'bulan'])
            ->unique()->values();
        $suppliers = Purchase::whereNotNull('supplier')->select('supplier')->distinct()->orderBy('supplier')->limit(100)->pluck('supplier');

        // Setelah gagal validasi, baris yang sudah diisi dikembalikan
        $rows = array_values(old('items', [[]]));

        return view('purchases.create', compact('names', 'units', 'suppliers', 'rows'));
    }

    public function store(Request $request, PurchaseService $service)
    {
        $data = $request->validate([
            'type'                 => ['required', Rule::in(Purchase::TYPES)],
            'purchased_at'         => ['nullable', 'date', 'before_or_equal:now'],
            'supplier'             => ['nullable', 'string', 'max:100'],
            'receipt'              => ['nullable', 'image', 'max:2048'],
            'items'                => ['required', 'array', 'min:1', 'max:50'],
            'items.*.name'         => ['required', 'string', 'max:100'],
            'items.*.qty'          => ['required', 'numeric', 'min:0.001', 'max:100000'],
            'items.*.unit'         => ['required', 'string', 'max:20'],
            'items.*.unit_price'   => ['required', 'integer', 'min:1', 'max:100000000'],
        ], [
            'type.required'                 => 'Pilih jenis pengeluaran.',
            'type.in'                       => 'Jenis tidak valid.',
            'purchased_at.date'             => 'Tanggal dan waktu tidak valid.',
            'purchased_at.before_or_equal'  => 'Waktu pembelian tidak boleh di masa depan.',
            'receipt.image'                 => 'Nota harus berupa gambar.',
            'receipt.max'                   => 'Ukuran foto nota maksimal 2 MB.',
            'items.required'                => 'Tambahkan minimal satu barang.',
            'items.min'                     => 'Tambahkan minimal satu barang.',
            'items.*.name.required'         => 'Nama barang wajib diisi.',
            'items.*.qty.required'          => 'Jumlah barang wajib diisi.',
            'items.*.qty.numeric'           => 'Jumlah harus berupa angka.',
            'items.*.qty.min'               => 'Jumlah harus lebih dari 0.',
            'items.*.unit.required'         => 'Satuan wajib diisi.',
            'items.*.unit_price.required'   => 'Harga per satuan wajib diisi.',
            'items.*.unit_price.integer'    => 'Harga harus berupa angka bulat (rupiah).',
            'items.*.unit_price.min'        => 'Harga minimal Rp1.',
        ]);

        $purchase = $service->create($request->user(), $data, $request->file('receipt'));

        return redirect()->route('purchases.show', $purchase)->with('success', 'Pembelian dicatat.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['items', 'user', 'canceller']);

        return view('purchases.show', compact('purchase'));
    }

    public function cancel(Request $request, Purchase $purchase, PurchaseService $service)
    {
        $data = $request->validate(
            ['reason' => ['required', 'string', 'min:3', 'max:255']],
            ['reason.required' => 'Alasan pembatalan wajib diisi.', 'reason.min' => 'Alasan terlalu singkat.']
        );

        $service->cancel($purchase, $request->user(), $data['reason']);

        return back()->with('success', 'Pembelian dibatalkan.');
    }
}
