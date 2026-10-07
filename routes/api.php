<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\MemberBarcodeController;
use App\Http\Controllers\Api\MemberOrderController;
use App\Http\Controllers\Api\MemberStampController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\OrderSyncController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Semua route otomatis menggunakan prefix /api
|
*/

// ==========================================================================
// AUTHENTICATION (PUBLIC)
// ==========================================================================

Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// ==========================================================================
// AUTHENTICATED ROUTES
// ==========================================================================

Route::middleware('auth:sanctum')->group(function () {

    // ======================================================================
    // CURRENT USER / SESSION
    // ======================================================================

    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user(),
        ]);
    })->name('auth.user');

    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/profile/update', [ProfileController::class, 'update'])
        ->name('profile.update');
    // ======================================================================
    // MEMBER
    // ======================================================================
    //
    // NOTE: prefix('member') dipakai supaya URI di bawah otomatis jadi
    // member/scan, member/stamp, member/stamp/history, dst — persis sama
    // seperti sebelumnya, cuma gak perlu ditulis 'member/' berulang-ulang.
    //
    Route::prefix('member')->name('member.')->group(function () {

        Route::post('/scan', [MemberBarcodeController::class, 'scan'])->name('scan');

        Route::get('/sync', [MemberBarcodeController::class, 'sync'])->name('sync');

        // Stamp milik member yang sedang login
        Route::get('/stamp', [MemberStampController::class, 'show'])->name('stamp.show');
        Route::get('/stamp/history', [MemberStampController::class, 'history'])->name('stamp.history');
        Route::post('/stamp/earn', [MemberStampController::class, 'earn'])->name('stamp.earn');

        /*
        |----------------------------------------------------------------
        | REDEEM MYSTERY BOX
        |----------------------------------------------------------------
        |
        | Dipanggil aplikasi member saat menekan "Buka Mystery Box".
        |
        */
        Route::post('/stamp/redeem', [MemberStampController::class, 'redeem'])->name('stamp.redeem');

        /*
        |----------------------------------------------------------------
        | RIWAYAT TRANSAKSI & MENU FAVORIT MEMBER
        |----------------------------------------------------------------
        |
        | Dipakai halaman TransactionHistoryPage di aplikasi member.
        |
        */
        Route::get('/orders', [MemberOrderController::class, 'history'])->name('orders.history');
        Route::get('/orders/{id}', [MemberOrderController::class, 'show'])
            ->whereNumber('id')
            ->name('orders.show');
        Route::get('/top-products', [MemberOrderController::class, 'topProducts'])->name('top-products');
        Route::get('/orders-summary', [MemberOrderController::class, 'summary'])->name('orders.summary');

        /*
        |----------------------------------------------------------------
        | INFORMASI & PROMO (ANNOUNCEMENTS)
        |----------------------------------------------------------------
        |
        | Dipakai kartu slider di Home aplikasi member.
        |
        */
        Route::get('/announcements/latest', [AnnouncementController::class, 'latest'])
            ->name('announcements.latest');

        /*
        |----------------------------------------------------------------
        | DEVICE TOKEN (PUSH NOTIFICATION)
        |----------------------------------------------------------------
        |
        | Dipanggil aplikasi member setiap kali berhasil login / token
        | FCM di-refresh, supaya backend tahu ke mana push notification
        | harus dikirim.
        |
        */
        Route::post('/device-token', [DeviceTokenController::class, 'store'])
            ->name('device-token.store');

    });

    // ======================================================================
    // PRODUCTS
    // ======================================================================

    Route::prefix('products')->name('api.products.')->group(function () {

        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::post('/edit', [ProductController::class, 'update'])->name('update');

        // Endpoint ringan khusus toggle ketersediaan produk (status 0/1)
        // dari aplikasi kasir — dipisah dari /products/edit supaya kasir
        // tidak perlu (dan tidak boleh) mengirim ulang seluruh data produk
        // hanya untuk menandai tersedia/tidak.
        Route::post('/status', [ProductController::class, 'updateStatus'])->name('status');

        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');

    });

    // ======================================================================
    // CATEGORIES
    // ======================================================================
    //
    // NOTE: URI resource ini sengaja tetap '/api-categories' (bukan
    // '/categories') supaya tidak mengubah endpoint yang mungkin sudah
    // dipakai aplikasi Flutter. Kalau suatu saat mau dirapikan jadi
    // '/categories', beri tahu saya — itu breaking change buat client.
    //
    Route::apiResource('api-categories', CategoryController::class)
        ->names('api.categories');

    // ======================================================================
    // ORDERS
    // ======================================================================

    /*
    |--------------------------------------------------------------------------
    | Save Order
    |--------------------------------------------------------------------------
    |
    | POST /api/save-order
    |
    | Endpoint ini yang akan:
    | 1. Membuat order
    | 2. Memastikan idempotency
    | 3. Menyimpan member_code
    | 4. Memberikan stamp jika ada member
    |
    */
    Route::post('/save-order', [OrderController::class, 'saveOrder'])->name('orders.save');

    // ======================================================================
    // DISCOUNTS
    // ======================================================================
    //
    // NOTE: sama seperti categories, URI '/api-discounts' dipertahankan
    // apa adanya supaya tidak breaking change buat client yang sudah pakai.
    //
    Route::get('/api-discounts', [DiscountController::class, 'index'])->name('api.discounts.index');
    Route::post('/api-discounts', [DiscountController::class, 'store'])->name('api.discounts.store');

    // ======================================================================
    // REPORTS
    // ======================================================================

    Route::get('/orders/{date?}', [OrderController::class, 'index'])->name('reports.orders');
    Route::get('/summary/{date?}', [OrderController::class, 'summary'])->name('reports.summary');
    Route::get('/order-item/{date?}', [OrderItemController::class, 'index'])->name('reports.order-item');
    Route::get('/order-sales', [OrderItemController::class, 'orderSales'])->name('reports.order-sales');

    Route::get('/orders/sync', [OrderSyncController::class, 'sync'])->name('orders.sync');

});
