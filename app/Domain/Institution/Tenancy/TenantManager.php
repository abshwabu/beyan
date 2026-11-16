<?php

namespace App\Domain\Institution\Tenancy;

use App\Domain\Institution\Models\Institution;
use Spatie\Permission\PermissionRegistrar;

class TenantManager
{
    protected ?Institution $tenant = null;

    protected bool $bypassed = false;

    protected bool $strict = false;

    protected bool $resolvingUser = false;

    /**
     * Set the current tenant institution.
     */
    public function setTenant(?Institution $tenant): self
    {
        $this->tenant = $tenant;

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant?->id);
        }

        return $this;
    }

    /**
     * Get the current tenant institution.
     */
    public function getTenant(): ?Institution
    {
        if ($this->tenant !== null) {
            return $this->tenant;
        }

        if ($this->bypassed || $this->resolvingUser) {
            return null;
        }

        $this->resolvingUser = true;

        try {
            // If not explicitly set and not bypassed, resolve from authenticated user
            if (auth()->check()) {
                $user = auth()->user();
                if ($user && isset($user->institution_id)) {
                    $this->tenant = $user->institution;
                }
            }
        } finally {
            $this->resolvingUser = false;
        }

        return $this->tenant;
    }

    /**
     * Get the current tenant ID.
     */
    public function getTenantId(): ?string
    {
        if ($this->tenant !== null) {
            return $this->tenant->id;
        }

        if ($this->bypassed || $this->resolvingUser) {
            return null;
        }

        $this->resolvingUser = true;

        try {
            if (auth()->check()) {
                $user = auth()->user();
                if ($user && isset($user->institution_id)) {
                    return $user->institution_id;
                }
            }
        } finally {
            $this->resolvingUser = false;
        }

        return null;
    }

    /**
     * Determine if a tenant is currently bound.
     */
    public function hasTenant(): bool
    {
        return $this->getTenantId() !== null;
    }

    /**
     * Clear the current tenant context.
     */
    public function clear(): self
    {
        $this->tenant = null;
        $this->bypassed = false;
        $this->strict = false;
        $this->resolvingUser = false;

        return $this;
    }

    /**
     * Enable or disable strict tenant scoping.
     */
    public function setStrict(bool $strict = true): self
    {
        $this->strict = $strict;

        return $this;
    }

    /**
     * Determine if strict tenant scoping is enabled.
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * Determine if tenant scoping is currently bypassed.
     */
    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    /**
     * Temporarily execute a callback without tenant scoping.
     */
    public function withoutTenantScope(callable $callback): mixed
    {
        $previousBypassed = $this->bypassed;
        $this->bypassed = true;

        try {
            return $callback();
        } finally {
            $this->bypassed = $previousBypassed;
        }
    }

    /**
     * Temporarily execute a callback under a specific tenant.
     */
    public function withTenant(Institution $tenant, callable $callback): mixed
    {
        $previousTenant = $this->tenant;
        $this->setTenant($tenant);

        try {
            return $callback($tenant);
        } finally {
            $this->setTenant($previousTenant);
        }
    }
}
