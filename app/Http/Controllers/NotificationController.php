<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $targetUrl = $notification->data['url'] ?? route('notifications.index');
        
        // If the URL exists, extract only the path to avoid 404 errors
        // caused by mismatched hosts (e.g., if .env APP_URL is localhost but user accesses via IP or .test)
        if (isset($notification->data['url'])) {
            $parsedUrl = parse_url($targetUrl);
            $path = $parsedUrl['path'] ?? '';
            $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
            $targetUrl = $path . $query;
        }

        return redirect($targetUrl);
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return redirect()->back()->with('success', 'Semua notifikasi ditandai sebagai terbaca.');
    }

    public function destroy($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();

        return redirect()->back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    public function clearAll()
    {
        auth()->user()->notifications()->delete();
        return redirect()->back()->with('success', 'Seluruh riwayat notifikasi dikosongkan.');
    }
}
