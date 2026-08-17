<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_deactivate_and_reactivate_buyer_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $buyer = User::factory()->create(['role' => UserRole::Buyer]);

        $this->actingAs($admin)
            ->patch(route('admin.buyers.toggle-active', $buyer))
            ->assertRedirect();

        $this->assertFalse($buyer->fresh()->is_active);

        $this->actingAs($buyer)
            ->get(route('buyer.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->patch(route('admin.buyers.toggle-active', $buyer))
            ->assertRedirect();

        $this->assertTrue($buyer->fresh()->is_active);
    }

    public function test_inactive_buyer_cannot_log_in(): void
    {
        $buyer = User::factory()->create([
            'role' => UserRole::Buyer,
            'is_active' => false,
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'email' => $buyer->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
