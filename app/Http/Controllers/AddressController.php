<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\IndonesiaRegion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        $requestedCheckoutItems = collect($request->input('checkout_items', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return view('account.addresses', [
            'addresses' => $request->user()->addresses()->latest('is_primary')->latest()->get(),
            'checkoutItemIds' => $request->user()->cartItems()
                ->whereIn('id', $requestedCheckoutItems)
                ->pluck('id')
                ->all(),
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
        $request->merge(['_editing_address_id' => $address->id]);
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
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\-\s]+$/'],
            'province_code' => ['required', 'string', 'max:2', 'exists:indonesia_regions,code'],
            'city_code' => ['required', 'string', 'max:5', 'exists:indonesia_regions,code'],
            'district_code' => ['required', 'string', 'max:8', 'exists:indonesia_regions,code'],
            'village_code' => ['required', 'string', 'max:13', 'exists:indonesia_regions,code'],
            'street' => ['required', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:50'],
            'rt' => ['nullable', 'string', 'regex:/^\d{1,3}$/'],
            'rw' => ['nullable', 'string', 'regex:/^\d{1,3}$/'],
            'landmark' => ['nullable', 'string', 'max:500'],
            'postal_code' => ['required', 'string', 'regex:/^\d{5}$/'],
            'latitude' => ['required', 'numeric', 'between:-11.5,6.5'],
            'longitude' => ['required', 'numeric', 'between:94.5,141.5'],
        ], [
            'province_code.required' => 'Provinsi wajib dipilih dari daftar.',
            'province_code.exists' => 'Provinsi yang dipilih tidak valid. Silakan pilih ulang.',
            'city_code.required' => 'Kabupaten/kota wajib dipilih dari daftar.',
            'city_code.exists' => 'Kabupaten/kota yang dipilih tidak valid. Silakan pilih ulang.',
            'district_code.required' => 'Kecamatan wajib dipilih dari daftar.',
            'district_code.exists' => 'Kecamatan yang dipilih tidak valid. Silakan pilih ulang.',
            'village_code.required' => 'Kelurahan/desa wajib dipilih dari daftar.',
            'village_code.exists' => 'Kelurahan/desa yang dipilih tidak valid. Silakan pilih ulang.',
            'phone.regex' => 'Nomor HP hanya boleh berisi angka dan simbol telepon yang umum.',
            'rt.regex' => 'RT harus berisi 1 sampai 3 digit angka.',
            'rw.regex' => 'RW harus berisi 1 sampai 3 digit angka.',
            'postal_code.regex' => 'Kode pos harus terdiri dari lima digit angka.',
            'latitude.required' => 'Titik lokasi pengantaran wajib ditentukan di peta.',
            'longitude.required' => 'Titik lokasi pengantaran wajib ditentukan di peta.',
            'latitude.between' => 'Titik lokasi harus berada di wilayah Indonesia.',
            'longitude.between' => 'Titik lokasi harus berada di wilayah Indonesia.',
        ], [
            'label' => 'label alamat',
            'recipient_name' => 'nama penerima',
            'phone' => 'nomor HP',
            'province_code' => 'provinsi',
            'city_code' => 'kabupaten/kota',
            'district_code' => 'kecamatan',
            'village_code' => 'kelurahan/desa',
            'street' => 'nama jalan',
            'house_number' => 'nomor rumah/blok',
            'rt' => 'RT',
            'rw' => 'RW',
            'landmark' => 'patokan alamat',
            'postal_code' => 'kode pos',
            'latitude' => 'titik latitude',
            'longitude' => 'titik longitude',
        ]);

        $regions = IndonesiaRegion::query()
            ->whereIn('code', [
                $validated['province_code'],
                $validated['city_code'],
                $validated['district_code'],
                $validated['village_code'],
            ])
            ->get()
            ->keyBy('code');

        $province = $regions->get($validated['province_code']);
        $city = $regions->get($validated['city_code']);
        $district = $regions->get($validated['district_code']);
        $village = $regions->get($validated['village_code']);

        if ($province?->level !== IndonesiaRegion::PROVINCE
            || $city?->level !== IndonesiaRegion::REGENCY
            || $district?->level !== IndonesiaRegion::DISTRICT
            || $village?->level !== IndonesiaRegion::VILLAGE
            || $city?->parent_code !== $province?->code
            || $district?->parent_code !== $city?->code
            || $village?->parent_code !== $district?->code) {
            throw ValidationException::withMessages([
                'village_code' => 'Urutan wilayah tidak valid. Pilih kembali dari provinsi hingga kelurahan/desa.',
            ]);
        }

        $detailParts = collect([
            $validated['street'],
            filled($validated['house_number'] ?? null) ? 'No. '.$validated['house_number'] : null,
            filled($validated['rt'] ?? null) ? 'RT '.str_pad($validated['rt'], 2, '0', STR_PAD_LEFT) : null,
            filled($validated['rw'] ?? null) ? 'RW '.str_pad($validated['rw'], 2, '0', STR_PAD_LEFT) : null,
            $validated['landmark'] ?? null,
        ])->filter()->implode(', ');

        return [
            ...$validated,
            'full_address' => $detailParts,
            'province' => $province->name,
            'city' => $city->name,
            'district' => $district->name,
            'village' => $village->name,
        ];
    }

    private function authorizeOwner(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }
}
