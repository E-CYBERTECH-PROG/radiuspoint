<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public const CATEGORIES = [
        'Bandwidth', 'Equipment', 'Rent', 'Salaries', 'Utilities',
        'Fuel & Transport', 'Marketing', 'Maintenance', 'Other',
    ];

    public function index(Request $request)
    {
        $search = $this->searchTerm($request);
        $tenantId = Auth::user()->tenant_id;

        $expenses = Expense::with('recordedBy')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('category', 'like', "%{$search}%")->orWhere('notes', 'like', "%{$search}%");
            }))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->latest('spent_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $stats = [
            'this_month' => Expense::where('tenant_id', $tenantId)->whereBetween('spent_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'this_year' => Expense::where('tenant_id', $tenantId)->whereBetween('spent_at', [now()->startOfYear(), now()->endOfYear()])->sum('amount'),
            'all_time' => Expense::where('tenant_id', $tenantId)->sum('amount'),
        ];

        return view('expenses.index', compact('expenses', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|in:'.implode(',', self::CATEGORIES),
            'amount' => 'required|numeric|min:0.01',
            'spent_at' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        Expense::create([
            'tenant_id' => Auth::user()->tenant_id,
            'recorded_by' => Auth::id(),
            'category' => $request->category,
            'amount' => $request->amount,
            'spent_at' => $request->spent_at,
            'notes' => $request->notes,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense logged.');
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'category' => 'required|in:'.implode(',', self::CATEGORIES),
            'amount' => 'required|numeric|min:0.01',
            'spent_at' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $expense->update([
            'category' => $request->category,
            'amount' => $request->amount,
            'spent_at' => $request->spent_at,
            'notes' => $request->notes,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense removed.');
    }
}
