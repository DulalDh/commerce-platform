<?php

namespace App\Modules\Promotions\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlashSale extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['tenant_id', 'name', 'discount_percentage', 'starts_at', 'ends_at', 'status'];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'scheduled',
    ];

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
