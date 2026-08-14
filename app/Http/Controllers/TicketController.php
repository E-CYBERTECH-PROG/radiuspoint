<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->searchTerm($request);

        $tickets = Ticket::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'notes' => 'required|string',
        ]);

        Ticket::create([
            'tenant_id' => Auth::user()->tenant_id,
            'username' => $request->username,
            'phone' => $request->phone,
            'notes' => $request->notes,
        ]);

        return redirect()->route('tickets.index')->with('success', 'Ticket created successfully.');
    }

    public function update(Request $request, Ticket $ticket)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'notes' => 'required|string',
        ]);

        $ticket->update([
            'username' => $request->username,
            'phone' => $request->phone,
            'notes' => $request->notes,
        ]);

        return redirect()->route('tickets.index')->with('success', 'Ticket updated successfully.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'Ticket removed.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $ticket->update(['status' => $request->status]);

        return redirect()->route('tickets.index')->with('success', 'Ticket status updated.');
    }
}
