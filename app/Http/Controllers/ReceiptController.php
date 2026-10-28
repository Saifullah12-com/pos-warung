<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;

class ReceiptController extends Controller
{
    public function show(Order $order)
{
    $order->load(['items', 'payment', 'user', 'customer']);

    return view('receipt.show', [
        'order'   => $order,
        'setting' => Setting::current(),
        'balance' => $order->customer?->balance(),
    ]);
}
}
