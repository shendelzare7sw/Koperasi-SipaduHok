<?php

namespace App\Http\Controllers;

use App\Models\IdentityVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdentityDocumentController extends Controller
{
    public function __invoke(Request $request, IdentityVerification $identityVerification): StreamedResponse
    {
        abort_unless(
            $request->user()->isAdmin() || $identityVerification->user_id === $request->user()->id,
            403,
        );
        abort_unless(Storage::disk('local')->exists($identityVerification->document_path), 404);

        return Storage::disk('local')->response(
            $identityVerification->document_path,
            null,
            [
                'Content-Type' => $identityVerification->document_mime,
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
