<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProductLabelController;
use App\Http\Controllers\ServerBillingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPriceReportController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])
    ->name('landing');

// Route::get('/daftar-member', [MemberRegisterController::class, 'create'])
//     ->name('member.register');
// Route::post('/daftar-member', [MemberRegisterController::class, 'store'])
//     ->name('member.register.store');
// Route::get('/daftar-member/sukses', [MemberRegisterController::class, 'success'])
//     ->name('member.register.success');
// Route::get('/lupa-password', [ForgotPasswordController::class, 'show'])
//     ->name('password.request');
// Route::post('/lupa-password', [ForgotPasswordController::class, 'reset'])
//     ->name('password.reset.default');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // HOME — arah setelah login, tergantung role
    Route::get('/home', function (Request $request) {
        if ($request->user()->role === 'admin') {
            return redirect()->route('dashboard');
        }

        return redirect()->route('member.portal');
    })->name('home');

    /*
    |--------------------------------------------------------------------------
    | MEMBER PORTAL (di luar role:admin, supaya member biasa bisa akses)
    |--------------------------------------------------------------------------
    */

    Route::get('/kartu-member', [MemberPortalController::class, 'show'])
        ->name('member.portal');

    Route::get('/kartu-member/riwayat', [MemberPortalController::class, 'history'])
        ->name('member.portal.history');

    Route::post('/kartu-member/redeem', [MemberPortalController::class, 'redeem'])
        ->name('member.portal.redeem');

    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES
    |--------------------------------------------------------------------------
    |
    | Semua route di bawah ini hanya untuk user dengan role "admin".
    |
    | ATURAN URUTAN: route khusus (products/import, products/labels,
    | members/generate-code, dst.) selalu ditaruh SEBELUM Route::resource
    | yang sama, supaya tidak tertangkap sebagai {id}.
    |
    */

    Route::middleware('role:admin')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */

        Route::resource('users', UserController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | CATEGORIES
        |--------------------------------------------------------------------------
        */

        Route::resource('categories', CategoryController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | PRODUCTS — import Excel & cetak label barcode
        |--------------------------------------------------------------------------
        */

        Route::get('products/import', [ProductImportController::class, 'create'])
            ->name('products.import');

        Route::post('products/import/preview', [ProductImportController::class, 'preview'])
            ->name('products.import.preview');

        Route::post('products/import', [ProductImportController::class, 'store'])
            ->name('products.import.store');

        Route::get('products/import/errors', [ProductImportController::class, 'errors'])
            ->name('products.import.errors');

        Route::get('products/labels', [ProductLabelController::class, 'index'])
            ->name('products.labels');

        Route::get('products/labels/print', [ProductLabelController::class, 'print'])
            ->name('products.labels.print');

        Route::resource('products', ProductController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | DISCOUNTS
        |--------------------------------------------------------------------------
        */

        Route::resource('discounts', DiscountController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | STOK & PEMBELIAN
        |--------------------------------------------------------------------------
        */

        Route::resource('suppliers', SupplierController::class)
            ->except(['show']);

        // tandai nota tempo sudah lunas
        Route::post('stock-ins/{id}/pay', [StockInController::class, 'pay'])
            ->name('stock-ins.pay');

        Route::resource('stock-ins', StockInController::class);

        Route::get('reports/supplier-prices', SupplierPriceReportController::class)
            ->name('reports.supplier-prices');

        /*
        |--------------------------------------------------------------------------
        | EXPENSES
        |--------------------------------------------------------------------------
        */

        Route::resource('expenses', ExpenseController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | MEMBERS
        |--------------------------------------------------------------------------
        */

        Route::get('members/generate-code', [MemberController::class, 'generateCode'])
            ->name('members.generate-code');

        Route::resource('members', MemberController::class);

        /*
        |--------------------------------------------------------------------------
        | ORDERS (TRANSAKSI)
        |--------------------------------------------------------------------------
        |
        | GET    /orders            daftar transaksi
        | GET    /orders/summary    ringkasan transaksi
        | GET    /orders/{id}       detail (dipakai modal detail transaksi)
        | GET    /orders/{id}/edit  form koreksi
        | PUT    /orders/{id}       simpan koreksi
        | DELETE /orders/{id}       hapus transaksi
        |
        */

        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');

        Route::get('/orders/summary', [OrderController::class, 'summary'])
            ->name('orders.summary');

        Route::get('/orders/{id}', [OrderController::class, 'show'])
            ->whereNumber('id')
            ->name('orders.show');

        Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])
            ->whereNumber('id')
            ->name('orders.edit');

        Route::put('/orders/{id}', [OrderController::class, 'update'])
            ->whereNumber('id')
            ->name('orders.update');

        Route::delete('/orders/{id}', [OrderController::class, 'destroy'])
            ->whereNumber('id')
            ->name('orders.destroy');

        /*
        |--------------------------------------------------------------------------
        | ANNOUNCEMENTS (INFORMASI MEMBER)
        |--------------------------------------------------------------------------
        */

        Route::resource('announcements', AnnouncementController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | SETTINGS
        |--------------------------------------------------------------------------
        */

        Route::get('settings', [SettingController::class, 'edit'])
            ->name('settings.edit');

        Route::put('settings', [SettingController::class, 'update'])
            ->name('settings.update');

        // tagihan server: "Sudah dibayar" → jatuh tempo maju satu siklus
        Route::post('settings/server-paid', [SettingController::class, 'serverPaid'])
            ->name('settings.server-paid');
    });
});
