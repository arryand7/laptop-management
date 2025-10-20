<?php

namespace App\Http\Controllers;

use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use App\Services\SanctionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SanctionService $sanctionService)
    {
        $user = $request->user();

        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();

        $overview = [
            'today_borrowings' => BorrowTransaction::whereBetween('borrowed_at', [$today, $today->copy()->endOfDay()])->count(),
            'week_borrowings' => BorrowTransaction::whereBetween('borrowed_at', [$startOfWeek, $now])->count(),
            'month_borrowings' => BorrowTransaction::whereBetween('borrowed_at', [$startOfMonth, $now])->count(),
            'active_borrowings' => BorrowTransaction::where('status', 'borrowed')->count(),
            'late_returns' => BorrowTransaction::where('was_late', true)->count(),
            'sanctioned_students' => User::students()->whereNotNull('sanction_ends_at')->count(),
        ];

        $recentBorrowData = BorrowTransaction::with('student', 'laptop')
            ->orderByDesc('borrowed_at')
            ->limit(5)
            ->get();

        $borrowedLaptopList = Laptop::with([
                'borrowTransactions' => fn ($query) => $query->where('status', 'borrowed')->latest('borrowed_at')->limit(1),
                'borrowTransactions.student',
                'owner',
            ])
            ->where('status', 'borrowed')
            ->get();

        $laptopOverviewStats = [
            'total' => Laptop::count(),
            'male' => Laptop::whereHas('owner', fn ($query) => $query->where('gender', 'male'))->count(),
            'female' => Laptop::whereHas('owner', fn ($query) => $query->where('gender', 'female'))->count(),
        ];

        $laptopClassBreakdown = Laptop::with('owner')
            ->get()
            ->groupBy(fn ($laptop) => $laptop->owner?->classroom ?: 'Tanpa Kelas')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $statusConfig = collect([
            'borrowed' => 'Dipinjam',
            'maintenance' => 'Maintenance',
            'retired' => 'Nonaktif',
        ]);

        $statusCounts = Laptop::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereIn('status', $statusConfig->keys())
            ->groupBy('status')
            ->pluck('total', 'status');

        $laptopStatusSummary = $statusConfig->map(function ($label, $status) use ($statusCounts) {
            return [
                'status' => $status,
                'label' => $label,
                'total' => (int) ($statusCounts[$status] ?? 0),
            ];
        })->values();

        $topViolators = User::students()
            ->orderByDesc('violations_count')
            ->limit(5)
            ->get();

        $recentBorrows = BorrowTransaction::where('borrowed_at', '>=', $now->copy()->subDays(6)->startOfDay())->get();
        $dailyBorrowSeries = collect(range(6, 0))->map(function ($daysAgo) use ($recentBorrows, $now) {
            $date = $now->copy()->subDays($daysAgo)->startOfDay();
            $count = $recentBorrows->filter(fn ($trx) => $trx->borrowed_at?->isSameDay($date))->count();

            return [
                'label' => $date->translatedFormat('d M'),
                'value' => $count,
            ];
        });

        $lateReturns = BorrowTransaction::where('was_late', true)
            ->whereNotNull('returned_at')
            ->where('returned_at', '>=', $now->copy()->subWeeks(4)->startOfWeek())
            ->get();

        $weeklyLateSeries = collect(range(3, 0))->map(function ($weeksAgo) use ($lateReturns, $now) {
            $start = $now->copy()->subWeeks($weeksAgo)->startOfWeek();
            $end = $start->copy()->endOfWeek();
            $count = $lateReturns->filter(fn ($trx) => $trx->returned_at?->betweenIncluded($start, $end))->count();

            return [
                'label' => $start->translatedFormat('d M') . ' - ' . $end->translatedFormat('d M'),
                'value' => $count,
            ];
        });

        $studentSummary = null;
        if ($user->isStudent()) {
            $sanctionService->refresh($user);

            $studentSummary = [
                'active_borrowings' => $user->borrowTransactionsAsStudent()->where('status', 'borrowed')->with('laptop')->get(),
                'history' => $user->borrowTransactionsAsStudent()
                    ->with('laptop', 'staff')
                    ->orderByDesc('borrowed_at')
                    ->limit(8)
                    ->get(),
                'sanctions' => $user->sanctions()->orderByDesc('starts_at')->limit(5)->get(),
            ];
        }

        $activeBorrowList = BorrowTransaction::with(['laptop.owner', 'student'])
            ->where('status', 'borrowed')
            ->latest('borrowed_at')
            ->limit(10)
            ->get();

        debug_event('Dashboard', 'Data dashboard dihitung', [
            'role' => $user->role,
        ]);

        return view('dashboard.index', [
            'user' => $user,
            'overview' => $overview,
            'recentBorrowData' => $recentBorrowData,
            'borrowedLaptopList' => $borrowedLaptopList,
            'activeBorrowList' => $activeBorrowList,
            'laptopOverviewStats' => $laptopOverviewStats,
            'laptopClassBreakdown' => $laptopClassBreakdown,
            'laptopStatusSummary' => $laptopStatusSummary,
            'topViolators' => $topViolators,
            'dailyBorrowSeries' => $dailyBorrowSeries,
            'weeklyLateSeries' => $weeklyLateSeries,
            'studentSummary' => $studentSummary,
        ]);
    }
}
