<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Notifications\IdentityVerificationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class IdentityVerificationController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.identity', [
            'verification' => $request->user()->identityVerification()->first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $verification = $request->user()->identityVerification()->first();

        if ($verification?->status === IdentityVerification::STATUS_VERIFIED) {
            throw ValidationException::withMessages([
                'identity' => 'Identitas yang sudah terverifikasi tidak dapat diubah dari akun pembeli.',
            ]);
        }

        $validated = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'regex:/^\d{16}$/'],
            'identity_document' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'consent' => ['accepted'],
        ], [
            'nik.regex' => 'NIK harus terdiri dari tepat 16 digit angka.',
            'identity_document.image' => 'Dokumen KTP harus berupa gambar yang valid.',
            'identity_document.mimes' => 'Dokumen KTP hanya mendukung JPG, JPEG, PNG, atau WebP.',
            'identity_document.max' => 'Ukuran dokumen KTP maksimal 5 MB.',
            'consent.accepted' => 'Persetujuan pemrosesan data identitas wajib diberikan.',
        ]);

        $nik = trim($validated['nik']);
        $nikHash = hash_hmac('sha256', $nik, (string) config('app.key'));
        $duplicateNik = IdentityVerification::query()
            ->where('nik_hash', $nikHash)
            ->when($verification, fn ($query) => $query->whereKeyNot($verification->id))
            ->exists();

        if ($duplicateNik) {
            throw ValidationException::withMessages([
                'nik' => 'NIK sudah digunakan untuk verifikasi akun lain.',
            ]);
        }

        $file = $request->file('identity_document');
        $newPath = $file->store('identity-documents/'.$request->user()->id, 'local');

        if (! $newPath) {
            throw ValidationException::withMessages([
                'identity_document' => 'Dokumen KTP gagal disimpan. Silakan coba kembali.',
            ]);
        }

        $oldPath = $verification?->document_path;

        try {
            DB::transaction(function () use ($request, $validated, $nik, $nikHash, $newPath, $file) {
                IdentityVerification::updateOrCreate(
                    ['user_id' => $request->user()->id],
                    [
                        'legal_name' => trim($validated['legal_name']),
                        'nik' => $nik,
                        'nik_hash' => $nikHash,
                        'document_path' => $newPath,
                        'document_mime' => (string) $file->getMimeType(),
                        'status' => IdentityVerification::STATUS_PENDING,
                        'review_note' => null,
                        'submitted_at' => now(),
                        'reviewed_at' => null,
                        'reviewed_by' => null,
                    ],
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        User::query()->where('role', UserRole::Admin->value)->each(function (User $admin) use ($request) {
            $admin->notify(new IdentityVerificationNotification(
                'Verifikasi KTP baru',
                $request->user()->name.' mengirim dokumen KTP untuk ditinjau.',
                'admin.buyers.show',
                $request->user()->id,
            ));
        });

        return back()->with('success', 'Dokumen KTP berhasil dikirim dan sedang menunggu verifikasi admin.');
    }
}
