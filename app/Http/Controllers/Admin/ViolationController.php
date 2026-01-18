<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowTransaction;
use App\Models\User;
use App\Models\Violation;
use App\Services\SanctionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ViolationController extends Controller
{
    public function __construct(private SanctionService $sanctionService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'active');

        $violations = Violation::with(['student', 'transaction.student', 'transaction.laptop'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('occurred_at')
            ->get();

        debug_event('Admin:Violations', 'Menampilkan data pelanggaran', [
            'status' => $status,
            'total' => $violations->count(),
        ]);

        return view('admin.violations.index', compact('violations', 'status'));
    }

    public function create()
    {
        $students = User::students()
            ->orderBy('name')
            ->get();

        return view('admin.violations.create', compact('students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'student')),
            ],
            'transaction_code' => ['nullable', 'string'],
            'points' => ['required', 'integer', 'min:1', 'max:10'],
            'occurred_at' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $student = User::students()->findOrFail($validated['student_id']);

        $transaction = null;
        $transactionCode = trim((string) ($validated['transaction_code'] ?? ''));
        if ($transactionCode !== '') {
            $transaction = BorrowTransaction::where('transaction_code', $transactionCode)->first();
            if (!$transaction) {
                return back()
                    ->withErrors(['transaction_code' => 'Kode transaksi tidak ditemukan.'])
                    ->withInput();
            }

            if ($transaction->student_id !== $student->id) {
                return back()
                    ->withErrors(['transaction_code' => 'Transaksi tersebut tidak dimiliki siswa yang dipilih.'])
                    ->withInput();
            }
        }

        $occurredAt = Carbon::parse($validated['occurred_at']);

        $violation = Violation::create([
            'user_id' => $student->id,
            'borrow_transaction_id' => $transaction?->id,
            'status' => 'active',
            'points' => $validated['points'],
            'notes' => $validated['notes'],
            'occurred_at' => $occurredAt,
        ]);

        $student->increment('violations_count', $violation->points);
        $student->refresh();

        $violationLimit = config('lending.violation_limit', 3);
        if ($student->violations_count >= $violationLimit && (!$student->sanction_ends_at || now()->greaterThanOrEqualTo($student->sanction_ends_at))) {
            $sanctionDays = config('lending.sanction_length_days', 7);
            $this->sanctionService->apply(
                student: $student,
                sanctionDays: $sanctionDays,
                issuedBy: $request->user(),
                reason: "Pelanggaran manual - mencapai {$violationLimit} pelanggaran"
            );
        } else {
            $this->sanctionService->refresh($student);
        }

        debug_event('Admin:Violations', 'Pelanggaran manual ditambahkan', [
            'violation_id' => $violation->id,
            'student' => $student->student_number,
            'points' => $violation->points,
        ]);

        return redirect()
            ->route('admin.violations.index')
            ->with('status', 'Pelanggaran baru berhasil ditambahkan.');
    }

    public function update(Request $request, Violation $violation)
    {
        $violation->resolve();

        if ($violation->student && $violation->student->violations_count > 0) {
            $violation->student->decrement('violations_count', min($violation->points, $violation->student->violations_count));
        }

        debug_event('Admin:Violations', 'Pelanggaran ditandai selesai', [
            'violation_id' => $violation->id,
        ]);

        return redirect()
            ->route('admin.violations.index')
            ->with('status', 'Pelanggaran berhasil ditandai selesai.');
    }
}
