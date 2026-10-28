<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\OrderService;

class PosController extends Controller
{
    public function index(OrderService $orders)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $products = Product::active()
            ->whereIn('category_id', $categories->pluck('id'))
            ->orderBy('name')
            ->get()
            ->map(fn(Product $p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'price'       => $p->price,
                'category_id' => $p->category_id,
                'photo'       => $p->photo_path ? asset('storage/' . $p->photo_path) : null,
                'available'   => $p->is_available,
            ])
            ->values();
        $customers = Customer::where('is_active', true)
            ->withBalance()
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'      => $c->id,
                'name'    => $c->name,
                'phone'   => $c->phone,
                'balance' => (int) $c->balance,
            ])
            ->values();

        return view('pos.index', [
            'customers'  => $customers,
            'categories' => $categories,
            'products'   => $products,
            'nextQueue'  => $orders->nextQueueNo(),
        ]);
    }

    public function store(StoreOrderRequest $request, OrderService $orders)
    {
        $order = $orders->create($request->user(), $request->validated());

        return redirect()->route('receipt.show', $order)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }
}
