<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DebtEntry;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($request->query('from', today()->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->query('to', $from->toDateString()))->endOfDay();

        // Semua laporan penjualan hanya menghitung transaksi berstatus "selesai"
        $completed = fn () => Order::completed()->whereBetween('created_at', [$from, $to]);

        // 1. Penjualan
        $summary = $completed()->selectRaw('COUNT(*) as trx, COALESCE(SUM(total), 0) as sales')->first();

        $cancelled = Order::where('status', Order::STATUS_BATAL)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // 2. Per metode: uang yang benar-benar masuk (diterima dikurangi kembalian) dan bagian yang dihutang
        $byMethod = Payment::join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('orders.status', Order::STATUS_SELESAI)
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('payments.method,
                         COALESCE(SUM(payments.amount_received - payments.change_amount), 0) as net,
                         COALESCE(SUM(payments.debt_amount), 0) as debt')
            ->groupBy('payments.method')
            ->get()
            ->keyBy('method');

        $newDebt = (int) $byMethod->sum('debt');

        // 3. Pelunasan hutang yang diterima pada periode, per metode
        $repaid = DebtEntry::where('type', DebtEntry::TYPE_BAYAR)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('method, COALESCE(SUM(amount), 0) as total')
            ->groupBy('method')
            ->get()
            ->keyBy('method');

        $moneyIn = [
            'tunai' => [
                'sales' => (int) ($byMethod->get('tunai')?->net ?? 0),
                'repay' => (int) ($repaid->get('tunai')?->total ?? 0),
            ],
            'qris' => [
                'sales' => (int) ($byMethod->get('qris')?->net ?? 0),
                'repay' => (int) ($repaid->get('qris')?->total ?? 0),
            ],
        ];

        $totalIn = $moneyIn['tunai']['sales'] + $moneyIn['tunai']['repay']
                 + $moneyIn['qris']['sales'] + $moneyIn['qris']['repay'];

        // 4. Piutang saat ini (tidak tergantung periode)
        $receivables = Customer::withBalance()->get()
            ->filter(fn ($c) => $c->balance > 0)
            ->sortByDesc('balance')
            ->values();

        $totalReceivable = (int) $receivables->sum('balance');

        // 5. Produk terlaris dan penjualan harian
        $topProducts = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', Order::STATUS_SELESAI)
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('order_items.product_name, SUM(order_items.qty) as qty, SUM(order_items.subtotal) as sales')
            ->groupBy('order_items.product_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $daily = $completed()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as trx, SUM(total) as sales')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // 6. Pengeluaran periode (pembelian yang dibatalkan tidak dihitung)
        $expenseByCategory = Expense::active()
            ->whereBetween('spent_at', [$from, $to])
            ->selectRaw('category, COUNT(*) as n, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $totalExpense = (int) $expenseByCategory->sum('total');
        $netCash      = $totalIn - $totalExpense;

        return view('reports.index', compact(
            'from', 'to', 'summary', 'cancelled', 'newDebt', 'moneyIn', 'totalIn',
            'receivables', 'totalReceivable', 'topProducts', 'daily',
            'expenseByCategory', 'totalExpense', 'netCash'
        ));
    }
}