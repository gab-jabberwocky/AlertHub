<?php

namespace App\Models;

use App\Models\Traits\OrganizationProjectScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscriber extends Model
{
    use HasFactory, OrganizationProjectScope;

    protected $fillable = [
        'project_id',
        'email',
        'external_id',
        'name',
        'notification_count',
        'last_notified_at',
        'metadata',
    ];

    protected $casts = [
        'notification_count' => 'integer',
        'last_notified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
