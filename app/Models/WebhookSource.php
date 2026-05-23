<?php

namespace App\Models;

use App\Models\Traits\OrganizationProjectScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookSource extends Model
{
    use HasFactory, OrganizationProjectScope;

    protected $fillable = [
        'project_id',
        'source_key',
        'source_type',
        'name',
        'signing_secret',
        'event_mappings',
        'is_active',
    ];

    protected $casts = [
        'event_mappings' => 'array',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
