<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['date' => ['nullable', 'date']]);

        // Admin boleh memilih tanggal; Kasir hanya melihat hari ini.
        $date = $request->user()->isAdmin() && $request->filled('date')
            ? Carbon::parse($request->query('date'))
            : today();

        $orders = Order::with(['user', 'payment', 'customer'])
            ->whereDate('created_at', $date)
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('orders.index', ['orders' => $orders, 'date' => $date]);
    }

    public function cancel(Request $request, Order $order, OrderService $service)
    {
        $data = $request->validate(
            ['reason' => ['required', 'string', 'min:3', 'max:255']],
            ['reason.required' => 'Alasan pembatalan wajib diisi.', 'reason.min' => 'Alasan terlalu singkat.']
        );

        $service->cancel($order, $request->user(), $data['reason']);

        return back()->with('success', "Transaksi {$order->order_no} dibatalkan. Pengembalian uang dilakukan manual.");
    }
}
