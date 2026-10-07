@extends('layouts.app')

@section('title', 'Tambah Member')

@section('content')
    <div class="crud-page">

        <div class="p-header">
            <div>
                <a href="{{ route('members.index') }}" class="p-back">
                    <i data-lucide="arrow-left"></i> Kembali ke member
                </a>
                <h1>Tambah Member</h1>
                <p>Daftarkan member baru beserta kartu barcode dan diskonnya.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('members.store') }}" novalidate data-guard>
            @csrf
            @include('pages.member._form', ['member' => null])
        </form>

    </div>
@endsection
