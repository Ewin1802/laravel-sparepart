@extends('layouts.app')

@section('title', 'Edit Member')

@section('content')
    <div class="crud-page">

        <div class="p-header">
            <div>
                <a href="{{ route('members.index') }}" class="p-back">
                    <i data-lucide="arrow-left"></i> Kembali ke member
                </a>
                <h1>Edit Member</h1>
                <p>{{ $member->user->name ?? '-' }} · <span class="p-code">{{ $member->code }}</span></p>
            </div>
        </div>

        <form method="POST" action="{{ route('members.update', $member->id) }}" novalidate data-guard>
            @csrf
            @method('PUT')
            @include('pages.member._form', ['member' => $member])
        </form>

    </div>
@endsection
