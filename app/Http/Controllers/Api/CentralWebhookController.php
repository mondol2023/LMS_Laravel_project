<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CentralInvoice;
use App\Models\CentralSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CentralWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            Log::warning('Central webhook: Invalid signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $action = $request->input('action');

        if (! $action) {
            return response()->json(['error' => 'Missing action'], 400);
        }

        Log::info("Central webhook received: {$action}", $request->except('action'));

        return match ($action) {
            'seller.suspended' => $this->handleStatusChange('suspended', $request),
            'seller.activated' => $this->handleStatusChange('active', $request),
            'seller.blocked' => $this->handleStatusChange('blocked', $request),
            'seller.unblocked' => $this->handleStatusChange('active', $request),
            'invoice.created' => $this->handleInvoiceCreated($request),
            'invoice.paid' => $this->handleInvoicePaid($request),
            'invoice.cancelled' => $this->handleInvoiceCancelled($request),
            'invoice.overdue' => $this->handleInvoiceOverdue($request),
            'invoice.overdue_reminder' => $this->handleOverdueReminder($request),
            'billing_settings.updated' => $this->handleBillingSettingsUpdated($request),
            default => response()->json(['error' => 'Unknown action'], 400),
        };
    }

    private function handleStatusChange(string $status, Request $request): JsonResponse
    {
        $setting = CentralSetting::current();
        $reason = $request->input('reason');

        $setting->updateStatus($status, null, $reason);

        Log::info("Seller status updated to: {$status}", [
            'reason' => $reason,
        ]);

        return response()->json(['success' => true, 'message' => "Status updated to {$status}"]);
    }

    private function handleInvoiceCreated(Request $request): JsonResponse
    {
        $invoiceId = $request->input('invoice_id');

        $existing = CentralInvoice::where('remote_invoice_id', $invoiceId)->first();

        if ($existing) {
            return response()->json(['success' => true, 'message' => 'Invoice already exists']);
        }

        CentralInvoice::create([
            'remote_invoice_id' => $invoiceId,
            'invoice_number' => $request->input('invoice_number'),
            'subtotal' => $request->input('subtotal', 0),
            'discount' => $request->input('discount', 0),
            'total' => $request->input('total', 0),
            'due_date' => $request->input('due_date'),
            'status' => 'unpaid',
            'data' => $request->all(),
            'received_at' => now(),
        ]);

        // Update grace period settings if provided
        $gracePeriod = $request->input('grace_period_days');
        $autoSuspend = $request->input('auto_suspend_after_days');

        if ($gracePeriod !== null || $autoSuspend !== null) {
            $setting = CentralSetting::current();
            $updates = [];
            if ($gracePeriod !== null) {
                $updates['grace_period_days'] = (int) $gracePeriod;
            }
            if ($autoSuspend !== null) {
                $updates['auto_suspend_after_days'] = (int) $autoSuspend;
            }
            $setting->update($updates);
        }

        Log::info("Invoice received from central: {$request->input('invoice_number')}");

        return response()->json(['success' => true, 'message' => 'Invoice recorded']);
    }

    private function handleInvoicePaid(Request $request): JsonResponse
    {
        $invoiceId = $request->input('invoice_id');
        $invoiceNumber = $request->input('invoice_number');

        $invoice = CentralInvoice::where('remote_invoice_id', $invoiceId)
            ->orWhere('invoice_number', $invoiceNumber)
            ->first();

        if ($invoice) {
            $invoice->update([
                'status' => 'paid',
                'data' => array_merge($invoice->data ?? [], [
                    'paid_at' => $request->input('paid_at'),
                ]),
            ]);
            Log::info("Invoice marked paid from central: {$invoiceNumber}");
        }

        return response()->json(['success' => true, 'message' => 'Invoice marked as paid']);
    }

    private function handleInvoiceCancelled(Request $request): JsonResponse
    {
        $invoiceId = $request->input('invoice_id');
        $invoiceNumber = $request->input('invoice_number');

        $invoice = CentralInvoice::where('remote_invoice_id', $invoiceId)
            ->orWhere('invoice_number', $invoiceNumber)
            ->first();

        if ($invoice) {
            $invoice->update(['status' => 'cancelled']);
            Log::info("Invoice cancelled from central: {$invoiceNumber}");
        }

        return response()->json(['success' => true, 'message' => 'Invoice cancelled']);
    }

    private function handleInvoiceOverdue(Request $request): JsonResponse
    {
        $invoiceId = $request->input('invoice_id');
        $invoiceNumber = $request->input('invoice_number');

        $invoice = CentralInvoice::where('remote_invoice_id', $invoiceId)
            ->orWhere('invoice_number', $invoiceNumber)
            ->first();

        if ($invoice) {
            $invoice->update(['status' => 'overdue']);
            Log::warning("Invoice marked overdue from central: {$invoiceNumber}");
        }

        return response()->json(['success' => true, 'message' => 'Invoice marked as overdue']);
    }

    private function handleOverdueReminder(Request $request): JsonResponse
    {
        $invoiceNumber = $request->input('invoice_number');

        $invoice = CentralInvoice::where('invoice_number', $invoiceNumber)->first();

        if ($invoice) {
            $invoice->update([
                'status' => 'overdue',
                'data' => array_merge($invoice->data ?? [], [
                    'days_overdue' => $request->input('days_overdue'),
                    'last_reminder_at' => now()->toDateTimeString(),
                ]),
            ]);
        } else {
            CentralInvoice::create([
                'invoice_number' => $invoiceNumber,
                'total' => $request->input('total', 0),
                'status' => 'overdue',
                'data' => $request->all(),
                'received_at' => now(),
            ]);
        }

        Log::warning("Overdue reminder received for invoice: {$invoiceNumber}");

        return response()->json(['success' => true, 'message' => 'Overdue reminder processed']);
    }

    private function handleBillingSettingsUpdated(Request $request): JsonResponse
    {
        $setting = CentralSetting::current();
        $updates = [];

        $gracePeriod = $request->input('grace_period_days');
        $autoSuspend = $request->input('auto_suspend_after_days');

        if ($gracePeriod !== null) {
            $updates['grace_period_days'] = (int) $gracePeriod;
        }
        if ($autoSuspend !== null) {
            $updates['auto_suspend_after_days'] = (int) $autoSuspend;
        }

        if (! empty($updates)) {
            $setting->update($updates);
        }

        Log::info('Billing settings updated from central', $updates);

        return response()->json(['success' => true, 'message' => 'Billing settings updated']);
    }

    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $apiSecret = config('central.api_secret');

        if (! $signature || ! $timestamp || ! $apiSecret) {
            return false;
        }

        // Reject requests older than 5 minutes
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload.$timestamp, $apiSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
