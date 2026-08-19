<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = $this->filteredQuery($request)
            ->paginate($this->perPage($request, 25))
            ->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    /**
     * Same filters as index() (search/status/date range), applied to every matching row
     * rather than just the current page — a CSV export should reflect the whole filtered
     * result set, not one page of it.
     */
    private function filteredQuery(Request $request)
    {
        $search = $this->searchTerm($request);

        return Transaction::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('mpesa_receipt', 'like', "%{$search}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest();
    }

    /**
     * Streams rather than builds the CSV in memory — a tenant's full transaction history
     * could be tens of thousands of rows.
     */
    public function export(Request $request): StreamedResponse
    {
        $filename = 'transactions-'.now()->format('Y-m-d_His').'.csv';

        $callback = function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Customer', 'Phone', 'Package', 'Amount (KES)', 'Method', 'Receipt', 'Status', 'Date']);

            $this->filteredQuery($request)->chunk(500, function ($transactions) use ($handle) {
                foreach ($transactions as $transaction) {
                    fputcsv($handle, [
                        $transaction->customer_name,
                        $transaction->phone_number,
                        $transaction->package_name,
                        $transaction->amount,
                        $transaction->payment_method,
                        $transaction->mpesa_receipt,
                        $transaction->status,
                        $transaction->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Polled by the index page while a visible row is still 'pending'. Re-reads the current
     * status of the given IDs instead of re-running the whole filtered/paginated query.
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
