@extends('layouts.app')

@section('title', 'Edit Informasi')

@section('content')
    <div class="crud-page">

        <div class="p-header">
            <div>
                <a href="{{ route('announcements.index') }}" class="p-back">
                    <i data-lucide="arrow-left"></i> Kembali ke informasi
                </a>
                <h1>Edit Informasi</h1>
                <p>{{ $announcement->title }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('announcements.update', $announcement->id) }}"
            enctype="multipart/form-data" novalidate data-guard>
            @csrf
            @method('PUT')
            @include('pages.announcement._form')
        </form>

    </div>
@endsection
