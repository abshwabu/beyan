<?php

namespace Database\Factories;

use App\Domain\Auth\Models\UserInvitation;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserInvitationFactory extends Factory
{
    protected $model = UserInvitation::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'invited_by_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'roles' => ['Teacher'],
            'token' => Str::random(64),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ];
    }
}
