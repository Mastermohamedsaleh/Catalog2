<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Legacy /profile URL: send the user to the page for their role. */
    public function edit(Request $request): RedirectResponse
    {
        return redirect()->route($this->homeRoute($request->user()));
    }

    /** Admin profile page (inside the admin shell; route is already admin-only). */
    public function admin(Request $request): View
    {
        return view('admin.profile.edit', ['user' => $request->user()]);
    }

    /** Merchant (regular user) profile page. */
    public function merchant(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.profile');
        }

        $stats = Order::where('user_id', $user->id)
            ->selectRaw("COUNT(*) as total, COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->first();

        return view('frontend.merchant-profile', [
            'user' => $user,
            'balance' => (float) ($user->wallet->balance ?? 0),
            'ordersTotal' => (int) $stats->total,
            'ordersDelivered' => (int) $stats->delivered,
            'canDelete' => $this->deletionBlocker($user) === null,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route($this->homeRoute($user))->with('success', __('Profile updated successfully.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($reason = $this->deletionBlocker($user)) {
            return redirect()->route($this->homeRoute($user))->with('error', $reason);
        }

        DB::transaction(function () use ($user) {
            $user->wallet?->delete();
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function homeRoute(User $user): string
    {
        return $user->isAdmin() ? 'admin.profile' : 'merchant.profile';
    }

    /**
     * Why this account cannot be self-deleted (null = it can).
     * Orders and the wallet ledger are financial history and must never be orphaned.
     */
    private function deletionBlocker(User $user): ?string
    {
        if ($user->isAdmin()) {
            return __('Admin accounts can only be removed by a super admin.');
        }

        if (Order::query()->where('user_id', $user->id)->exists()) {
            return __('This account has orders, so it cannot be deleted. Please contact support.');
        }

        $wallet = $user->wallet;
        if ($wallet && ((float) $wallet->balance > 0 || WalletTransaction::where('wallet_id', $wallet->id)->exists())) {
            return __('This account has wallet history, so it cannot be deleted. Please contact support.');
        }

        return null;
    }
}
