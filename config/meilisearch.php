<?php

return [
    'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
    'key' => env('MEILISEARCH_KEY'),

    // Single combined index spanning products + services, distinguished by
    // a "type" field on each document - "a single search endpoint querying
    // an index spanning products + services" per the roadmap, rather than
    // two separate indices the client would have to query and merge itself.
    'index' => env('MEILISEARCH_MARKETPLACE_INDEX', 'marketplace'),
];
