<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Barcode;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Daftar produk untuk sinkron aplikasi kasir (barcode ikut terkirim).
     */
    public function index()
    {
        $products = Product::with('category')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Product',
            'data' => $products,
        ], 200);
    }

    /**
     * Tambah produk dari aplikasi.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            // desimal boleh: selang per meter, oli per liter
            'stock' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'status' => 'required|in:1,0',
            'is_favorite' => 'required|in:1,0',
            'base_unit' => 'required|string|max:10',
            ...$this->codeRules(),
        ]);

        $product = new Product();
        $product->name = $data['name'];
        $product->description = $data['description'] ?? null;
        $product->price = (int) $data['price'];
        $product->stock = $data['stock'];
        $product->category_id = $data['category_id'];
        $product->status = (int) $data['status'];
        $product->is_favorite = (int) $data['is_favorite'];
        $product->base_unit = $data['base_unit'];
        $product->code = Barcode::normalize($data['code'] ?? null);
        // kosong → kode toko dibuat otomatis oleh model Product
        $product->barcode = Barcode::normalize($data['barcode'] ?? null);
        $product->save();

        if ($request->hasFile('image')) {
            $product->image = $this->storeImage($request->file('image'), $product);
            $product->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $product->fresh('category'),
        ], 201);
    }

    /**
     * Ubah produk dari aplikasi. Kolom yang tidak dikirim tidak diubah.
     */
    public function update(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:products,id']);

        $product = Product::findOrFail($request->id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'base_unit' => 'required|string|max:10',
            'status' => 'sometimes|in:1,0',
            'is_favorite' => 'sometimes|in:1,0',
            ...$this->codeRules($product->id),
        ]);

        $product->name = $data['name'];
        $product->price = (int) $data['price'];
        $product->category_id = $data['category_id'];
        $product->stock = $data['stock'];
        $product->base_unit = $data['base_unit'];

        if ($request->has('description')) {
            $product->description = $data['description'];
        }

        if ($request->has('status')) {
            $product->status = (int) $data['status'];
        }

        if ($request->has('is_favorite')) {
            $product->is_favorite = (int) $data['is_favorite'];
        }

        if ($request->has('code')) {
            $product->code = Barcode::normalize($data['code'] ?? null);
        }

        if ($request->has('barcode')) {
            // dikosongkan → kembali ke kode toko (model Product)
            $product->barcode = Barcode::normalize($data['barcode'] ?? null);
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($product->image);
            $product->image = $this->storeImage($request->file('image'), $product);
        }

        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' => $product->fresh('category'),
        ]);
    }

    /**
     * Update HANYA status ketersediaan produk (aktif/nonaktif).
     *
     * Sengaja dipisah dari update(), supaya kasir yang cuma ingin
     * menandai produk "tidak tersedia sementara" tidak perlu (dan tidak
     * bisa) mengirim ulang seluruh data produk.
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:products,id',
            'status' => 'required|in:0,1',
        ]);

        $product = Product::findOrFail($request->id);

        $product->status = (int) $request->status;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product status updated',
            'data' => $product,
        ]);
    }

    /**
     * Hapus produk. Produk yang sudah punya riwayat stok masuk tidak bisa
     * dihapus (data pembelian harus tetap utuh) — nonaktifkan saja.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $image = $product->image;

        try {
            DB::transaction(function () use ($product) {
                DB::table('order_items')->where('product_id', $product->id)->delete();
                $product->delete();
            });
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produk sudah punya riwayat stok masuk, tidak dapat dihapus. Nonaktifkan saja produknya.',
            ], 409);
        }

        $this->deleteImage($image);

        return response()->json([
            'success' => true,
            'message' => 'Product Deleted',
        ]);
    }

    // ============================================================
    // HELPER
    // ============================================================

    /** Kode part & barcode: opsional, tidak boleh kembar. */
    private function codeRules($ignoreId = null): array
    {
        return [
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('products', 'code')->ignore($ignoreId),
            ],
            'barcode' => [
                'nullable', 'string', 'max:64',
                'regex:/^[\x20-\x7E]+$/',
                Rule::unique('products', 'barcode')->ignore($ignoreId),
            ],
        ];
    }

    /**
     * Simpan foto dengan pola yang sama seperti panel web:
     * file di storage/app/public/products/{id}.{ext}, kolom image = "storage/products/{id}.{ext}".
     */
    private function storeImage(UploadedFile $file, Product $product): string
    {
        $filename = $product->id . '.' . $file->getClientOriginalExtension();

        $file->storeAs('products', $filename, 'public');

        return 'storage/products/' . $filename;
    }

    /** Hapus foto lama, termasuk format lama ("products/x.jpg" atau nama file saja). */
    private function deleteImage(?string $image): void
    {
        if (! $image) {
            return;
        }

        $path = preg_replace('#^storage/#', '', $image);

        if (! str_contains($path, '/')) {
            $path = 'products/' . $path;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
