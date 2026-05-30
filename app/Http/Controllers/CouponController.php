<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search');

        $coupons = Coupon::query()
            ->when($search, function ($query, $search) {
                $query->where('code', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Coupons/Index', [
            'coupons' => $coupons,
            'search' => $search,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Coupons/Create');
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $coupon = Coupon::query()->create($validated);

        return redirect()->route('coupons.index')
            ->with('success', "Coupon '{$coupon->code}' created successfully.");
    }

    public function edit(Coupon $coupon): Response
    {
        return Inertia::render('Coupons/Edit', [
            'coupon' => $coupon,
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validated();

        $coupon->update($validated);

        return redirect()->route('coupons.index')
            ->with('success', "Coupon '{$coupon->code}' updated successfully.");
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $couponCode = $coupon->code;
        $coupon->delete();

        return redirect()->route('coupons.index')
            ->with('success', "Coupon '{$couponCode}' deleted successfully.");
    }
}
