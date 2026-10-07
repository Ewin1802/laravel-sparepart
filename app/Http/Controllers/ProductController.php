<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name')
            ->when($request->name, function ($query, $name) {
                $query->where('products.name', 'like', "%{$name}%");
            })
            ->orderByDesc('products.id')
            ->paginate(10)
            ->withQueryString();

        return view('pages.products.index', compact('products'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $categories = DB::table('categories')
            ->orderBy('name')
            ->get();

        return view('pages.products.create', compact('categories'));
    }

    /**
     * Store new product.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|max:255',
            'description'   => 'required',
            'price'         => 'required|numeric|min:0',
            'category_id'   => 'required|exists:categories,id',
            'stock'         => 'required|numeric|min:0',
            'status'        => 'required|boolean',
            'is_favorite'   => 'required|boolean',
            'base_unit' => 'required|string|max:10',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $product = new Product();

        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->category_id = $request->category_id;
        $product->stock = $request->stock;
        $product->status = $request->status;
        $product->is_favorite = $request->is_favorite;
        $product->base_unit = $request->base_unit;

        // simpan dulu agar mendapatkan ID
        $product->save();

        // upload gambar
        if ($request->hasFile('image')) {

            $image = $request->file('image');

            $filename = $product->id . '.' . $image->getClientOriginalExtension();

            $image->storeAs(
                'products',
                $filename,
                'public'
            );

            $product->image = 'storage/products/' . $filename;

            $product->save();
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);

        $categories = DB::table('categories')
            ->orderBy('name')
            ->get();

        return view('pages.products.edit', compact(
            'product',
            'categories'
        ));
    }

    /**
     * Update product.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'          => 'required|max:255',
            'description'   => 'required',
            'price'         => 'required|numeric|min:0',
            'category_id'   => 'required|exists:categories,id',
            'stock'         => 'required|numeric|min:0',
            'status'        => 'required|boolean',
            'is_favorite'   => 'required|boolean',
            'base_unit'     => 'required|string|max:10',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $product = Product::findOrFail($id);

        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->category_id = $request->category_id;
        $product->stock = $request->stock;
        $product->status = $request->status;
        $product->base_unit = $request->base_unit;
        $product->is_favorite = $request->is_favorite;

        if ($request->hasFile('image')) {

            // hapus gambar lama
            if (
                $product->image &&
                Storage::disk('public')->exists(
                    str_replace('storage/', '', $product->image)
                )
            ) {

                Storage::disk('public')->delete(
                    str_replace('storage/', '', $product->image)
                );
            }

            $image = $request->file('image');

            $filename = $product->id . '.' . $image->getClientOriginalExtension();

            $image->storeAs(
                'products',
                $filename,
                'public'
            );

            $product->image = 'storage/products/' . $filename;
        }

        $product->save();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Delete product.
     */
    public function destroy($id)
    {
        try {

            // Semua langkah dalam SATU transaksi: kalau produk gagal dihapus
            // (mis. masih dipakai di riwayat stok masuk), penghapusan
            // order_items ikut dibatalkan — tidak ada data yang hilang separuh.
            $image = DB::transaction(function () use ($id) {

                $product = Product::findOrFail($id);

                DB::table('order_items')
                    ->where('product_id', $id)
                    ->delete();

                $image = $product->image;

                $product->delete();

                return $image;
            });

            // file gambar dihapus setelah data benar-benar terhapus
            if (
                $image &&
                Storage::disk('public')->exists(
                    str_replace('storage/', '', $image)
                )
            ) {

                Storage::disk('public')->delete(
                    str_replace('storage/', '', $image)
                );
            }

            return redirect()
                ->route('products.index')
                ->with('success', 'Produk berhasil dihapus.');

        } catch (QueryException $e) {

            return redirect()
                ->route('products.index')
                ->with(
                    'error',
                    'Produk tidak dapat dihapus karena sudah punya riwayat stok masuk. Nonaktifkan saja produknya.'
                );

        } catch (\Exception $e) {

            return redirect()
                ->route('products.index')
                ->with(
                    'error',
                    'Terjadi kesalahan saat menghapus produk.'
                );
        }
    }
}
