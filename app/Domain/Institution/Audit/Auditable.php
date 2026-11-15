<?php

namespace App\Domain\Institution\Audit;

use App\Domain\Institution\Audit\Models\AuditLog;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * Boot the trait to listen for Eloquent mutation events.
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAuditEntry('created', null, $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            $hidden = array_merge($model->getHidden(), ['password', 'remember_token', 'updated_at']);
            $changes = Arr::except($changes, $hidden);

            if (! empty($changes)) {
                $old = Arr::only($model->getOriginal(), array_keys($changes));
                $model->writeAuditEntry('updated', $old, $changes);
            }
        });

        static::deleted(function ($model) {
            $model->writeAuditEntry('deleted', $model->getAuditableAttributes(), null);
        });
    }

    /**
     * Explicitly record an audit log entry for this model (e.g. role change, permission edit).
     */
    public function recordAudit(string $action, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        return $this->writeAuditEntry($action, $oldValues, $newValues);
    }

    /**
     * Persist an audit log entry within the active database transaction.
     */
    protected function writeAuditEntry(string $action, ?array $oldValues, ?array $newValues): AuditLog
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $institutionId = $this->institution_id
            ?? ($this instanceof Institution ? $this->id : null)
            ?? $tenantManager->getTenantId();

        $userId = auth()->id();
        $ip = Request::ip();
        $userAgent = Request::userAgent();

        return AuditLog::withoutInstitutionScope()->create([
            'institution_id' => $institutionId,
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => (string) $this->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Attributes safe for audit storage.
     */
    public function getAuditableAttributes(): array
    {
        $hidden = array_merge($this->getHidden(), ['password', 'remember_token']);

        return Arr::except($this->attributesToArray(), $hidden);
    }
}
