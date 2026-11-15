<?php

namespace App\Domain\Institution\Tenancy;

use App\Domain\Institution\Models\Institution;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToInstitution
{
    /**
     * Boot the trait to apply the tenant global scope and creation listener.
     */
    public static function bootBelongsToInstitution(): void
    {
        static::addGlobalScope(new InstitutionScope);

        static::creating(function ($model) {
            /** @var TenantManager $tenantManager */
            $tenantManager = app(TenantManager::class);

            if ($tenantManager->hasTenant()) {
                if (empty($model->institution_id)) {
                    $model->institution_id = $tenantManager->getTenantId();
                } elseif (! $tenantManager->isBypassed() && $model->institution_id !== $tenantManager->getTenantId()) {
                    throw new DomainException('Cross-tenant data manipulation is prohibited.');
                }
            }
        });
    }

    /**
     * Get the institution that owns the model.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    /**
     * Scope a query to ignore the tenant global scope.
     */
    public function scopeWithoutInstitutionScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(InstitutionScope::class);
    }

    /**
     * Scope a query to a specific institution ID.
     */
    public function scopeForInstitution(Builder $query, string $institutionId): Builder
    {
        return $query->withoutGlobalScope(InstitutionScope::class)
            ->where($this->qualifyColumn('institution_id'), '=', $institutionId);
    }
}
