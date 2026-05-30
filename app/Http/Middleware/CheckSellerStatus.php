<?php

namespace App\Http\Middleware;

use App\Models\CentralInvoice;
use App\Models\CentralSetting;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckSellerStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $setting = CentralSetting::current();

        // Real-time invoice status sync based on current grace period
        if ($setting->isActive()) {
            $this->syncInvoiceStatuses($setting);
        }

        // Blocked: suspended or blocked by super admin
        if ($setting->isSuspended() || $setting->isBlocked()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Service unavailable',
                    'status' => $setting->seller_status,
                    'message' => $setting->status_message,
                ], 503);
            }

            return Inertia::render('Admin/SellerStatus', [
                'status' => $setting->seller_status,
                'message' => $setting->status_message,
                'reason' => $setting->status_reason,
                'lastUpdate' => $setting->last_status_update?->toDateTimeString(),
            ])->toResponse($request)->setStatusCode(503);
        }

        // Restricted: has overdue invoices — only dashboard & billing accessible
        $hasOverdue = CentralInvoice::where('status', 'overdue')->exists();

        if ($hasOverdue) {
            return redirect()->route('billing.index')
                ->with('error', 'You have overdue invoices. Please pay to access all features.');
        }

        return $next($request);
    }

    private function syncInvoiceStatuses(CentralSetting $setting): void
    {
        $gracePeriodDays = $setting->grace_period_days ?? 7;
        $autoSuspendAfterDays = $setting->auto_suspend_after_days ?? 15;
        $today = now();
        $graceDeadline = $today->copy()->subDays($gracePeriodDays);

        // Invoices past due_date + grace period → overdue
        CentralInvoice::where('status', 'unpaid')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $graceDeadline)
            ->update(['status' => 'overdue']);

        // Invoices marked overdue but still within grace period (grace period was extended) → revert to unpaid
        CentralInvoice::where('status', 'overdue')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $graceDeadline)
            ->update(['status' => 'unpaid']);

        // Check if any overdue invoice exceeds grace + auto_suspend threshold → suspend
        $overdueInvoice = CentralInvoice::where('status', 'overdue')
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->first();

        if (! $overdueInvoice) {
            return;
        }

        $daysOverdue = (int) Carbon::parse($overdueInvoice->due_date)->diffInDays($today);

        if ($daysOverdue > ($gracePeriodDays + $autoSuspendAfterDays)) {
            $setting->updateStatus(
                'suspended',
                "Your account has been suspended due to unpaid invoice {$overdueInvoice->invoice_number} ({$daysOverdue} days overdue). Please contact support.",
                "overdue_invoice:{$overdueInvoice->invoice_number}",
            );
        }
    }
}
