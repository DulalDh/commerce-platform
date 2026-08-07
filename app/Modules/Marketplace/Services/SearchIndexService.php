<?php

namespace App\Modules\Marketplace\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Services\Models\Service;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;

/**
 * Wraps the raw Meilisearch client (not Scout - kept as a thin, explicit
 * service so indexing is triggered deliberately from the places that
 * actually change marketplace visibility, not via magic model observers).
 * One combined index spanning products + services, distinguished by a
 * "type" field, so the search endpoint queries a single index instead of
 * merging results from two.
 */
class SearchIndexService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client(config('meilisearch.host'), config('meilisearch.key'));
    }

    public function index(): Indexes
    {
        return $this->client->index(config('meilisearch.index'));
    }

    public function configureIndex(): void
    {
        $index = $this->index();
        $index->updateFilterableAttributes(['type', 'tenant_id', 'category', 'price']);
        $index->updateSearchableAttributes(['name', 'description', 'category']);
        $index->updateSortableAttributes(['price', 'created_at']);
    }

    public function indexProduct(Product $product): void
    {
        if ($product->status !== 'published' || ! $product->published_to_marketplace) {
            $this->removeDocument('product', (string) $product->id);

            return;
        }

        $this->index()->addDocuments([[
            'id' => "product-{$product->id}",
            'type' => 'product',
            'tenant_id' => $product->tenant_id,
            'record_id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category?->name,
            'price' => (float) ($product->sale_price ?? $product->price),
            'created_at' => $product->created_at?->timestamp,
        ]], 'id');
    }

    public function indexService(Service $service): void
    {
        if ($service->status !== 'published') {
            $this->removeDocument('service', (string) $service->id);

            return;
        }

        $this->index()->addDocuments([[
            'id' => "service-{$service->id}",
            'type' => 'service',
            'tenant_id' => $service->tenant_id,
            'record_id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'category' => $service->category?->name,
            'price' => (float) $service->price,
            'created_at' => $service->created_at?->timestamp,
        ]], 'id');
    }

    public function removeDocument(string $type, string $recordId): void
    {
        $this->index()->deleteDocument("{$type}-{$recordId}");
    }

    /**
     * @return array{hits: array, total: int}
     */
    public function search(string $query, ?string $type = null, ?string $tenantId = null, int $page = 1, int $perPage = 20): array
    {
        $filters = [];
        if ($type) {
            $filters[] = "type = {$type}";
        }
        if ($tenantId) {
            $filters[] = "tenant_id = {$tenantId}";
        }

        $results = $this->index()->search($query, [
            'filter' => implode(' AND ', $filters) ?: null,
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ]);

        return [
            'hits' => $results->getHits(),
            'total' => $results->getEstimatedTotalHits(),
        ];
    }
}
