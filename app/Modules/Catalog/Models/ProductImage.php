<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use BelongsToTenant, HasUuids;

    public $timestamps = true;

    protected $fillable = ['tenant_id', 'product_id', 'path', 'position'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
