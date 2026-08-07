<?php

namespace App\Modules\CMS\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['tenant_id', 'title', 'slug', 'content', 'status', 'created_by'];

    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return HasMany<PageVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->orderByDesc('created_at');
    }
}
