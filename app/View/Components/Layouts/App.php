<?php

namespace App\View\Components\Layouts;

use App\Models\IdentityVerification;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Component;

class App extends Component
{
    public function __construct(public ?string $title = null) {}

    public function render(): View
    {
        $user = auth()->user();

        return view('layouts.app', [
            'latestNotifications' => $user
                ? $user->notifications()->latest()->limit(6)->get()
                : new Collection,
            'unreadNotificationCount' => $user
                ? $user->unreadNotifications()->count()
                : 0,
            'wishlistCount' => $user && ! $user->isAdmin()
                ? $user->wishlists()->count()
                : 0,
            'storeSettings' => Schema::hasTable('store_settings')
                ? StoreSetting::values()
                : StoreSetting::DEFAULTS,
            'pendingIdentityCount' => $user && $user->isAdmin() && Schema::hasTable('identity_verifications')
                ? IdentityVerification::where('status', IdentityVerification::STATUS_PENDING)->count()
                : 0,
        ]);
    }
}
