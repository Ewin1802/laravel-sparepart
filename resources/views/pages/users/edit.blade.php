@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="crud-page">

        <div class="p-header">
            <div>
                <a href="{{ route('users.index') }}" class="p-back">
                    <i data-lucide="arrow-left"></i> Kembali ke user
                </a>
                <h1>Edit User</h1>
                <p>{{ $user->name }} · {{ $user->email }}</p>
            </div>
        </div>

        <form action="{{ route('users.update', $user) }}" method="POST" novalidate data-guard>
            @csrf
            @method('PUT')
            @include('pages.users._form')
        </form>

    </div>
@endsection
