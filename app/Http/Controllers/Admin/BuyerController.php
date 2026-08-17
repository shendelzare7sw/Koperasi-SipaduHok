<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Notifications\IdentityVerificationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BuyerController extends Controller
{
    public function index(Request $request): View
    {
        $buyers = User::query()
            ->where('role', UserRole::Buyer->value)
            ->with('identityVerification')
            ->when($request->filled('verification'), function ($query) use ($request) {
                $status = $request->string('verification')->value();
                $status === 'not_submitted'
                    ? $query->whereDoesntHave('identityVerification')
                    : $query->whereHas('identityVerification', fn ($identity) => $identity->where('status', $status));
            })
            ->when($request->filled('account_status'), fn ($query) => $query
                ->where('is_active', $request->string('account_status')->value() === 'active'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($nested) => $nested
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->withCount('orders')
            ->withSum([
                'orders as completed_spend' => fn ($query) => $query->where('status', OrderStatus::Completed->value),
            ], 'total')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.buyers.index', [
            'buyers' => $buyers,
            'pendingVerificationCount' => IdentityVerification::where('status', IdentityVerification::STATUS_PENDING)->count(),
            'activeBuyerCount' => User::where('role', UserRole::Buyer->value)->where('is_active', true)->count(),
            'inactiveBuyerCount' => User::where('role', UserRole::Buyer->value)->where('is_active', false)->count(),
        ]);
    }

    public function show(User $buyer): View
    {
        $this->ensureBuyer($buyer);

        return view('admin.buyers.show', [
            'buyer' => $buyer->load([
                'identityVerification.reviewer',
                'addresses',
                'orders' => fn ($query) => $query->latest()->limit(10),
            ]),
        ]);
    }

    public function approve(Request $request, User $buyer): RedirectResponse
    {
        $this->ensureBuyer($buyer);
        $verification = $buyer->identityVerification;
        abort_unless($verification && $verification->status === IdentityVerification::STATUS_PENDING, 422);

        $verification->update([
            'status' => IdentityVerification::STATUS_VERIFIED,
            'review_note' => null,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);
        $buyer->notify(new IdentityVerificationNotification(
            'Identitas berhasil diverifikasi',
            'Admin menyetujui verifikasi KTP Anda. Checkout sekarang dapat digunakan.',
            'account.identity.edit',
            $buyer->id,
            'fa-circle-check',
        ));

        return back()->with('success', 'Identitas pembeli berhasil diverifikasi.');
    }

    public function toggleActive(User $buyer): RedirectResponse
    {
        $this->ensureBuyer($buyer);
        $buyer->update(['is_active' => ! $buyer->is_active]);

        if (! $buyer->is_active) {
            DB::table('sessions')->where('user_id', $buyer->id)->delete();
        }

        return back()->with(
            'success',
            $buyer->is_active
                ? 'Akun pembeli berhasil diaktifkan kembali.'
                : 'Akun pembeli berhasil dinonaktifkan dan seluruh sesi aktifnya dicabut.',
        );
    }

    public function reject(Request $request, User $buyer): RedirectResponse
    {
        $this->ensureBuyer($buyer);
        $validated = $request->validate([
            'review_note' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $verification = $buyer->identityVerification;
        abort_unless($verification && $verification->status === IdentityVerification::STATUS_PENDING, 422);

        $verification->update([
            'status' => IdentityVerification::STATUS_REJECTED,
            'review_note' => $validated['review_note'],
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);
        $buyer->notify(new IdentityVerificationNotification(
            'Verifikasi identitas perlu diperbaiki',
            'Dokumen KTP ditolak. Buka halaman verifikasi untuk melihat alasan dan mengirim ulang.',
            'account.identity.edit',
            $buyer->id,
            'fa-triangle-exclamation',
        ));

        return back()->with('success', 'Verifikasi ditolak dan pembeli telah diberi notifikasi.');
    }

    private function ensureBuyer(User $buyer): void
    {
        abort_unless($buyer->role === UserRole::Buyer, 404);
    }
}
