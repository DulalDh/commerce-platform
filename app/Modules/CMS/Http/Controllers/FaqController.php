<?php

namespace App\Modules\CMS\Http\Controllers;

use App\Modules\CMS\Http\Requests\StoreFaqRequest;
use App\Modules\CMS\Http\Requests\UpdateFaqRequest;
use App\Modules\CMS\Http\Resources\FaqResource;
use App\Modules\CMS\Models\Faq;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(FaqResource::collection(Faq::orderBy('position')->get()));
    }

    public function store(StoreFaqRequest $request): JsonResponse
    {
        $faq = Faq::create($request->validated());

        return ApiResponse::success(new FaqResource($faq), status: 201);
    }

    public function show(Faq $faq): JsonResponse
    {
        return ApiResponse::success(new FaqResource($faq));
    }

    public function update(UpdateFaqRequest $request, Faq $faq): JsonResponse
    {
        $faq->update($request->validated());

        return ApiResponse::success(new FaqResource($faq));
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();

        return ApiResponse::success(status: 204);
    }

    public function public(Request $request): JsonResponse
    {
        $faqs = Faq::withoutGlobalScopes()
            ->where('is_active', true)
            ->when(
                $request->filled('tenant_id'),
                fn ($q) => $q->where('tenant_id', $request->tenant_id),
                fn ($q) => $q->whereNull('tenant_id')
            )
            ->orderBy('position')
            ->get();

        return ApiResponse::success(FaqResource::collection($faqs));
    }
}
