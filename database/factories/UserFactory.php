<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'role' => UserRole::Buyer,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Admin,
        ]);
    }

    public function identityVerified(): static
    {
        return $this->afterCreating(function (User $user) {
            $nik = fake()->unique()->numerify('################');

            $user->identityVerification()->create([
                'legal_name' => $user->name,
                'nik' => $nik,
                'nik_hash' => hash_hmac('sha256', $nik, (string) config('app.key')),
                'document_path' => 'identity-documents/testing/'.$user->id.'.jpg',
                'document_mime' => 'image/jpeg',
                'status' => IdentityVerification::STATUS_VERIFIED,
                'submitted_at' => now(),
                'reviewed_at' => now(),
            ]);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
