<?php

namespace App\Models\Traits;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

trait OrganizationProjectScope
{
    public static function bootOrganizationProjectScope(): void
    {
        static::addGlobalScope('organization_project', function (Builder $builder) {
            $organization = app('currentOrganization');

            if (! $organization instanceof Organization) {
                return;
            }

            $builder->whereHas('project', function (Builder $query) use ($organization) {
                $query->where('organization_id', $organization->id);
            });
        });
    }

    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->withoutGlobalScope('organization_project')
            ->whereHas('project', function (Builder $subQuery) use ($organization) {
                $subQuery->where('organization_id', $organization->id);
            });
    }
}
