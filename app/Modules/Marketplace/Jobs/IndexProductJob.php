<?php

namespace App\Modules\Marketplace\Jobs;

use App\Modules\Catalog\Models\Product;
use App\Modules\Marketplace\Services\SearchIndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $productId) {}

    public function handle(SearchIndexService $search): void
    {
        $product = Product::withoutGlobalScopes()->with('category')->find($this->productId);

        if (! $product) {
            $search->removeDocument('product', $this->productId);

            return;
        }

        $search->indexProduct($product);
    }
}
