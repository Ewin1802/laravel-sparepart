<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberBarcode;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberOrderController extends Controller
{
    /**
     * ==========================================================
     * RIWAYAT TRANSAKSI MEMBER (PAGINATED)
     * ==========================================================
     *
     * GET /api/member/orders
     *
     * Hanya menampilkan order milik member yang sedang login,
     * dicocokkan lewat orders.member_code = member_barcodes.code.
     */
    public function history(Request $request)
    {
        $member = $this->findMember($request);

        if (!$member) {
            return $this->memberNotFound();
        }

        $orders = Order::where('member_code', $member->code)
            ->orderByDesc('id')
            ->paginate(10);

        $data = $orders->getCollection()->map(function (Order $order) {
            return $this->formatOrderSummary($order);
        });

        return response()->json([
            'status' => 'success',

            'data' => $data,

            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * ==========================================================
     * DETAIL SATU TRANSAKSI MILIK MEMBER
     * ==========================================================
     *
     * GET /api/member/orders/{id}
     *
     * PENTING: order dicocokkan dengan member_code milik member
     * yang login, supaya member A tidak bisa mengintip transaksi
     * member B hanya dengan menebak-nebak ID order.
     */
    public function show(Request $request, int $id)
    {
        $member = $this->findMember($request);

        if (!$member) {
            return $this->memberNotFound();
        }

        $order = Order::with('orderItems.product')
            ->where('id', $id)
            ->where('member_code', $member->code)
            ->first();

        if (!$order) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        $items = $order->orderItems
            ->map(function ($item) {
                $productName = $item->product_name
                    ?? optional($item->product)->name
                    ?? 'Produk';

                return [
                    'product_id'   => $item->product_id,
                    'product_name' => $productName,
                    'quantity'     => $item->quantity,
                    'price'        => (float) $item->price,
                    'total'        => (float) $item->quantity * (float) $item->price,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'order'  => $this->formatOrderDetail($order),
            'items'  => $items,
        ]);
    }

    /**
     * ==========================================================
     * MENU YANG PALING SERING DIPESAN OLEH MEMBER INI
     * ==========================================================
     *
     * GET /api/member/top-products?limit=5
     */
    public function topProducts(Request $request)
    {
        $member = $this->findMember($request);

        if (!$member) {
            return $this->memberNotFound();
        }

        $limit = (int) $request->input('limit', 5);

        if ($limit < 1) {
            $limit = 5;
        }

        $orderIds = Order::where('member_code', $member->code)->pluck('id');

        $topProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_quantity'))
            ->whereIn('order_id', $orderIds)
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->with('product')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id'     => $item->product_id,
                    'name'           => optional($item->product)->name ?? 'Produk',
                    'image'          => optional($item->product)->image,
                    'total_quantity' => (int) $item->total_quantity,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data'   => $topProducts,
        ]);
    }

    /**
     * ==========================================================
     * RINGKASAN PROFIL MEMBER
     * ==========================================================
     *
     * GET /api/member/orders-summary
     *
     * Bonus endpoint: total belanja, total transaksi, dan
     * menu favorit dalam satu response — cocok untuk kartu
     * ringkasan di halaman profil aplikasi member.
     */
    public function summary(Request $request)
    {
        $member = $this->findMember($request);

        if (!$member) {
            return $this->memberNotFound();
        }

        $orderQuery = Order::where('member_code', $member->code);

        $totalOrders = (clone $orderQuery)->count();
        $totalSpent  = (clone $orderQuery)->sum('payment_amount');

        $orderIds = (clone $orderQuery)->pluck('id');

        $favorite = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_quantity'))
            ->whereIn('order_id', $orderIds)
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->with('product')
            ->first();

        return response()->json([
            'status' => 'success',

            'data' => [
                'total_orders' => $totalOrders,
                'total_spent'  => (int) $totalSpent,

                'favorite_product' => $favorite ? [
                    'product_id'     => $favorite->product_id,
                    'name'           => optional($favorite->product)->name ?? 'Produk',
                    'total_quantity' => (int) $favorite->total_quantity,
                ] : null,
            ],
        ]);
    }

    // ==============================================================
    // HELPERS
    // ==============================================================

    private function findMember(Request $request): ?MemberBarcode
    {
        $user = $request->user();

        return MemberBarcode::where('user_id', $user->id)->first();
    }

    private function memberNotFound()
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Data member tidak ditemukan.',
        ], 404);
    }

    private function formatOrderSummary(Order $order): array
    {
        return [
            'id'              => $order->id,
            'invoice'         => 'INV' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'transaction_time' => $order->transaction_time,
            'payment_method'  => $order->payment_method,
            'total_item'      => $order->total_item,
            'total'           => $order->total,
            'payment_amount'  => $order->payment_amount,
        ];
    }

    private function formatOrderDetail(Order $order): array
    {
        return [
            'id'               => $order->id,
            'invoice'          => 'INV' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'transaction_time' => $order->transaction_time,
            'payment_method'   => $order->payment_method,
            'sub_total'        => $order->sub_total,
            'tax'              => $order->tax,
            'discount_amount'  => $order->discount_amount,
            'service_charge'   => $order->service_charge,
            'total'            => $order->total,
            'payment_amount'   => $order->payment_amount,
            'total_item'       => $order->total_item,
        ];
    }
}
