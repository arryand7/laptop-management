<?php

namespace App\Http\Controllers;

use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LandingController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        $laptops = Laptop::with('owner')
            ->orderBy('name')
            ->get();

        $totalLaptops = $laptops->count();
        $statusSummary = $laptops
            ->groupBy('status')
            ->map(fn ($collection, $status) => [
                'status' => $status,
                'total' => $collection->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $retiredLaptops = $laptops
            ->where('status', 'retired')
            ->values();

        $genderCounts = [
            'male' => $laptops->filter(fn ($laptop) => $laptop->owner?->gender === 'male')->count(),
            'female' => $laptops->filter(fn ($laptop) => $laptop->owner?->gender === 'female')->count(),
        ];

        $borrowedLaptops = BorrowTransaction::with(['student', 'laptop'])
            ->where('status', 'borrowed')
            ->latest('borrowed_at')
            ->limit(5)
            ->get();

        $recentActivities = BorrowTransaction::with(['student', 'laptop'])
            ->latest('borrowed_at')
            ->limit(6)
            ->get();

        $topViolators = User::students()
            ->orderByDesc('violations_count')
            ->limit(5)
            ->get();

        $now = Carbon::now();
        $borrowCounts = BorrowTransaction::selectRaw('DATE(borrowed_at) as day, COUNT(*) as total')
            ->whereNotNull('borrowed_at')
            ->where('borrowed_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        $violationCounts = Violation::selectRaw('DATE(occurred_at) as day, COUNT(*) as total')
            ->whereNotNull('occurred_at')
            ->where('occurred_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        $borrowSeries = collect(range(29, 0))->map(function ($daysAgo) use ($now, $borrowCounts) {
            $date = $now->copy()->subDays($daysAgo)->startOfDay();
            $key = $date->toDateString();

            return [
                'label' => $date->format('d M'),
                'value' => (int) $borrowCounts->get($key, 0),
            ];
        });

        $violationSeries = collect(range(29, 0))->map(function ($daysAgo) use ($now, $violationCounts) {
            $date = $now->copy()->subDays($daysAgo)->startOfDay();
            $key = $date->toDateString();

            return [
                'label' => $date->format('d M'),
                'value' => (int) $violationCounts->get($key, 0),
            ];
        });

        return view('landing.index', [
            'totalLaptops' => $totalLaptops,
            'laptops' => $laptops,
            'statusSummary' => $statusSummary,
            'retiredLaptops' => $retiredLaptops,
            'laptopGenderCounts' => $genderCounts,
            'borrowedLaptops' => $borrowedLaptops,
            'recentActivities' => $recentActivities,
            'topViolators' => $topViolators,
            'borrowSeries' => $borrowSeries,
            'violationSeries' => $violationSeries,
        ]);
    }
}
