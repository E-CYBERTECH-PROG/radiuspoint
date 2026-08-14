<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Support\ThemePalette;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'themes' => ThemePalette::COLORS,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Switch which of the 3 dashboard.blade.php arrangements this user sees (see
     * DashboardController::index()). Unlike the Appearance color pickers this isn't just a CSS
     * recolor — it's a different Blade view entirely — so it's persisted server-side rather than
     * to localStorage, and takes effect the moment they next load the dashboard.
     */
    public function updateDashboardLayout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dashboard_layout' => ['required', Rule::in(User::DASHBOARD_LAYOUTS)],
        ]);

        $request->user()->update($validated);

        return Redirect::route('profile.edit')->with('status', 'dashboard-layout-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
