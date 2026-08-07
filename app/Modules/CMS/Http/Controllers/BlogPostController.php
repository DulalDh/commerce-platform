<?php

namespace App\Modules\CMS\Http\Controllers;

use App\Modules\CMS\Http\Requests\StoreBlogPostRequest;
use App\Modules\CMS\Http\Requests\UpdateBlogPostRequest;
use App\Modules\CMS\Http\Resources\BlogPostResource;
use App\Modules\CMS\Models\BlogPost;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogPostController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(BlogPostResource::collection(BlogPost::orderByDesc('published_at')->get()));
    }

    public function store(StoreBlogPostRequest $request): JsonResponse
    {
        $post = BlogPost::create([...$request->validated(), 'author_id' => $request->user()->id]);

        return ApiResponse::success(new BlogPostResource($post), status: 201);
    }

    public function show(BlogPost $blogPost): JsonResponse
    {
        return ApiResponse::success(new BlogPostResource($blogPost));
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blogPost): JsonResponse
    {
        $blogPost->update($request->validated());

        return ApiResponse::success(new BlogPostResource($blogPost));
    }

    public function destroy(BlogPost $blogPost): JsonResponse
    {
        $blogPost->delete();

        return ApiResponse::success(status: 204);
    }

    public function public(Request $request): JsonResponse
    {
        $posts = BlogPost::withoutGlobalScopes()
            ->where('status', 'published')
            ->when(
                $request->filled('tenant_id'),
                fn ($q) => $q->where('tenant_id', $request->tenant_id),
                fn ($q) => $q->whereNull('tenant_id')
            )
            ->orderByDesc('published_at')
            ->get();

        return ApiResponse::success(BlogPostResource::collection($posts));
    }
}
