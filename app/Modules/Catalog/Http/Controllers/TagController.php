<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\StoreTagRequest;
use App\Modules\Catalog\Http\Requests\UpdateTagRequest;
use App\Modules\Catalog\Http\Resources\TagResource;
use App\Modules\Catalog\Models\Tag;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TagController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(TagResource::collection(Tag::orderBy('name')->get()));
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = Tag::create($request->validated());

        return ApiResponse::success(new TagResource($tag), status: 201);
    }

    public function show(Tag $tag): JsonResponse
    {
        return ApiResponse::success(new TagResource($tag));
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $tag->update($request->validated());

        return ApiResponse::success(new TagResource($tag));
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return ApiResponse::success(status: 204);
    }
}
