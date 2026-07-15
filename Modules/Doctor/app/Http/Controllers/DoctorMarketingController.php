<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\MarketingPackage;
use App\Models\MarketingSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DoctorMarketingController extends Controller
{
    public function packages(Request $request)
    {
        $packages = MarketingPackage::all()->map(fn ($p) => [
            'id' => $p->id,
            'nameKey' => $p->name_key,
            'postsPerMonth' => $p->posts_per_month,
            'monthlyPrice' => (float) $p->monthly_price,
            'yearlyPrice' => (float) $p->yearly_price,
            'isMostPopular' => (bool) $p->is_most_popular,
            'platforms' => $p->platforms,
            'features' => $p->features,
        ]);

        $active = MarketingSubscription::where('doctor_id', $request->user()->id)
            ->where('end_date', '>=', now()->toDateString())
            ->latest()
            ->first();

        return ApiResponse::data([
            'packages' => $packages,
            'activePlan' => $active ? [
                'packageId' => $active->package_id,
                'postsUsed' => $active->posts_used,
                'totalPosts' => $active->total_posts,
                'startDate' => $active->start_date?->toISOString(),
                'endDate' => $active->end_date?->toISOString(),
            ] : null,
        ]);
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'packageId' => 'required|exists:marketing_packages,id',
            'billingCycle' => 'required|in:monthly,yearly',
        ]);

        $package = MarketingPackage::findOrFail($validated['packageId']);
        $start = Carbon::today();
        $end = $validated['billingCycle'] === 'yearly'
            ? $start->copy()->addYear()
            : $start->copy()->addMonth();

        $subscription = MarketingSubscription::create([
            'doctor_id' => $request->user()->id,
            'package_id' => $package->id,
            'billing_cycle' => $validated['billingCycle'],
            'posts_used' => 0,
            'total_posts' => (int) $package->posts_per_month,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        return ApiResponse::message('Subscription successful', [
            'subscriptionId' => 'sub_' . $subscription->id,
        ]);
    }
}
