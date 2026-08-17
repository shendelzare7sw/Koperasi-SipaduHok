<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Courier;
use App\Models\IdentityVerification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_must_be_approved_before_checkout_and_ktp_is_private(): void
    {
        Storage::fake('local');
        config()->set('services.payment_gateway', 'placeholder');
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $cartItem = CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 1]);
        $buyer->addresses()->create([
            'label' => 'Rumah',
            'recipient_name' => $buyer->name,
            'phone' => $buyer->phone,
            'full_address' => 'Jl. Sekolah No. 1',
            'village' => 'Sukamaju',
            'district' => 'Cendekia',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40123',
            'is_primary' => true,
        ]);
        Courier::create(['code' => 'main', 'name' => 'Kurir Koperasi', 'fee' => 10000, 'is_active' => true]);

        $this->actingAs($buyer)
            ->get(route('checkout.create', ['items' => [$cartItem->id]]))
            ->assertRedirect(route('account.identity.edit'));

        $this->post(route('account.identity.update'), [
            'legal_name' => 'Pembeli Sesuai KTP',
            'nik' => '3273010101010001',
            'identity_document' => UploadedFile::fake()->image('ktp.webp', 1200, 760),
            'consent' => '1',
        ])->assertRedirect();

        $verification = $buyer->identityVerification()->firstOrFail();
        $raw = DB::table('identity_verifications')->where('id', $verification->id)->first();
        $this->assertSame(IdentityVerification::STATUS_PENDING, $verification->status);
        $this->assertNotSame('3273010101010001', $raw->nik);
        $this->assertNotSame('Pembeli Sesuai KTP', $raw->legal_name);
        Storage::disk('local')->assertExists($verification->document_path);
        $this->assertSame(1, $admin->notifications()->count());

        $this->actingAs($otherBuyer)->get(route('identity.document', $verification))->assertForbidden();
        $this->actingAs($buyer)->get(route('identity.document', $verification))->assertOk();
        $this->actingAs($admin)->get(route('identity.document', $verification))->assertOk();

        $this->patch(route('admin.buyers.identity.approve', $buyer))->assertRedirect();
        $this->assertSame(IdentityVerification::STATUS_VERIFIED, $verification->fresh()->status);

        $this->actingAs($buyer)
            ->get(route('checkout.create', ['items' => [$cartItem->id]]))
            ->assertOk()
            ->assertSee('Metode Pembayaran');
    }

    public function test_admin_can_reject_and_buyer_can_resubmit_document(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->post(route('account.identity.update'), [
            'legal_name' => $buyer->name,
            'nik' => '3273010101010002',
            'identity_document' => UploadedFile::fake()->image('buram.jpg'),
            'consent' => '1',
        ])->assertRedirect();

        $verification = $buyer->identityVerification()->firstOrFail();
        $oldPath = $verification->document_path;

        $this->actingAs($admin)->patch(route('admin.buyers.identity.reject', $buyer), [
            'review_note' => 'Foto KTP terlalu buram dan bagian kanan terpotong.',
        ])->assertRedirect();

        $this->assertSame(IdentityVerification::STATUS_REJECTED, $verification->fresh()->status);
        $this->actingAs($buyer)
            ->get(route('account.identity.edit'))
            ->assertOk()
            ->assertSee('Foto KTP terlalu buram');

        $this->post(route('account.identity.update'), [
            'legal_name' => $buyer->name,
            'nik' => '3273010101010002',
            'identity_document' => UploadedFile::fake()->image('jelas.png', 1200, 760),
            'consent' => '1',
        ])->assertRedirect();

        $verification->refresh();
        $this->assertSame(IdentityVerification::STATUS_PENDING, $verification->status);
        $this->assertNull($verification->review_note);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($verification->document_path);
    }

    public function test_same_nik_cannot_verify_two_buyer_accounts(): void
    {
        Storage::fake('local');
        $first = User::factory()->create();
        $second = User::factory()->create();

        $payload = [
            'legal_name' => 'Nama KTP',
            'nik' => '3273010101010003',
            'identity_document' => UploadedFile::fake()->image('ktp.jpg'),
            'consent' => '1',
        ];

        $this->actingAs($first)->post(route('account.identity.update'), $payload)->assertRedirect();
        $this->actingAs($second)->post(route('account.identity.update'), [
            ...$payload,
            'identity_document' => UploadedFile::fake()->image('ktp-lain.jpg'),
        ])->assertSessionHasErrors('nik');
    }
}
