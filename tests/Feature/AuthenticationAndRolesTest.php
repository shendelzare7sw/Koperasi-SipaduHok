<?php

namespace Tests\Feature;

use App\Enums\BuyerType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_always_creates_a_buyer_account(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Wali Siswa',
            'email' => 'wali@example.test',
            'phone' => '081234567890',
            'buyer_type' => BuyerType::Parent->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('catalog.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'wali@example.test',
            'role' => UserRole::Buyer->value,
            'buyer_type' => BuyerType::Parent->value,
        ]);
    }

    public function test_role_middleware_separates_admin_and_buyer_areas(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($buyer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('cart.index'))->assertForbidden();
    }
}
