<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses', [
            'addresses' => $request->user()->addresses()->latest('is_primary')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAddress($request);

        DB::transaction(function () use ($request, $validated) {
            $makePrimary = $request->boolean('is_primary') || ! $request->user()->addresses()->exists();

            if ($makePrimary) {
                $request->user()->addresses()->update(['is_primary' => false]);
            }

            $request->user()->addresses()->create([
                ...$validated,
                'is_primary' => $makePrimary,
            ]);
        });

        return back()->with('success', 'Alamat baru berhasil disimpan.');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);
        $validated = $this->validateAddress($request);

        DB::transaction(function () use ($request, $address, $validated) {
            if ($request->boolean('is_primary')) {
                $request->user()->addresses()->whereKeyNot($address->id)->update(['is_primary' => false]);
            }

            $address->update([
                ...$validated,
                'is_primary' => $request->boolean('is_primary') || $address->is_primary,
            ]);
        });

        return back()->with('success', 'Alamat berhasil diperbarui.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        DB::transaction(function () use ($request, $address) {
            $wasPrimary = $address->is_primary;
            $address->delete();

            if ($wasPrimary) {
                $request->user()->addresses()->oldest()->first()?->update(['is_primary' => true]);
            }
        });

        return back()->with('success', 'Alamat berhasil dihapus.');
    }

    public function setPrimary(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()->update(['is_primary' => false]);
            $address->update(['is_primary' => true]);
        });

        return back()->with('success', 'Alamat utama berhasil diubah.');
    }

    /** @return array<string, mixed> */
    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'full_address' => ['required', 'string', 'max:1000'],
        ]);
    }

    private function authorizeOwner(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }
}
