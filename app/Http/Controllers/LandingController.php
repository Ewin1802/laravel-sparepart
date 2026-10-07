<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', 30);

        if (!in_array($range, [7, 30, 90])) {
            $range = 30;
        }

        // ==========================================
        // SETTING TOKO
        // ==========================================
        $setting = Setting::first();


        // ==========================================
        // TOP PRODUCTS
        // ==========================================
        $topProducts = DB::table('order_items')
            ->join(
                'products',
                'order_items.product_id',
                '=',
                'products.id'
            )
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('MAX(order_items.price) as unit_price')
            )
            ->havingRaw('SUM(order_items.quantity) > 0')
            ->where(
                'order_items.created_at',
                '>=',
                Carbon::now()->subDays($range)
            )
            ->groupBy(
                'products.id',
                'products.name'
            )
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();


        // ==========================================
        // TOP PRODUCT IDS
        // ==========================================
        $topProductIds = $topProducts->isNotEmpty()
            ? $topProducts
                ->take(3)
                ->pluck('id')
                ->toArray()
            : [];


        // ==========================================
        // SLIDER PRODUCTS
        // ==========================================
        $sliderProducts = Product::with('category')
            ->where('status', 1)
            ->whereNotNull('image')
            ->orderByDesc('is_favorite')
            ->limit(20)
            ->get();


        // ==========================================
        // MENU PRODUCTS
        // ==========================================
        $menuProducts = Product::with('category')
            ->where('status', 1)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();


        // ==========================================
        // CATEGORIES
        // ==========================================
        $categories = Category::orderBy('name')->get();


        // ==========================================
        // LANDING PAGE
        // ==========================================
        return view('landing', compact(
            'setting',
            'topProducts',
            'sliderProducts',
            'topProductIds',
            'range',
            'menuProducts',
            'categories'
        ));
    }
}
