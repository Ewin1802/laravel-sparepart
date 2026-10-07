@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
    <div class="crud-page">

        <div class="p-header">
            <div>
                <a href="{{ route('users.index') }}" class="p-back">
                    <i data-lucide="arrow-left"></i> Kembali ke user
                </a>
                <h1>Tambah User</h1>
                <p>Buat akun untuk admin, kasir, atau pelanggan.</p>
            </div>
        </div>

        <form action="{{ route('users.store') }}" method="POST" novalidate data-guard>
            @csrf
            @include('pages.users._form')
        </form>

    </div>
@endsection
