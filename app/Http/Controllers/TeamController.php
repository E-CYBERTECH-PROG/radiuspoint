<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->role === 'SuperAdmin', 403);

        $search = $this->searchTerm($request);

        $members = User::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_platform_admin', false)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('team.index', compact('members'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->role === 'SuperAdmin', 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:Admin,Technician,Sales Agent',
        ]);

        $password = Str::password(16);

        User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
            'role' => $request->role,
        ]);

        return redirect()->route('team.index')
            ->with('success', "Team member added. Temporary password: {$password} — copy this now, it won't be shown again.");
    }

    public function update(Request $request, User $user)
    {
        abort_unless(Auth::user()->role === 'SuperAdmin', 403);
        abort_unless($user->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:Admin,Technician,Sales Agent',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        return redirect()->route('team.index')->with('success', 'Team member updated.');
    }

    public function resetPassword(User $user)
    {
        abort_unless(Auth::user()->role === 'SuperAdmin', 403);
        abort_unless($user->tenant_id === Auth::user()->tenant_id, 403);

        $password = Str::password(16);
        $user->update(['password' => $password]);

        return redirect()->route('team.index')
            ->with('success', "Password reset for {$user->name}. New temporary password: {$password} — copy this now, it won't be shown again.");
    }

    public function destroy(User $user)
    {
        abort_unless(Auth::user()->role === 'SuperAdmin', 403);
        abort_unless($user->tenant_id === Auth::user()->tenant_id, 403);

        if ($user->id === Auth::id()) {
            return redirect()->route('team.index')->with('error', 'You cannot remove your own account.');
        }

        if ($user->role === 'SuperAdmin') {
            return redirect()->route('team.index')->with('error', 'You cannot remove another SuperAdmin.');
        }

        $user->delete();

        return redirect()->route('team.index')->with('success', 'Team member removed.');
    }
}
