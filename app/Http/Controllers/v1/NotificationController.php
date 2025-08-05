<?php



// app/Http/Controllers/API/NotificationController.php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->notificationService->getAllNotifications();

        // Apply pagination if requested
        if ($request->has('per_page')) {
            $perPage = (int) $request->get('per_page', 15);
            $page = (int) $request->get('page', 1);
            $offset = ($page - 1) * $perPage;

            $notifications = collect($result['data']);
            $paginatedNotifications = $notifications->slice($offset, $perPage)->values();

            $result['data'] = [
                'data' => $paginatedNotifications,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $notifications->count(),
                'last_page' => ceil($notifications->count() / $perPage)
            ];
        }

        return response()->json($result);
    }

    /**
     * Get notification count
     */
    public function count(): JsonResponse
    {
        try {
            $result = $this->notificationService->getAllNotifications();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $result['total'],
                    'high_priority' => collect($result['data'])->where('priority', 'high')->count(),
                    'medium_priority' => collect($result['data'])->where('priority', 'medium')->count(),
                    'low_priority' => collect($result['data'])->where('priority', 'low')->count()
                ],
                'message' => 'Notification count retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read (placeholder)
     */
    public function markAsRead(string $id): JsonResponse
    {
        // This would typically update a notifications table
        // For now, just return success
        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read (placeholder)
     */
    public function markAllAsRead(): JsonResponse
    {
        // This would typically update all notifications for the user
        // For now, just return success
        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete notification (placeholder)
     */
    public function destroy(string $id): JsonResponse
    {
        // This would typically delete from notifications table
        // For now, just return success
        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }
}
