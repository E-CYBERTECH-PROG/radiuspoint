<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Lead;
use App\Models\PppoeUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->searchTerm($request);

        $leads = Lead::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('leads.index', compact('leads'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:200',
        ]);

        Lead::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'location' => $request->location,
            'notes' => $request->notes,
        ]);

        return redirect()->route('leads.index')->with('success', 'Lead added successfully.');
    }

    public function update(Request $request, Lead $lead)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:200',
        ]);

        $lead->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'location' => $request->location,
            'notes' => $request->notes,
        ]);

        return redirect()->route('leads.index')->with('success', 'Lead updated successfully.');
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();

        return redirect()->route('leads.index')->with('success', 'Lead removed.');
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $request->validate([
            'status' => 'required|in:new,contacted,converted,lost',
        ]);

        $lead->update(['status' => $request->status]);

        return redirect()->route('leads.index')->with('success', 'Lead status updated.');
    }

    public function convert(Request $request, Lead $lead)
    {
        $request->validate([
            'type' => 'required|in:pppoe,hotspot',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        if ($request->type === 'pppoe') {
            $customer = PppoeUser::create([
                'tenant_id' => Auth::user()->tenant_id,
                'username' => $lead->phone,
                'name' => $lead->name,
                'phone_number' => $lead->phone,
                'current_plan_id' => $request->plan_id,
                'status' => 'active',
            ]);

            $lead->update([
                'status' => 'converted',
                'converted_to_pppoe_user_id' => $customer->id,
            ]);
        } else {
            $customer = HotspotUser::create([
                'tenant_id' => Auth::user()->tenant_id,
                'phone_number' => $lead->phone,
                'current_plan_id' => $request->plan_id,
                'status' => 'active',
            ]);

            $lead->update([
                'status' => 'converted',
                'converted_to_hotspot_user_id' => $customer->id,
            ]);
        }

        return redirect()->route('leads.index')->with('success', 'Lead converted to customer.');
    }
}
