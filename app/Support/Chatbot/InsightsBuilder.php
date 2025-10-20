<?php

namespace App\Support\Chatbot;

use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Support\Carbon;

class InsightsBuilder
{
    public function build(): array
    {
        $now = Carbon::now();

        $totalLaptops = Laptop::count();
        $statusCounts = Laptop::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $activeBorrowings = BorrowTransaction::where('status', 'borrowed')->count();
        $overdueBorrowings = BorrowTransaction::where('status', 'borrowed')
            ->where('due_at', '<', $now)
            ->count();

        $studentsTotal = User::students()->count();
        $studentsActive = User::students()->where('is_active', true)->count();

        $topViolators = User::students()
            ->where('violations_count', '>', 0)
            ->orderByDesc('violations_count')
            ->limit(5)
            ->get(['name', 'student_number', 'classroom', 'violations_count']);

        $recentBorrowings = BorrowTransaction::with('student:id,name,student_number,classroom', 'laptop:id,code,name')
            ->latest('borrowed_at')
            ->limit(5)
            ->get(['id', 'borrowed_at', 'due_at', 'student_id', 'laptop_id', 'status']);

        $recentViolations = Violation::with('student:id,name,student_number,classroom')
            ->latest('occurred_at')
            ->limit(5)
            ->get(['id', 'user_id', 'points', 'notes', 'occurred_at']);

        return [
            'generated_at' => $now->toIso8601String(),
            'summary' => [
                'total_laptops' => $totalLaptops,
                'status_breakdown' => $statusCounts,
                'borrowed_count' => $activeBorrowings,
                'overdue_count' => $overdueBorrowings,
                'students_total' => $studentsTotal,
                'students_active' => $studentsActive,
            ],
            'top_violators' => $topViolators->map(function ($student) {
                return [
                    'name' => $student->name,
                    'nis' => $student->student_number,
                    'classroom' => $student->classroom,
                    'count' => $student->violations_count,
                ];
            }),
            'recent_borrowings' => $recentBorrowings->map(function ($trx) {
                return [
                    'borrowed_at' => optional($trx->borrowed_at)->toIso8601String(),
                    'due_at' => optional($trx->due_at)->toIso8601String(),
                    'status' => $trx->status,
                    'student' => $trx->student ? [
                        'name' => $trx->student->name,
                        'nis' => $trx->student->student_number,
                        'classroom' => $trx->student->classroom,
                    ] : null,
                    'laptop' => $trx->laptop ? [
                        'code' => $trx->laptop->code,
                        'name' => $trx->laptop->name,
                    ] : null,
                ];
            }),
            'recent_violations' => $recentViolations->map(function ($violation) {
                return [
                    'occurred_at' => optional($violation->occurred_at)->toIso8601String(),
                    'points' => $violation->points,
                    'notes' => $violation->notes,
                    'student' => $violation->student ? [
                        'name' => $violation->student->name,
                        'nis' => $violation->student->student_number,
                        'classroom' => $violation->student->classroom,
                    ] : null,
                ];
            }),
        ];
    }
}
