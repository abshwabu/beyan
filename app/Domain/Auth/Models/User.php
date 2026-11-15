<?php

namespace App\Domain\Auth\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, BelongsToInstitution, HasFactory, HasRoles, HasUlids, Notifiable;

    protected $table = 'users';

    protected string $guard_name = 'web';

    protected $fillable = [
        'institution_id',
        'name',
        'email',
        'role',
        'phone',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * Check if user is a Platform Super Admin (operating outside any institution).
     */
    public function isPlatformSuperAdmin(): bool
    {
        return empty($this->institution_id) && (
            $this->role === 'platform_super_admin' ||
            $this->hasRole('Platform Super Admin')
        );
    }

    /**
     * Check if user is Owner of their institution.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner' || $this->hasRole('Owner');
    }

    /**
     * Check if user is Teacher in their institution.
     */
    public function isTeacher(): bool
    {
        return $this->hasRole('Teacher') || $this->role === 'teacher';
    }

    /**
     * Students assigned to this teacher.
     */
    public function assignedStudents()
    {
        return $this->belongsToMany(
            Student::class,
            'student_teacher',
            'teacher_id',
            'student_id'
        )->withTimestamps();
    }

    /**
     * Assign roles with audit tracking in the current database transaction.
     */
    public function assignRolesWithAudit(array|string ...$roles): self
    {
        $oldRoles = $this->getRoleNames()->toArray();
        $this->assignRole(...$roles);
        $newRoles = $this->fresh()->getRoleNames()->toArray();

        $this->recordAudit('role_assigned', ['roles' => $oldRoles], ['roles' => $newRoles]);

        return $this;
    }

    /**
     * Sync roles with audit tracking in the current database transaction.
     */
    public function syncRolesWithAudit(array $roles): self
    {
        $oldRoles = $this->getRoleNames()->toArray();
        $this->syncRoles($roles);
        $newRoles = $this->fresh()->getRoleNames()->toArray();

        $this->recordAudit('roles_synced', ['roles' => $oldRoles], ['roles' => $newRoles]);

        return $this;
    }
}
