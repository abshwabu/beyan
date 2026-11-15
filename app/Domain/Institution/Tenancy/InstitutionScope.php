<?php

namespace App\Domain\Institution\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class InstitutionScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        if ($tenantManager->isBypassed()) {
            return;
        }

        if ($tenantManager->hasTenant()) {
            $builder->where($model->qualifyColumn('institution_id'), '=', $tenantManager->getTenantId());
        } elseif ($tenantManager->isStrict()) {
            // Strict isolation: if in a strict tenant context but tenant is not set, block access
            $builder->whereRaw('1 = 0');
        }
    }
}
