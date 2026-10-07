@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
    <div class="crud-page">

        <div class="p-header">
            <div>
                <a href="{{ route('products.index') }}" class="p-back">
                    <i data-lucide="arrow-left"></i> Kembali ke produk
                </a>
                <h1>Edit Produk</h1>
                <p>{{ $product->name }}</p>
            </div>
        </div>

        <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data"
            id="productForm" novalidate data-guard>
            @csrf
            @method('PUT')
            @include('pages.products._form')
        </form>

    </div>
@endsection
