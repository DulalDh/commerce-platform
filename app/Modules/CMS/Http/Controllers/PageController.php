<?php

namespace App\Modules\CMS\Http\Controllers;

use App\Modules\CMS\Http\Requests\StorePageRequest;
use App\Modules\CMS\Http\Requests\UpdatePageRequest;
use App\Modules\CMS\Http\Resources\PageResource;
use App\Modules\CMS\Http\Resources\PageVersionResource;
use App\Modules\CMS\Models\Page;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(PageResource::collection(Page::orderBy('title')->get()));
    }

    public function store(StorePageRequest $request): JsonResponse
    {
        $page = Page::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return ApiResponse::success(new PageResource($page), status: 201);
    }

    public function show(Page $page): JsonResponse
    {
        return ApiResponse::success(new PageResource($page));
    }

    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        // Snapshot before overwriting - "versioning optional" per the
        // roadmap, implemented as a simple append-only history rather than
        // full diff/restore tooling.
        $page->versions()->create([
            'title' => $page->title,
            'content' => $page->content,
            'created_by' => $request->user()->id,
        ]);

        $page->update($request->validated());

        return ApiResponse::success(new PageResource($page));
    }

    public function versions(Page $page): JsonResponse
    {
        return ApiResponse::success(PageVersionResource::collection($page->versions));
    }

    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return ApiResponse::success(status: 204);
    }

    /**
     * Public: published pages, platform-wide (tenant_id null) or for a
     * specific tenant if ?tenant_id= is given.
     */
    public function public(Request $request): JsonResponse
    {
        $pages = Page::withoutGlobalScopes()
            ->where('status', 'published')
            ->when(
                $request->filled('tenant_id'),
                fn ($q) => $q->where('tenant_id', $request->tenant_id),
                fn ($q) => $q->whereNull('tenant_id')
            )
            ->get();

        return ApiResponse::success(PageResource::collection($pages));
    }
}
