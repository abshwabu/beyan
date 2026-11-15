<?php

namespace App\Domain\Institution\Models;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Term;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Platform\Models\PlatformPayment;
use App\Domain\Platform\Models\Subscription;
use Carbon\CarbonInterface;
use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Institution extends Model
{
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected $table = 'institutions';

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'contact',
        'mode',
        'timezone',
        'currency',
        'locale',
        'country',
        'status',
        'subscription_plan',
        'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'contact' => 'array',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InstitutionFactory
    {
        return InstitutionFactory::new();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'institution_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'institution_id');
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'institution_id')->latestOfMany();
    }

    public function platformPayments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class, 'institution_id');
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class, 'institution_id');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class, 'institution_id');
    }

    public function currentAcademicYear(): ?AcademicYear
    {
        return $this->academicYears()->where('is_current', true)->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Convert a UTC date or datetime to the institution's timezone.
     */
    public function toTimezone(?CarbonInterface $date = null, ?string $format = null): ?string
    {
        if (! $date) {
            return null;
        }

        $carbon = Carbon::instance($date)->setTimezone($this->timezone ?: 'Africa/Addis_Ababa');

        return $format ? $carbon->format($format) : $carbon->toIso8601String();
    }
}
