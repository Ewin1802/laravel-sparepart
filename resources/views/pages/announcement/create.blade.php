@extends('layouts.app')

@section('title', 'Tambah Informasi')

@section('content')
    <div class="crud-page">

        <div class="p-header">
            <div>
                <a href="{{ route('announcements.index') }}" class="p-back">
                    <i data-lucide="arrow-left"></i> Kembali ke informasi
                </a>
                <h1>Tambah Informasi</h1>
                <p>Buat info atau promo baru untuk aplikasi member.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('announcements.store') }}" enctype="multipart/form-data" novalidate
            data-guard>
            @csrf
            @include('pages.announcement._form')
        </form>

    </div>
@endsection
