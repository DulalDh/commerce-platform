<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Modules\Notifications\Http\Resources\NotificationResource;
use App\Modules\Notifications\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(NotificationResource::collection($notifications));
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return ApiResponse::error('Not found.', [], 404);
        }

        $notification->update(['read_at' => now()]);

        return ApiResponse::success(new NotificationResource($notification));
    }
}
