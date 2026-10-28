<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index', [
            'products' => Product::with('category')->orderBy('category_id')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('products.form', [
            'product'    => new Product(['is_available' => true]),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $data = $request->safe()->except('photo');
        $data['is_available'] = $request->boolean('is_available');

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Produk ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('products.form', [
            'product'    => $product,
            'categories' => Category::where('is_active', true)->orWhere('id', $product->category_id)->orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->safe()->except('photo');
        $data['is_available'] = $request->boolean('is_available');

        if ($request->hasFile('photo')) {
            if ($product->photo_path) {
                Storage::disk('public')->delete($product->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Produk diperbarui.');
    }

    public function toggleAvailability(Product $product)
    {
        $product->update(['is_available' => ! $product->is_available]);

        return back();
    }

    // Pengganti "hapus": produk yang pernah terjual tidak boleh hilang dari riwayat
    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', 'Status produk diperbarui.');
    }
}
