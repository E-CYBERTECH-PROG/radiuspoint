<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::where('tenant_id', Auth::user()->tenant_id)
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('phone_number', 'like', "%{$request->search}%")
                    ->orWhere('customer_name', 'like', "%{$request->search}%")
                    ->orWhere('mpesa_receipt', 'like', "%{$request->search}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate($this->perPage($request, 25))
            ->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    /**
     * Polled by the index page while any visible row is still 'pending' — the M-Pesa STK
     * callback (BillingController::handleCallback) flips status independently of anything the
     * admin is looking at, so this just re-reads the current state of the given IDs rather than
     * re-running the whole filtered/paginated query.
     */
    public function liveStatus(Request $request)
    {
        $ids = (array) $request->input('ids', []);

        $transactions = Transaction::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('id', $ids)
            ->get(['id', 'status', 'mpesa_receipt']);

        return response()->json(['data' => $transactions->keyBy('id')]);
    }
}
