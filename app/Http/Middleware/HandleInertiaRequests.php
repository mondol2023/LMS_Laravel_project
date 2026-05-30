<?php

namespace App\Http\Middleware;

use App\Models\CentralInvoice;
use App\Models\CentralSetting;
use App\Services\CoachingAccessService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $coachingService = app(CoachingAccessService::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'isCoachingMode' => $coachingService->isCoachingAccess($request),
            'clientIp' => $coachingService->getClientIp($request),
            'sellerStatus' => fn () => CentralSetting::current()->seller_status,
            'billingNotifications' => function () use ($request) {
                if (! $request->user() || $request->user()->role !== 'admin') {
                    return null;
                }

                $setting = CentralSetting::current();

                // Sync invoice statuses so dashboard/billing pages show correct overdue state
                if ($setting->isActive()) {
                    $gracePeriodDays = $setting->grace_period_days ?? 7;
                    $graceDeadline = now()->subDays($gracePeriodDays);

                    CentralInvoice::where('status', 'unpaid')
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', $graceDeadline)
                        ->update(['status' => 'overdue']);

                    CentralInvoice::where('status', 'overdue')
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '>=', $graceDeadline)
                        ->update(['status' => 'unpaid']);
                }

                $unpaid = CentralInvoice::where('status', 'unpaid')->get(['id', 'invoice_number', 'total', 'due_date', 'status']);
                $overdue = CentralInvoice::where('status', 'overdue')->get(['id', 'invoice_number', 'total', 'due_date', 'status']);

                return [
                    'unpaid' => $unpaid,
                    'overdue' => $overdue,
                    'total_count' => $unpaid->count() + $overdue->count(),
                    'grace_period_days' => $setting->grace_period_days ?? 7,
                    'auto_suspend_after_days' => $setting->auto_suspend_after_days ?? 15,
                ];
            },
        ];
    }
}
