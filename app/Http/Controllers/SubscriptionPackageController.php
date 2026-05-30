<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionPackageRequest;
use App\Http\Requests\UpdateSubscriptionPackageRequest;
use App\Models\SubscriptionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPackageController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $filter = $request->get('filter', 'all'); // all, active, inactive

        $packages = SubscriptionPackage::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($filter === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($filter === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->withCount(['activeSubscriptions', 'subscriptions as total_subscriptions_count'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Subscriptions/Packages/Index', [
            'packages' => $packages,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Subscriptions/Packages/Create');
    }

    public function store(StoreSubscriptionPackageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $package = SubscriptionPackage::query()->create($validated);

        return redirect()->route('packages.index')
            ->with('success', "Package '{$package->name}' created successfully.");
    }

    public function edit(SubscriptionPackage $package): Response
    {
        return Inertia::render('Subscriptions/Packages/Edit', [
            'package' => $package,
        ]);
    }

    public function update(UpdateSubscriptionPackageRequest $request, SubscriptionPackage $package): RedirectResponse
    {
        $validated = $request->validated();

        $package->update($validated);

        return redirect()->route('packages.index')
            ->with('success', "Package '{$package->name}' updated successfully.");
    }

    public function destroy(SubscriptionPackage $package): RedirectResponse
    {
        // Check if package has any subscriptions (active or inactive)
        if ($package->subscriptions()->exists()) {
            $activeCount = $package->activeSubscriptions()->count();
            $totalCount = $package->subscriptions()->count();

            $message = $activeCount > 0
                ? "Cannot delete package with {$activeCount} active subscription(s). Please deactivate it instead."
                : "Cannot delete package with {$totalCount} subscription record(s). The package has been used by students.";

            return back()->with('error', $message);
        }

        $packageName = $package->name;
        $package->delete();

        return redirect()->route('packages.index')
            ->with('success', "Package '{$packageName}' deleted successfully.");
    }

    public function toggleStatus(SubscriptionPackage $package): RedirectResponse
    {
        $package->update(['is_active' => ! $package->is_active]);

        $status = $package->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Package '{$package->name}' {$status} successfully.");
    }
}
