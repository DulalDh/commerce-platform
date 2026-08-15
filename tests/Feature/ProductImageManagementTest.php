<?php

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    config(['filesystems.default' => 'local']);

    $this->tenant = Tenant::create([
        'name' => 'Image Test Co', 'type' => 'merchant', 'status' => 'active', 'subdomain' => 'image-test-'.uniqid(),
    ]);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Owner', 'email' => 'owner@image-test.test',
        'password' => bcrypt('password'),
    ]);
    $this->product = Product::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Widget', 'slug' => 'widget-'.uniqid(), 'price' => 10, 'status' => 'draft',
    ]);
});

test('uploading the first image generates variants and sets it primary', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 800, 800);

    $response = $this->actingAs($this->owner, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/images", ['image' => $file])
        ->assertCreated();

    $response->assertJsonPath('data.is_primary', true);
    $response->assertJsonPath('data.variants.large', fn ($url) => is_string($url));

    $image = ProductImage::first();
    expect($image->is_primary)->toBeTrue();
    expect($image->variants)->toHaveKeys(['original', 'large', 'medium', 'small', 'thumbnail']);
    foreach ($image->variants as $path) {
        Storage::disk('local')->assertExists($path);
    }
});

test('second uploaded image is not primary', function () {
    $this->actingAs($this->owner, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/images", ['image' => UploadedFile::fake()->image('a.jpg')])
        ->assertCreated();

    $second = $this->actingAs($this->owner, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/images", ['image' => UploadedFile::fake()->image('b.jpg')])
        ->assertCreated();

    $second->assertJsonPath('data.is_primary', false);
});

test('deleting the primary image removes files and promotes the next image', function () {
    $first = $this->actingAs($this->owner, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/images", ['image' => UploadedFile::fake()->image('a.jpg')])
        ->json('data');

    $second = $this->actingAs($this->owner, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/images", ['image' => UploadedFile::fake()->image('b.jpg')])
        ->json('data');

    $firstVariants = ProductImage::find($first['id'])->variants;

    $this->actingAs($this->owner, 'sanctum')
        ->deleteJson("/api/v1/products/{$this->product->id}/images/{$first['id']}")
        ->assertNoContent();

    expect(ProductImage::find($first['id']))->toBeNull();
    foreach ($firstVariants as $path) {
        Storage::disk('local')->assertMissing($path);
    }

    $remaining = ProductImage::find($second['id']);
    expect($remaining->is_primary)->toBeTrue();
});

test('an image can be explicitly set as primary', function () {
    $first = $this->actingAs($this->owner, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/images", ['image' => UploadedFile::fake()->image('a.jpg')])
        ->json('data');

    $second = $this->actingAs($this->owner, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/images", ['image' => UploadedFile::fake()->image('b.jpg')])
        ->json('data');

    $this->actingAs($this->owner, 'sanctum')
        ->patchJson("/api/v1/products/{$this->product->id}/images/{$second['id']}/primary")
        ->assertOk()
        ->assertJsonPath('data.is_primary', true);

    expect(ProductImage::find($first['id'])->is_primary)->toBeFalse();
    expect(ProductImage::find($second['id'])->is_primary)->toBeTrue();
});
