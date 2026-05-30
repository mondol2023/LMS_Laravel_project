<?php

namespace App\Http\Controllers;

use App\Models\CoachingIp;
use App\Services\CoachingAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoachingIpController extends Controller
{
    public function __construct(
        protected CoachingAccessService $coachingAccessService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $filter = $request->get('filter', 'all'); // all, active, inactive

        $coachingIps = CoachingIp::query()
            ->when($search, function ($query, $search) {
                $query->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            })
            ->when($filter === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($filter === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('settings/CoachingIps/Index', [
            'coachingIps' => $coachingIps,
            'search' => $search,
            'filter' => $filter,
            'currentIp' => $this->coachingAccessService->getClientIp($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('settings/CoachingIps/Create', [
            'currentIp' => $this->coachingAccessService->getClientIp(request()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ip_address' => ['required', 'ip', 'unique:coaching_ips,ip_address'],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $coachingIp = CoachingIp::create($validated);

        // Clear the cache so new IP is recognized
        $this->coachingAccessService->clearCache();

        return redirect()->route('coaching-ips.index')
            ->with('success', "Coaching IP '{$coachingIp->ip_address}' added successfully.");
    }

    public function edit(CoachingIp $coachingIp): Response
    {
        return Inertia::render('settings/CoachingIps/Edit', [
            'coachingIp' => $coachingIp,
            'currentIp' => $this->coachingAccessService->getClientIp(request()),
        ]);
    }

    public function update(Request $request, CoachingIp $coachingIp): RedirectResponse
    {
        $validated = $request->validate([
            'ip_address' => ['required', 'ip', 'unique:coaching_ips,ip_address,' . $coachingIp->id],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $coachingIp->update($validated);

        // Clear the cache so changes take effect
        $this->coachingAccessService->clearCache();

        return redirect()->route('coaching-ips.index')
            ->with('success', "Coaching IP '{$coachingIp->ip_address}' updated successfully.");
    }

    public function destroy(CoachingIp $coachingIp): RedirectResponse
    {
        $ipAddress = $coachingIp->ip_address;
        $coachingIp->delete();

        // Clear the cache so deletion takes effect
        $this->coachingAccessService->clearCache();

        return redirect()->route('coaching-ips.index')
            ->with('success', "Coaching IP '{$ipAddress}' deleted successfully.");
    }

    public function toggleStatus(CoachingIp $coachingIp): RedirectResponse
    {
        $coachingIp->update(['is_active' => ! $coachingIp->is_active]);

        // Clear the cache so status change takes effect
        $this->coachingAccessService->clearCache();

        $status = $coachingIp->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Coaching IP '{$coachingIp->ip_address}' {$status} successfully.");
    }

    /**
     * Add current IP as a coaching IP.
     */
    public function addCurrentIp(Request $request): RedirectResponse
    {
        $currentIp = $this->coachingAccessService->getClientIp($request);

        // Check if this IP already exists
        if (CoachingIp::where('ip_address', $currentIp)->exists()) {
            return back()->with('error', "IP '{$currentIp}' is already in the list.");
        }

        CoachingIp::create([
            'ip_address' => $currentIp,
            'label' => 'Added from current session',
            'is_active' => true,
        ]);

        // Clear the cache
        $this->coachingAccessService->clearCache();

        return back()->with('success', "Current IP '{$currentIp}' added successfully.");
    }
}
