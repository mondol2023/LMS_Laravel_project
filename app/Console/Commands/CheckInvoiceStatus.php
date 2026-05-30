<?php

namespace App\Console\Commands;

use App\Models\CentralInvoice;
use App\Models\CentralSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckInvoiceStatus extends Command
{
    protected $signature = 'central:check-invoices';

    protected $description = 'Sync invoice statuses based on grace period and auto-suspend if needed';

    public function handle(): int
    {
        $setting = CentralSetting::current();

        if ($setting->isSuspended() || $setting->isBlocked()) {
            $this->info("Seller is already {$setting->seller_status}. Skipping.");

            return Command::SUCCESS;
        }

        $gracePeriodDays = $setting->grace_period_days ?? 7;
        $autoSuspendAfterDays = $setting->auto_suspend_after_days ?? 15;
        $today = now();
        $graceDeadline = $today->copy()->subDays($gracePeriodDays);

        // Step 1: Invoices past due_date + grace period → overdue
        $newlyOverdue = CentralInvoice::where('status', 'unpaid')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $graceDeadline)
            ->get();

        foreach ($newlyOverdue as $invoice) {
            $invoice->update(['status' => 'overdue']);
            $this->info("Marked invoice {$invoice->invoice_number} as overdue.");
        }

        // Step 2: Invoices marked overdue but now within grace period (settings changed) → revert to unpaid
        $reverted = CentralInvoice::where('status', 'overdue')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $graceDeadline)
            ->get();

        foreach ($reverted as $invoice) {
            $invoice->update(['status' => 'unpaid']);
            $this->info("Reverted invoice {$invoice->invoice_number} to unpaid (within grace period).");
        }

        // Step 3: Check overdue invoices for auto-suspend
        $overdueInvoices = CentralInvoice::where('status', 'overdue')
            ->whereNotNull('due_date')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = (int) Carbon::parse($invoice->due_date)->diffInDays($today);

            if ($daysOverdue > ($gracePeriodDays + $autoSuspendAfterDays)) {
                $setting->updateStatus(
                    'suspended',
                    "Your account has been suspended due to unpaid invoice {$invoice->invoice_number} ({$daysOverdue} days overdue). Please contact support.",
                    "overdue_invoice:{$invoice->invoice_number}",
                );

                $this->warn("Auto-suspended: Invoice {$invoice->invoice_number} is {$daysOverdue} days overdue (grace: {$gracePeriodDays}d + suspend: {$autoSuspendAfterDays}d).");

                return Command::SUCCESS;
            }

            $daysLeft = ($gracePeriodDays + $autoSuspendAfterDays) - $daysOverdue;
            $this->warn("Invoice {$invoice->invoice_number} is {$daysOverdue} days overdue. Auto-suspend in {$daysLeft} days.");
        }

        if ($overdueInvoices->isEmpty() && $newlyOverdue->isEmpty()) {
            $this->info('No overdue invoices. All good.');
        }

        return Command::SUCCESS;
    }
}
