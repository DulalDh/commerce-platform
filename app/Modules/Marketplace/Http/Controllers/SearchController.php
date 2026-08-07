<?php

namespace App\Modules\Marketplace\Http\Controllers;

use App\Modules\Marketplace\Http\Requests\SearchRequest;
use App\Modules\Marketplace\Services\SearchIndexService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SearchController
{
    public function __construct(protected SearchIndexService $search) {}

    public function index(SearchRequest $request): JsonResponse
    {
        $results = $this->search->search(
            $request->q,
            $request->type,
            $request->tenant_id,
            (int) $request->input('page', 1),
        );

        return ApiResponse::success($results['hits'], ['total' => $results['total']]);
    }
}
