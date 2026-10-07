<div class="users-card">

    <div class="users-header">
        <div>
            <h3>Pengguna Terbaru</h3>
            <p>Akun yang baru ditambahkan.</p>
        </div>

        @if (Route::has('users.index'))
            <a href="{{ route('users.index') }}" class="users-link">
                Lihat semua <i data-lucide="arrow-right"></i>
            </a>
        @endif
    </div>

    <div class="users-list">
        @forelse ($recentUsers ?? [] as $user)
            <div class="user-item">
                <div class="user-avatar">
                    @if ($user->photo)
                        {{-- width/height + lazy: mencegah layout bergeser & hemat bandwidth --}}
                        <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}"
                            width="48" height="48" loading="lazy" decoding="async">
                    @else
                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                    @endif
                </div>

                <div class="user-content">
                    <div class="user-name">{{ $user->name }}</div>
                    <div class="user-email">{{ $user->email }}</div>
                </div>

                <div class="user-right">
                    <span class="user-role">{{ ucfirst($user->role ?? 'User') }}</span>
                    <small>{{ $user->created_at?->diffForHumans() }}</small>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i data-lucide="user-round"></i>
                Belum ada pengguna.
            </div>
        @endforelse
    </div>

</div>
