<?php

namespace App\Modules\Marketplace\Jobs;

use App\Modules\Marketplace\Services\SearchIndexService;
use App\Modules\Services\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $serviceId) {}

    public function handle(SearchIndexService $search): void
    {
        $service = Service::withoutGlobalScopes()->with('category')->find($this->serviceId);

        if (! $service) {
            $search->removeDocument('service', $this->serviceId);

            return;
        }

        $search->indexService($service);
    }
}
