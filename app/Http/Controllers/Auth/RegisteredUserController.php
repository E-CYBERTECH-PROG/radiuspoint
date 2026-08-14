<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the incoming registration data.
        $request->validate([
            'company_name' => ['required', 'string', 'max:255', 'unique:tenants'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Create the tenant profile, pending admin approval, with a 10-day free
        // trial. After it expires, GenerateTenantInvoices bills 3% commission on
        // monthly revenue (see Tenant::isTrialExpired()).
        $tenant = Tenant::create([
            'company_name' => $request->company_name,
            'status' => 'pending',
            'subscription_status' => 'trial',
            'subscription_expires_at' => now()->addDays(10),
        ]);

        // Create the user and link them to the new tenant.
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'SuperAdmin', // first user of a tenant gets top access
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false));
    }
}