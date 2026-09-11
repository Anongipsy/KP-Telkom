<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse|RedirectResponse
    {
        // Ensure user can only mark their own notifications
        if ($notification->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $this->notificationService->markAsRead($notification->id, $notification->user_id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
                'unread_count' => $this->notificationService->getUnreadCount($request->user()->id),
            ]);
        }

        return back()->with('status', 'Notifikasi berhasil ditandai sudah dibaca.');
    }

    /**
     * Mark all unread notifications for current user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $this->notificationService->markAllAsRead($request->user()->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
                'unread_count' => 0,
            ]);
        }

        return back()->with('status', 'Semua notifikasi berhasil ditandai sudah dibaca.');
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, Notification $notification): JsonResponse|RedirectResponse
    {
        if ($notification->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil dihapus.',
                'unread_count' => $this->notificationService->getUnreadCount($request->user()->id),
            ]);
        }

        return back()->with('status', 'Notifikasi berhasil dihapus.');
    }

    /**
     * Delete all notifications for current user.
     */
    public function clearAll(Request $request): JsonResponse|RedirectResponse
    {
        Notification::where('user_id', $request->user()->id)->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Semua notifikasi berhasil dibersihkan.',
                'unread_count' => 0,
            ]);
        }

        return back()->with('status', 'Semua notifikasi berhasil dibersihkan.');
    }
}
