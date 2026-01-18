<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sanction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SanctionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'active');

        $sanctions = Sanction::with(['student.latestBorrowTransaction.laptop'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('starts_at')
            ->get();

        debug_event('Admin:Sanctions', 'Menampilkan data sanksi', [
            'status' => $status,
            'total' => $sanctions->count(),
        ]);

        return view('admin.sanctions.index', compact('sanctions', 'status'));
    }

    public function create()
    {
        $students = User::students()
            ->orderBy('name')
            ->get();

        return view('admin.sanctions.create', compact('students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'student')),
            ],
            'reason' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $student = User::students()->findOrFail($validated['student_id']);

        $startsAt = Carbon::parse($validated['starts_at']);
        $endsAt = Carbon::parse($validated['ends_at']);

        $sanction = Sanction::create([
            'user_id' => $student->id,
            'issued_by' => $request->user()->id,
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'reason' => $validated['reason'],
        ]);

        $student->update([
            'sanction_ends_at' => $endsAt,
        ]);

        debug_event('Admin:Sanctions', 'Sanksi manual dibuat', [
            'sanction_id' => $sanction->id,
            'student' => $student->student_number,
        ]);

        return redirect()
            ->route('admin.sanctions.index')
            ->with('status', 'Sanksi baru berhasil dibuat.');
    }

    public function update(Request $request, Sanction $sanction)
    {
        $action = $request->input('action', 'expire');
        $now = now();

        if ($action === 'revoke') {
            $sanction->update([
                'status' => 'revoked',
                'ends_at' => $now,
            ]);
        } else {
            $sanction->update([
                'status' => 'expired',
                'ends_at' => $now,
            ]);
        }

        if ($sanction->student) {
            $hasActive = $sanction->student->sanctions()
                ->where('status', 'active')
                ->where('ends_at', '>', $now)
                ->exists();

            if (!$hasActive) {
                $sanction->student->update(['sanction_ends_at' => null]);
            }
        }

        debug_event('Admin:Sanctions', 'Sanksi diperbarui', [
            'sanction_id' => $sanction->id,
            'action' => $action,
        ]);

        return redirect()
            ->route('admin.sanctions.index')
            ->with('status', 'Status sanksi diperbarui.');
    }
}
