<?php

use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\DebtPdfController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiningTableController;


// --- Halaman awal (sebelum login) ---
Route::get('/', [HomeController::class, 'index'])->name('home');

// --- Tamu (belum login) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
});

// --- Sudah login (Admin dan Kasir) ---
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/kasir', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pelanggan', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/pelanggan', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/pelanggan/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/pelanggan/{customer}/bayar', [CustomerController::class, 'pay'])->name('customers.pay');
    Route::get('/pelanggan/{customer}/hutang.pdf', [DebtPdfController::class, 'statement'])->name('customers.debt-pdf');
    Route::post('/kasir', [PosController::class, 'store'])->name('pos.store');
    Route::get('/struk/{order}', [ReceiptController::class, 'show'])->name('receipt.show');
    Route::get('/riwayat', [OrderController::class, 'index'])->name('orders.index');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // --- Khusus Admin ---
    Route::middleware('role:admin')->group(function () {
        Route::post('/riwayat/{order}/batal', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('/pengeluaran', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/pengeluaran/baru', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/pengeluaran', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/pengeluaran/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
        Route::post('/pengeluaran/{expense}/batal', [ExpenseController::class, 'cancel'])->name('expenses.cancel');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/piutang.pdf', [DebtPdfController::class, 'receivables'])->name('reports.receivables-pdf');
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update']);
        Route::patch('/categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');

        Route::resource('products', ProductController::class)->except(['show', 'destroy']);
        Route::patch('/products/{product}/availability', [ProductController::class, 'toggleAvailability'])->name('products.availability');
        Route::patch('/products/{product}/active', [ProductController::class, 'toggleActive'])->name('products.active');

        Route::get('/akun', [UserController::class, 'index'])->name('users.index');
        Route::post('/akun', [UserController::class, 'store'])->name('users.store');
        Route::patch('/akun/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::patch('/akun/{user}/reset', [UserController::class, 'resetPassword'])->name('users.reset');
        Route::put('/pelanggan/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('/pelanggan/{customer}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');
        Route::get('/meja', [DiningTableController::class, 'index'])->name('tables.index');
        Route::post('/meja', [DiningTableController::class, 'store'])->name('tables.store');
        Route::post('/meja/otomatis', [DiningTableController::class, 'bulk'])->name('tables.bulk');
        Route::get('/meja/cetak', [DiningTableController::class, 'print'])->name('tables.print');
        Route::patch('/meja/{table}/toggle', [DiningTableController::class, 'toggle'])->name('tables.toggle');
        Route::patch('/meja/{table}/kode', [DiningTableController::class, 'regenerate'])->name('tables.regenerate');
    });
});
