<?php

namespace App\Console\Commands;

use App\Modules\Marketplace\Services\SearchIndexService;
use Illuminate\Console\Command;

class SetupMarketplaceSearchIndex extends Command
{
    protected $signature = 'search:setup-index';

    protected $description = 'Configure the Meilisearch marketplace index (filterable/searchable/sortable attributes)';

    public function handle(SearchIndexService $search): int
    {
        $search->configureIndex();
        $this->info('Marketplace search index configured.');

        return self::SUCCESS;
    }
}
