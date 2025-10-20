<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class DashboardController extends Controller
{
    public function TotalRevenue()
    {
        $totalRevenue = Order::sum('amount');

        $currentMonth = Order::whereMonth('created_at', now()->month)->sum('amount');
        $lastMonth = Order::whereMonth('created_at', now()->subMonth()->month)->sum('amount');

        $change = $lastMonth > 0
            ? number_format((($currentMonth - $lastMonth) / $lastMonth) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'title' => 'Total Revenue',
                'value' => '$' . number_format($totalRevenue, 2),
                'change' => $change . '%',
                'trend' => $change >= 0 ? 'up' : 'down',
            ]
        ]);
    }

    public function totalLastMonth()
    {

        //$totaLastMonth = Order::sum('amount');
        $lastMonth = Order::whereMonth('created_at', now()->subMonth()->month)->sum('amount');
        return response()->json([
            'success' => true,
            'data' => [
                'title' => 'Total LastMonth',
                'value' => '$' . $lastMonth,
            ]
        ]);
    }

    public function thisMonth()
    {
        $thisMonth = Order::whereMonth('created_at', now()->month)->sum('amount');
        $lastMonth = Order::whereMonth('created_at', now()->subMonth()->month)->sum('amount');

        $change = $lastMonth > 0
            ? number_format((($thisMonth - $lastMonth) / $lastMonth) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'title' => 'Current Month',
                'value' => '$' . $thisMonth,
                'change' => $change . '%',
                'trend' => $change >= 0 ? 'up' : 'down',
            ]
        ]);
    }

    public function Customer()
    {
        // Total customers/orders this month
        $currentMonthCount = Order::whereMonth('created_at', now()->month)
            ->count();

        // Total customers/orders last month
        $lastMonthCount = Order::whereMonth('created_at', now()->subMonth()->month)
            ->count();

        // Percentage change
        $change = $lastMonthCount
            ? (($currentMonthCount - $lastMonthCount) / $lastMonthCount) * 100
            : 0;

        $trend = $change >= 0 ? 'up' : 'down';

        return response()->json([
            'success' => true,
            'data' => [
                'title' => 'Customers',
                'value' => $currentMonthCount,
                'change' => number_format($change, 2) . '%',
                'trend' => $trend
            ]
        ]);
    }

    public function RevenueOverview()
    {
        $months = collect(range(1, 12));
        $currentYear = now()->year;

        $lastYearData = $months->map(function ($month) use ($currentYear) {
            return Order::whereYear('created_at', $currentYear - 1)
                ->whereMonth('created_at', $month)
                ->sum('amount'); // or 'price' depending on your table
        });

        $currentYearData = $months->map(function ($month) use ($currentYear) {
            return Order::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('amount');
        });

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'lastYear' => $lastYearData,
                'currentYear' => $currentYearData
            ]
        ]);
    }

    public function getPaidPayments()
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        // Get latest 100 payment intents WITH payment method expanded
        $paymentIntents = \Stripe\PaymentIntent::all([
            'limit' => 100,
            'expand' => ['data.payment_method'], // 👈 this is the key
        ]);

        $data = collect($paymentIntents->data)
            ->filter(fn($pi) => $pi->status === 'succeeded')
            ->sortByDesc(fn($pi) => $pi->created)
            ->take(4)
            ->map(function ($pi) {
                $charge = $pi->charges->data[0] ?? null;
                $pm     = $pi->payment_method; // expanded PaymentMethod

                return [
                    'owner' => $pm?->billing_details?->name
                        ?? $pm?->billing_details?->email
                        ?? $charge?->billing_details?->name
                        ?? $charge?->billing_details?->email
                        ?? 'Unknown',

                    'date'  => date('Y-m-d', $pi->created),

                    'price' => '$' . number_format($pi->amount_received / 100, 2),

                    'type'  => $pm?->card?->brand
                        ? ucfirst($pm->card->brand) . ' card'
                        : ($charge?->payment_method_details?->type ?? 'Unknown'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }
}
