<?php

namespace App\Modules\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ProductImageVariantService
{
    /**
     * Width (in px) for each generated variant. The original file is stored as-is.
     */
    private const SIZES = [
        'large' => 1200,
        'medium' => 600,
        'small' => 300,
        'thumbnail' => 100,
    ];

    /**
     * Store the original file plus resized variants for a product image.
     *
     * @return array<string, string> map of variant name => stored path
     */
    public function store(UploadedFile $file, string $directory, string $disk): array
    {
        $manager = ImageManager::gd();
        $source = $manager->read($file->getRealPath());
        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        $paths = [
            'original' => $file->storeAs($directory, $this->filename('original', $extension), $disk),
        ];

        foreach (self::SIZES as $name => $width) {
            $resized = (clone $source)->scale(width: $width);
            $encoded = (string) $resized->encode();

            $path = $directory . '/' . $this->filename($name, $extension);
            Storage::disk($disk)->put($path, $encoded);
            $paths[$name] = $path;
        }

        return $paths;
    }

    /**
     * Delete every stored variant file for a product image.
     *
     * @param array<string, string>|null $variants
     */
    public function delete(?array $variants, ?string $path, string $disk): void
    {
        $files = array_values(array_filter($variants ?? []));
        if ($path) {
            $files[] = $path;
        }

        if ($files !== []) {
            Storage::disk($disk)->delete(array_unique($files));
        }
    }

    private function filename(string $variant, string $extension): string
    {
        return $variant . '-' . uniqid() . '.' . $extension;
    }
}
