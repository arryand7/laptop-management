<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\SanctionService;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function __invoke(Request $request, SanctionService $sanctionService)
    {
        $student = $request->user();
        $sanctionService->refresh($student);

        $activeBorrowings = $student->borrowTransactionsAsStudent()
            ->where('status', 'borrowed')
            ->with('laptop')
            ->get();

        $history = $student->borrowTransactionsAsStudent()
            ->with('laptop', 'staff')
            ->orderByDesc('borrowed_at')
            ->get();

        return view('student.history', compact('student', 'activeBorrowings', 'history'));
    }
}
