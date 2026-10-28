<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Services\ExpenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        // Bawaan: dari awal bulan sampai hari ini
        $from = Carbon::parse($request->query('from', today()->startOfMonth()->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->query('to', today()->toDateString()))->endOfDay();

        $expenses = Expense::with(['items', 'user'])
            ->whereBetween('spent_at', [$from, $to])
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $total     = (int) Expense::active()->whereBetween('spent_at', [$from, $to])->sum('amount');
        $count     = Expense::active()->whereBetween('spent_at', [$from, $to])->count();
        $cancelled = Expense::where('status', Expense::STATUS_BATAL)->whereBetween('spent_at', [$from, $to])->count();

        return view('expenses.index', compact('expenses', 'from', 'to', 'total', 'count', 'cancelled'));
    }

    public function create()
    {
        return view('expenses.create', [
            'categories' => Expense::CATEGORIES,
            // Saran otomatis dari pembelian sebelumnya
            'names'      => ExpenseItem::select('name')->distinct()->orderBy('name')->limit(300)->pluck('name'),
            'units'      => ExpenseItem::select('unit')->distinct()->orderBy('unit')->limit(100)->pluck('unit'),
        ]);
    }

    public function store(StoreExpenseRequest $request, ExpenseService $service)
    {
        $expense = $service->create($request->user(), $request->validated(), $request->file('receipt'));

        return redirect()->route('expenses.show', $expense)->with('success', 'Pembelian dicatat.');
    }

    public function show(Expense $expense)
    {
        $expense->load(['items', 'user', 'canceller']);

        return view('expenses.show', compact('expense'));
    }

    public function cancel(Request $request, Expense $expense, ExpenseService $service)
    {
        $data = $request->validate(
            ['reason' => ['required', 'string', 'min:3', 'max:255']],
            ['reason.required' => 'Alasan pembatalan wajib diisi.', 'reason.min' => 'Alasan terlalu singkat.']
        );

        $service->cancel($expense, $request->user(), $data['reason']);

        return back()->with('success', 'Pembelian dibatalkan.');
    }
}
