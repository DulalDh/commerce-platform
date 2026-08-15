<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk(config('filesystems.default'));
        $variants = $this->variants ?? [];

        $variantUrls = collect($variants)
            ->mapWithKeys(fn (string $path, string $name) => [$name => $disk->url($path)])
            ->all();

        return [
            'id' => $this->id,
            'url' => $variantUrls['large'] ?? $disk->url($this->path),
            'is_primary' => (bool) $this->is_primary,
            'variants' => $variantUrls ?: null,
            'position' => $this->position,
        ];
    }
}
