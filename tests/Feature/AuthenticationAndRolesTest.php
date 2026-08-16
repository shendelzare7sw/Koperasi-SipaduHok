<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_pages_use_one_buyer_profile_and_icon_password_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('buyer_type', false)
            ->assertSee('fa-eye', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('buyer_type', false)
            ->assertDontSee('Tipe pembeli')
            ->assertSee('fa-eye', false)
            ->assertSee('data-confirm=', false);

        $this->assertFalse(Schema::hasColumn('users', 'buyer_type'));
    }

    public function test_public_registration_creates_a_single_buyer_role(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Wali Siswa',
            'email' => 'wali@example.test',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('buyer.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'wali@example.test',
            'role' => UserRole::Buyer->value,
        ]);
    }

    public function test_role_middleware_separates_admin_and_buyer_areas(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($buyer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('cart.index'))->assertForbidden();
    }

    public function test_admin_can_open_unified_seller_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Dashboard Koperasi');
        $this->actingAs($admin)->get(route('admin.buyers.index'))->assertOk()->assertSee('Akun Pembeli');
        $this->actingAs($admin)->get(route('admin.reports.sales'))->assertOk()->assertSee('Laporan Penjualan');
    }

    public function test_buyer_can_open_buyer_dashboard(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('buyer.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Pembeli')
            ->assertSee($buyer->name)
            ->assertSee('data-confirm=', false);
    }
}
