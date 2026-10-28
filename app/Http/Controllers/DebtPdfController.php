<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Setting;
use App\Services\DebtReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DebtPdfController extends Controller
{
    // Rincian hutang satu pelanggan (Admin dan Kasir)
    public function statement(Request $request, Customer $customer, DebtReportService $report)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $data = $report->statement(
            $customer,
            $request->filled('from') ? Carbon::parse($request->query('from')) : null,
            $request->filled('to') ? Carbon::parse($request->query('to')) : null,
        );

        $data['setting']   = Setting::current();
        $data['printedBy'] = $request->user()->name;

        $file = 'hutang-' . Str::slug($customer->name) . '-' . now()->format('Ymd') . '.pdf';

        return Pdf::loadView('pdf.debt-statement', $data)->setPaper('a4')->stream($file);
    }

    // Daftar piutang seluruh pelanggan (khusus Admin)
    public function receivables(Request $request)
    {
        $customers = Customer::withBalance()
            ->withMax(['debtEntries as last_debt_at' => fn($q) => $q->where('type', 'hutang')], 'created_at')
            ->get()
            ->filter(fn($c) => $c->balance > 0)
            ->sortByDesc('balance')
            ->values();

        return Pdf::loadView('pdf.receivables', [
            'customers' => $customers,
            'total'     => (int) $customers->sum('balance'),
            'setting'   => Setting::current(),
            'printedBy' => $request->user()->name,
        ])->setPaper('a4')->stream('piutang-' . now()->format('Ymd') . '.pdf');
    }
}
