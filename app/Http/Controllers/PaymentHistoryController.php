<?php

namespace App\Http\Controllers;

use App\Models\PaymentHistory;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->get('filter', 'all');
        $search = $request->get('search');
        $packageId = $request->get('package');

        $query = PaymentHistory::query()
            ->with(['user', 'package', 'assignedBy', 'subscription']);

        // Apply filters
        if ($filter === 'paid') {
            $query->paid();
        } elseif ($filter === 'partial') {
            $query->partial();
        } elseif ($filter === 'due') {
            $query->due();
        }

        // Package filter
        if ($packageId) {
            $query->where('package_id', $packageId);
        }

        // Search
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $payments = $query->latest('payment_date')->paginate(20);

        // Get all packages for filter dropdown
        $packages = SubscriptionPackage::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Summary statistics
        $stats = [
            'total_earned' => PaymentHistory::sum('paid_amount'),
            'paid_count' => PaymentHistory::paid()->count(),
            'partial_count' => PaymentHistory::partial()->count(),
            'due_count' => PaymentHistory::due()->count(),
            'total_due' => PaymentHistory::where('due_amount', '>', 0)->sum('due_amount'),
        ];

        return Inertia::render('PaymentHistory/Index', [
            'payments' => $payments,
            'stats' => $stats,
            'filter' => $filter,
            'search' => $search,
            'selectedPackage' => $packageId,
            'packages' => $packages,
        ]);
    }

    public function show(PaymentHistory $payment): Response
    {
        $payment->load(['user', 'package', 'subscription', 'assignedBy']);

        return Inertia::render('PaymentHistory/Show', [
            'payment' => $payment,
        ]);
    }

    public function recordAdditionalPayment(Request $request, PaymentHistory $payment)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:'.$payment->due_amount],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_date' => ['required', 'date'],
            'new_expiry_date' => ['nullable', 'date', 'after:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Update payment record
        $newPaidAmount = $payment->paid_amount + $validated['amount'];
        $newDueAmount = $payment->package_price - $newPaidAmount;

        $payment->update([
            'paid_amount' => $newPaidAmount,
            'due_amount' => $newDueAmount,
            'payment_status' => $newDueAmount <= 0 ? 'paid' : 'partial',
            'expiry_date' => $validated['new_expiry_date'] ?? $payment->expiry_date,
        ]);

        // Update subscription expiry if provided
        if (isset($validated['new_expiry_date']) && $payment->subscription) {
            $payment->subscription->update([
                'expires_at' => $validated['new_expiry_date'],
            ]);
        }

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}
