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
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPriceReportController;
use App\Http\Controllers\UserController;
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

// ROUTE "HOME" — nentuin redirect setelah login, tergantung role
    Route::get('/home', function (\Illuminate\Http\Request $request) {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->route('dashboard');
        }

        return redirect()->route('member.portal');
    })->name('home');

    // ROUTE INI DI LUAR role:admin, supaya member biasa bisa akses
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
    | Semua route di bawah ini hanya bisa diakses oleh user
    | dengan role "admin", termasuk Dashboard.
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
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

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
        | EXPENSES
        |--------------------------------------------------------------------------
        */

        Route::resource('expenses', ExpenseController::class)
            ->except(['show']);

        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::post('stock-ins/{id}/pay', [StockInController::class, 'pay'])->name('stock-ins.pay');
        Route::resource('stock-ins', StockInController::class);
        Route::get('reports/supplier-prices', SupplierPriceReportController::class)->name('reports.supplier-prices');
        Route::get('products/import', [ProductImportController::class, 'create'])->name('products.import');
        Route::post('products/import/preview', [ProductImportController::class, 'preview'])->name('products.import.preview');
        Route::post('products/import', [ProductImportController::class, 'store'])->name('products.import.store');
        Route::get('products/import/errors', [ProductImportController::class, 'errors'])->name('products.import.errors');

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
        | ORDER REPORT
        |--------------------------------------------------------------------------
        |
        | GET /orders
        | Menampilkan daftar transaksi
        |
        */

        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');

        /*
        |--------------------------------------------------------------------------
        | ORDER SUMMARY
        |--------------------------------------------------------------------------
        |
        | GET /orders/summary
        | Digunakan untuk mengambil ringkasan transaksi
        |
        */

        Route::get('/orders/summary', [OrderController::class, 'summary'])
            ->name('orders.summary');

        /*
        |--------------------------------------------------------------------------
        | ORDER DETAIL
        |--------------------------------------------------------------------------
        |
        | GET /orders/{id}
        | Digunakan oleh modal detail transaksi
        |
        */

        Route::get('/orders/{id}', [OrderController::class, 'show'])
            ->whereNumber('id')
            ->name('orders.show');

        Route::get('settings', [SettingController::class, 'edit'])
            ->name('settings.edit');

        Route::put('settings', [SettingController::class, 'update'])
            ->name('settings.update');

        Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])
            ->name('orders.edit');

        Route::put('/orders/{id}', [OrderController::class, 'update'])
            ->name('orders.update');

            /*
            |--------------------------------------------------------------------------
            | ANNOUNCEMENTS (INFORMASI MEMBER)
            |--------------------------------------------------------------------------
            */

        Route::resource('announcements', AnnouncementController::class)
            ->except(['show']);
        Route::delete('/orders/{id}', [OrderController::class, 'destroy'])
            ->whereNumber('id')
            ->name('orders.destroy');

        // Digunakan oleh modal detail transaksi
        Route::get('/orders/{id}', [OrderController::class, 'show'])
            ->whereNumber('id')
            ->name('orders.show');
    });

});
