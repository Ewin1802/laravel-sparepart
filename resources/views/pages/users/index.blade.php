@extends('layouts.app')

@section('title', 'User')

@section('content')
    @php
        $search = request('name');
        $roles = [
            'admin' => ['Admin', 'shield-check'],
            'staff' => ['Kasir', 'scan-line'],
            'user' => ['User', 'user-round'],
        ];
    @endphp

    <div class="crud-page">

        {{-- HEADER --}}
        <div class="p-header">
            <div>
                <span class="p-eyebrow">Tim & akses</span>
                <h1>User</h1>
                <p>Kelola akun admin, kasir, dan pengguna sistem.</p>
            </div>

            <a href="{{ route('users.create') }}" class="p-btn p-btn-primary">
                <i data-lucide="user-plus"></i>
                Tambah User
            </a>
        </div>

        <div class="p-card">

            {{-- PENCARIAN --}}
            <form method="GET" action="{{ route('users.index') }}" class="p-toolbar" role="search">
                <label class="p-search">
                    <i data-lucide="search"></i>
                    <input type="search" name="name" value="{{ $search }}" placeholder="Cari nama atau email..."
                        autocomplete="off" aria-label="Cari user">
                </label>

                <button type="submit" class="p-btn p-btn-dark">Cari</button>

                @if ($search)
                    <a href="{{ route('users.index') }}" class="p-btn p-btn-ghost">Reset</a>
                @endif

                <span class="p-count">
                    <strong>{{ number_format($users->total(), 0, ',', '.') }}</strong> user
                </span>
            </form>

            {{-- DAFTAR --}}
            <div class="p-table-wrap">
                <table class="p-table p-table-simple">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>No. HP</th>
                            <th class="text-center">Peran</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($users as $user)
                            @php
                                [$roleLabel, $roleIcon] = $roles[$user->role] ?? $roles['user'];
                                $isMe = auth()->id() === $user->id;
                            @endphp
                            <tr>
                                <td class="cell-main">
                                    <div class="p-item">
                                        <div class="p-thumb is-avatar">
                                            @if ($user->photo ?? null)
                                                <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}"
                                                    width="52" height="52" loading="lazy" decoding="async"
>
                                            @else
                                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                            @endif
                                        </div>
                                        <div class="p-item-text">
                                            <div class="p-name">
                                                {{ $user->name }}
                                                @if ($isMe)
                                                    <span class="p-you">Anda</span>
                                                @endif
                                            </div>
                                            <small class="p-desc">
                                                {{ $user->email }}
                                                @if ($user->phone_number)
                                                    <span class="p-show-sm">· {{ $user->phone_number }}</span>
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td class="cell-hide-sm">{{ $user->phone_number ?: '-' }}</td>

                                <td class="cell-stock text-center" data-label="Peran">
                                    <span class="p-role is-{{ $user->role ?: 'user' }}">
                                        <i data-lucide="{{ $roleIcon }}"></i> {{ $roleLabel }}
                                    </span>
                                </td>

                                <td class="cell-actions">
                                    <div class="p-actions">
                                        <a href="{{ route('users.edit', $user->id) }}" class="p-icon-btn" title="Edit"
                                            aria-label="Edit {{ $user->name }}">
                                            <i data-lucide="pencil"></i>
                                        </a>

                                        {{-- akun sendiri tidak bisa dihapus dari sini (mencegah terkunci keluar) --}}
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                            data-confirm="Hapus user &quot;{{ $user->name }}&quot;? Akun ini tidak bisa login lagi.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-icon-btn is-danger"
                                                title="{{ $isMe ? 'Tidak bisa menghapus akun sendiri' : 'Hapus' }}"
                                                aria-label="Hapus {{ $user->name }}" @disabled($isMe)>
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="p-empty-row">
                                <td colspan="4">
                                    <div class="p-empty">
                                        <span class="p-empty-icon"><i data-lucide="users"></i></span>
                                        @if ($search)
                                            <h4>User tidak ditemukan</h4>
                                            <p>Tidak ada user dengan kata kunci "{{ $search }}".</p>
                                            <a href="{{ route('users.index') }}" class="p-btn p-btn-ghost">Tampilkan semua</a>
                                        @else
                                            <h4>Belum ada user</h4>
                                            <p>Tambahkan akun untuk admin atau kasir.</p>
                                            <a href="{{ route('users.create') }}" class="p-btn p-btn-primary">
                                                <i data-lucide="user-plus"></i> Tambah User
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="p-footer">
                    <span class="p-page-info">
                        Menampilkan <strong>{{ $users->firstItem() }}</strong>–<strong>{{ $users->lastItem() }}</strong>
                        dari <strong>{{ number_format($users->total(), 0, ',', '.') }}</strong>
                    </span>
                    {{ $users->withQueryString()->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>

    </div>
@endsection
