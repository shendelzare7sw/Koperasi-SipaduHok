<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierController extends Controller
{
    public function edit(): View
    {
        return view('admin.courier.edit', ['courier' => $this->mainCourier()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'fee' => is_string($request->input('fee'))
                ? str_replace('.', '', trim($request->input('fee')))
                : $request->input('fee'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fee' => ['required', 'integer', 'min:0'],
            'estimate' => ['nullable', 'string', 'max:100'],
        ]);

        $this->mainCourier()->update([...$validated, 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Pengaturan satu-satunya Kurir Koperasi berhasil diperbarui.');
    }

    private function mainCourier(): Courier
    {
        return Courier::firstOrCreate(
            ['code' => 'main'],
            ['name' => 'Kurir Koperasi', 'fee' => 0, 'estimate' => 'Diantar pada hari sekolah', 'is_active' => true],
        );
    }
}
