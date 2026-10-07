<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * GET /api/member/announcements/latest
     *
     * Dipakai buat kartu informasi di Home aplikasi member.
     */
    public function latest(Request $request)
    {
        $limit = (int) $request->input('limit', 5);

        if ($limit < 1) {
            $limit = 5;
        }

        $announcements = Announcement::where('is_active', true)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(function (Announcement $item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'message' => $item->message,
                    'image' => $item->image,
                    'published_at' => $item->published_at,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $announcements,
        ]);
    }
}
