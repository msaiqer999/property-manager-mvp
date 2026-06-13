<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesOrganization
{
    protected function organizationId(): int
    {
        return auth()->user()->organization_id;
    }

    protected function forOrganization(Builder $query): Builder
    {
        return $query->where('organization_id', $this->organizationId());
    }
}
