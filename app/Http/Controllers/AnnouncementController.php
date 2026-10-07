<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::latest('published_at')->paginate(10);

        return view('pages.announcement.index', compact('announcements'));
    }

    public function create()
    {
        return view('pages.announcement.create');
    }

    public function store(Request $request, FcmService $fcm)
    {
        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'message'   => ['required', 'string'],
            'image'     => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('announcements', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['published_at'] = now();

        $announcement = Announcement::create($validated);

        // ==================================================
        // KIRIM PUSH NOTIFICATION
        // ==================================================
        //
        // Langsung kirim ke semua member kalau info ini dibuat
        // dalam kondisi aktif.
        //
        if ($announcement->is_active) {
            $fcm->sendToAllMembers(
                $announcement->title,
                Str::limit($announcement->message, 120),
                [
                    'type' => 'announcement',
                    'announcement_id' => (string) $announcement->id,
                ]
            );
        }

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Informasi berhasil ditambahkan' . ($announcement->is_active ? ' dan notifikasi terkirim ke member.' : '.'));
    }

    public function edit(Announcement $announcement)
    {
        return view('pages.announcement.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement, FcmService $fcm)
    {
        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'message'   => ['required', 'string'],
            'image'     => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($announcement->image) {
                Storage::disk('public')->delete($announcement->image);
            }

            $validated['image'] = $request->file('image')->store('announcements', 'public');
        } else {
            unset($validated['image']);
        }

        $wasActive = $announcement->is_active;
        $validated['is_active'] = $request->boolean('is_active');

        $announcement->update($validated);

        // ==================================================
        // KIRIM PUSH NOTIFICATION
        // ==================================================
        //
        // Cuma kirim notifikasi baru kalau statusnya BARU SAJA
        // diaktifkan (dari nonaktif -> aktif), supaya member gak
        // di-spam notifikasi tiap kali admin edit typo kecil di
        // info yang sudah aktif sebelumnya.
        //
        if (!$wasActive && $announcement->is_active) {
            $fcm->sendToAllMembers(
                $announcement->title,
                Str::limit($announcement->message, 120),
                [
                    'type' => 'announcement',
                    'announcement_id' => (string) $announcement->id,
                ]
            );
        }

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Informasi berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement)
    {
        if ($announcement->image) {
            Storage::disk('public')->delete($announcement->image);
        }

        $announcement->delete();

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Informasi berhasil dihapus.');
    }
}
