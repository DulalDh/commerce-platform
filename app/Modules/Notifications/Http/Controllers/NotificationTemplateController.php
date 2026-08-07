<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Modules\Notifications\Http\Requests\StoreNotificationTemplateRequest;
use App\Modules\Notifications\Http\Requests\UpdateNotificationTemplateRequest;
use App\Modules\Notifications\Http\Resources\NotificationTemplateResource;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class NotificationTemplateController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(NotificationTemplateResource::collection(NotificationTemplate::orderBy('event_type')->get()));
    }

    public function store(StoreNotificationTemplateRequest $request): JsonResponse
    {
        $template = NotificationTemplate::create($request->validated());

        return ApiResponse::success(new NotificationTemplateResource($template), status: 201);
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): JsonResponse
    {
        $notificationTemplate->update($request->validated());

        return ApiResponse::success(new NotificationTemplateResource($notificationTemplate));
    }

    public function destroy(NotificationTemplate $notificationTemplate): JsonResponse
    {
        $notificationTemplate->delete();

        return ApiResponse::success(status: 204);
    }
}
