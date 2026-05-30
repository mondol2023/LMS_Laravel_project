<?php

namespace App\Http\Controllers;

use App\Models\CentralInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class BkashPaymentController extends Controller
{
    /**
     * Initiate bKash payment by calling super admin API.
     */
    public function initiate(CentralInvoice $invoice): RedirectResponse
    {
        if ($invoice->status === 'paid' || $invoice->status === 'cancelled') {
            return redirect()->route('billing.show', $invoice)->with('error', 'This invoice cannot be paid.');
        }

        $superAdminUrl = config('central.super_admin_url');
        $apiSecret = config('central.api_secret');

        if (! $superAdminUrl || ! $apiSecret) {
            return redirect()->route('billing.show', $invoice)->with('error', 'Payment service not configured.');
        }

        $payload = json_encode([
            'invoice_id' => $invoice->remote_invoice_id,
            'success_url' => route('bkash.success', $invoice),
            'cancel_url' => route('bkash.cancelled', $invoice),
            'fail_url' => route('bkash.failed', $invoice),
        ]);

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $payload.$timestamp, $apiSecret);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Signature' => $signature,
                    'X-Timestamp' => $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->post(rtrim($superAdminUrl, '/').'/api/seller/pay/bkash', json_decode($payload, true));

            $data = $response->json();

            if ($response->successful() && isset($data['bkashURL'])) {
                return redirect()->away($data['bkashURL']);
            }

            $error = $data['error'] ?? 'Could not initiate payment.';
            Log::error('bKash initiate failed', $data ?? []);

            return redirect()->route('billing.show', $invoice)->with('error', $error);
        } catch (\Exception $e) {
            Log::error('bKash initiate exception: '.$e->getMessage());

            return redirect()->route('billing.show', $invoice)->with('error', 'Payment service unavailable. Please try again.');
        }
    }

    public function success(Request $request, CentralInvoice $invoice): Response
    {
        $trxID = $request->query('trxID');

        // Update local invoice to paid (webhook may also do this, but ensure immediate UI update)
        if ($invoice->status !== 'paid') {
            $invoice->update([
                'status' => 'paid',
                'data' => array_merge($invoice->data ?? [], [
                    'payment_method' => 'bkash',
                    'bkash_trx_id' => $trxID,
                    'paid_at' => now()->toDateTimeString(),
                ]),
            ]);
        }

        return Inertia::render('Admin/Billing/PaymentSuccess', [
            'invoice' => $invoice->fresh(),
            'trxID' => $trxID,
        ]);
    }

    public function cancelled(CentralInvoice $invoice): Response
    {
        return Inertia::render('Admin/Billing/PaymentCancelled', [
            'invoice' => $invoice,
        ]);
    }

    public function failed(Request $request, CentralInvoice $invoice): Response
    {
        return Inertia::render('Admin/Billing/PaymentFailed', [
            'invoice' => $invoice,
            'error' => $request->query('error', 'Payment failed. Please try again.'),
        ]);
    }
}
