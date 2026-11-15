<?php

namespace Database\Factories;

use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Institution\Models\Institution>
 */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Madrasa';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.strtolower(Str::random(5)),
            'logo' => null,
            'contact' => [
                'email' => fake()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->address(),
            ],
            'mode' => fake()->randomElement(['online', 'physical', 'hybrid']),
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => fake()->randomElement(['en', 'am', 'om', 'ar']),
            'status' => 'active',
            'subscription_plan' => 'pilot',
            'subscription_ends_at' => now()->addYear(),
        ];
    }

    public function online(): static
    {
        return $this->state(fn () => ['mode' => 'online']);
    }

    public function physical(): static
    {
        return $this->state(fn () => ['mode' => 'physical']);
    }

    public function hybrid(): static
    {
        return $this->state(fn () => ['mode' => 'hybrid']);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
