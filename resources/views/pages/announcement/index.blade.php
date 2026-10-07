@extends('layouts.app')

@section('title', 'Informasi Member')

@section('content')
    @php
        $total = $announcements->total();
    @endphp

    <div class="crud-page">

        {{-- HEADER --}}
        <div class="p-header">
            <div>
                <span class="p-eyebrow">Aplikasi Member</span>
                <h1>Informasi</h1>
                <p>Info & promo untuk aplikasi member. Yang diaktifkan otomatis mengirim notifikasi ke semua member.</p>
            </div>

            <a href="{{ route('announcements.create') }}" class="p-btn p-btn-primary">
                <i data-lucide="plus"></i>
                Tambah Informasi
            </a>
        </div>

        <div class="p-card">
            <div class="p-toolbar">
                <label class="p-search">
                    <i data-lucide="search"></i>
                    <input type="search" placeholder="Cari judul atau isi pesan..." autocomplete="off"
                        aria-label="Cari informasi" data-table-filter="#announcementTable">
                </label>

                <span class="p-count">
                    <strong data-filter-count>{{ $announcements->count() }}</strong> dari {{ $total }} informasi
                </span>
            </div>

            <div class="p-table-wrap">
                <table class="p-table p-table-announce" id="announcementTable">
                    <thead>
                        <tr>
                            <th>Informasi</th>
                            <th>Status</th>
                            <th>Dipublikasikan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($announcements as $item)
                            <tr data-filter-row>
                                <td class="cell-main">
                                    <div class="p-item">
                                        <div class="p-thumb is-banner">
                                            @if ($item->image)
                                                <img src="{{ $item->image_url }}" alt="" width="88" height="52"
                                                    loading="lazy" decoding="async">
                                            @else
                                                <i data-lucide="megaphone"></i>
                                            @endif
                                        </div>
                                        <div class="p-item-text">
                                            <div class="p-name">{{ $item->title }}</div>
                                            <small class="p-desc">{{ \Illuminate\Support\Str::limit($item->message, 140) }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td class="cell-status">
                                    <span class="p-status {{ $item->is_active ? 'is-on' : 'is-off' }}">
                                        {{ $item->is_active ? 'Aktif' : 'Draf' }}
                                    </span>
                                </td>

                                <td class="cell-date">
                                    @if ($item->published_at)
                                        <span class="p-date">
                                            <strong>{{ $item->published_at->translatedFormat('d M Y') }}</strong>
                                            <small>{{ $item->published_at->format('H:i') }}</small>
                                        </span>
                                    @else
                                        <span class="p-sub">Belum dikirim</span>
                                    @endif
                                </td>

                                <td class="cell-actions">
                                    <div class="p-actions">
                                        <a href="{{ route('announcements.edit', $item->id) }}" class="p-icon-btn"
                                            title="Edit" aria-label="Edit {{ $item->title }}">
                                            <i data-lucide="pencil"></i>
                                        </a>

                                        <form action="{{ route('announcements.destroy', $item->id) }}" method="POST"
                                            data-confirm="Hapus informasi &quot;{{ $item->title }}&quot;?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-icon-btn is-danger" title="Hapus"
                                                aria-label="Hapus {{ $item->title }}">
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
                                        <span class="p-empty-icon"><i data-lucide="megaphone"></i></span>
                                        <h4>Belum ada informasi</h4>
                                        <p>Contoh: promo ganti oli akhir pekan, stok ban baru datang, jam buka libur.</p>
                                        <a href="{{ route('announcements.create') }}" class="p-btn p-btn-primary">
                                            <i data-lucide="plus"></i> Tambah Informasi
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        <tr class="p-empty-row" data-filter-empty hidden>
                            <td colspan="4">
                                <div class="p-empty">
                                    <span class="p-empty-icon"><i data-lucide="search-x"></i></span>
                                    <h4>Informasi tidak ditemukan</h4>
                                    <p>Pencarian hanya di halaman ini. Coba kata kunci lain.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if ($announcements->hasPages())
                <div class="p-footer">
                    <span class="p-page-info">
                        Menampilkan <strong>{{ $announcements->firstItem() }}</strong>–<strong>{{ $announcements->lastItem() }}</strong>
                        dari <strong>{{ $total }}</strong>
                    </span>
                    {{ $announcements->withQueryString()->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>

    </div>
@endsection
