@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
    <div class="crud-page">

        <div class="p-header">
            <div>
                <a href="{{ route('products.index') }}" class="p-back">
                    <i data-lucide="arrow-left"></i> Kembali ke produk
                </a>
                <h1>Tambah Produk</h1>
                <p>Tambahkan sparepart baru ke katalog.</p>
            </div>
        </div>

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data"
            id="productForm" novalidate data-guard>
            @csrf
            @include('pages.products._form')
        </form>

    </div>
@endsection
