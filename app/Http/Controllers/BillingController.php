<?php

namespace App\Http\Controllers;

use App\Models\CentralInvoice;
use App\Models\CentralSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $setting = CentralSetting::current();

        $invoices = CentralInvoice::query()
            ->when($request->input('status'), function ($query, string $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_invoices' => CentralInvoice::count(),
            'unpaid_count' => CentralInvoice::where('status', 'unpaid')->count(),
            'overdue_count' => CentralInvoice::where('status', 'overdue')->count(),
            'paid_count' => CentralInvoice::where('status', 'paid')->count(),
            'total_due' => CentralInvoice::unpaid()->sum('total'),
        ];

        return Inertia::render('Admin/Billing/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'sellerStatus' => $setting->seller_status,
            'statusMessage' => $setting->status_message,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(CentralInvoice $invoice): Response
    {
        return Inertia::render('Admin/Billing/Show', [
            'invoice' => $invoice,
        ]);
    }
}
