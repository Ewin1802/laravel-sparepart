@extends('layouts.app')

@section('title', 'Member')

@section('content')
    @php $search = request('name'); @endphp

    <div class="crud-page">

        {{-- HEADER --}}
        <div class="p-header">
            <div>
                <span class="p-eyebrow">Pelanggan</span>
                <h1>Member</h1>
                <p>Kelola member, kartu barcode, diskon khusus, dan stamp loyalitas.</p>
            </div>

            <a href="{{ route('members.create') }}" class="p-btn p-btn-primary">
                <i data-lucide="user-plus"></i>
                Tambah Member
            </a>
        </div>

        {{-- Pesan sukses tampil sebagai notifikasi (toast) dari layout --}}

        <div class="p-card">

            {{-- PENCARIAN --}}
            <form method="GET" action="{{ route('members.index') }}" class="p-toolbar" role="search">
                <label class="p-search">
                    <i data-lucide="search"></i>
                    <input type="search" name="name" value="{{ $search }}"
                        placeholder="Cari nama, email, atau kode barcode..." autocomplete="off"
                        aria-label="Cari member">
                </label>

                <button type="submit" class="p-btn p-btn-dark">Cari</button>

                @if ($search)
                    <a href="{{ route('members.index') }}" class="p-btn p-btn-ghost">Reset</a>
                @endif

                <span class="p-count">
                    <strong>{{ number_format($members->total(), 0, ',', '.') }}</strong> member
                </span>
            </form>

            {{-- DAFTAR — tabel di layar lebar, kartu di layar sempit --}}
            <div class="p-table-wrap">
                <table class="p-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>No. HP</th>
                            <th>Kode</th>
                            <th>Stamp</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($members as $member)
                            @php
                                $name = $member->user->name ?? '-';
                                $target = max(1, (int) $member->stamp_target);
                                $count = (int) $member->stamp_count;
                                $pct = min(100, round($count / $target * 100));
                            @endphp
                            <tr>
                                <td class="cell-main">
                                    <div class="p-item">
                                        <div class="p-thumb is-avatar">{{ strtoupper(mb_substr($name, 0, 1)) }}</div>
                                        <div class="p-item-text">
                                            <div class="p-name">{{ $name }}</div>
                                            <small class="p-desc">
                                                {{ $member->user->email ?? '-' }}
                                                @if ($member->user->phone_number ?? null)
                                                    <span class="p-show-sm">· {{ $member->user->phone_number }}</span>
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td class="cell-hide-sm">{{ $member->user->phone_number ?? '-' }}</td>

                                <td class="cell-category" data-label="Kode">
                                    <span class="p-code">{{ $member->code }}</span>
                                </td>

                                <td class="cell-stock" data-label="Stamp">
                                    <div class="p-progress {{ $count >= $target ? 'is-full' : '' }}"
                                        title="{{ $count }} dari {{ $target }} stamp">
                                        <span class="p-progress-bar"><span style="width: {{ $pct }}%"></span></span>
                                        {{ $count }}/{{ $target }}
                                    </div>
                                </td>

                                <td class="cell-price text-end" data-label="Diskon">
                                    <strong class="p-price">{{ $member->discount_label }}</strong>
                                </td>

                                <td class="cell-status text-center" data-label="Status">
                                    <span class="p-status {{ $member->is_active ? 'is-on' : 'is-off' }}">
                                        {{ $member->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td class="cell-actions">
                                    <div class="p-actions">
                                        <a href="{{ route('members.edit', $member->id) }}" class="p-icon-btn"
                                            title="Edit" aria-label="Edit {{ $name }}">
                                            <i data-lucide="pencil"></i>
                                        </a>

                                        <form action="{{ route('members.destroy', $member->id) }}" method="POST"
                                            data-confirm="Hapus member &quot;{{ $name }}&quot;? Stamp yang terkumpul ikut terhapus.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-icon-btn is-danger" title="Hapus"
                                                aria-label="Hapus {{ $name }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="p-empty-row">
                                <td colspan="7">
                                    <div class="p-empty">
                                        <span class="p-empty-icon"><i data-lucide="id-card"></i></span>
                                        @if ($search)
                                            <h4>Member tidak ditemukan</h4>
                                            <p>Tidak ada member dengan kata kunci "{{ $search }}".</p>
                                            <a href="{{ route('members.index') }}" class="p-btn p-btn-ghost">Tampilkan semua</a>
                                        @else
                                            <h4>Belum ada member</h4>
                                            <p>Daftarkan pelanggan setia atau bengkel langganan sebagai member.</p>
                                            <a href="{{ route('members.create') }}" class="p-btn p-btn-primary">
                                                <i data-lucide="user-plus"></i> Tambah Member
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($members->hasPages())
                <div class="p-footer">
                    <span class="p-page-info">
                        Menampilkan <strong>{{ $members->firstItem() }}</strong>–<strong>{{ $members->lastItem() }}</strong>
                        dari <strong>{{ number_format($members->total(), 0, ',', '.') }}</strong>
                    </span>
                    {{ $members->withQueryString()->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>

    </div>
@endsection
