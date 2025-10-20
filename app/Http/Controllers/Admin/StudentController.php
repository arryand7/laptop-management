<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\UsersImport;
use App\Models\BorrowTransaction;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));

        $students = User::students()
            ->withCount('ownedLaptops')
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%")
                        ->orWhere('classroom', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        debug_event('Admin:Students', 'Menampilkan daftar siswa', ['total' => $students->count(), 'search' => $search]);

        return view('admin.students.index', compact('students', 'search'));
    }

    public function create()
    {
        return view('admin.students.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'student_number' => ['required', 'string', 'max:50', 'unique:users,student_number'],
            'card_code' => ['nullable', 'string', 'max:255', 'unique:users,card_code'],
            'gender' => ['required', 'in:male,female'],
            'classroom' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $plainPassword = $validated['password'] ?? Str::random(10);
        $cardCode = $validated['card_code'] ?? Str::random(64);

        $student = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'student_number' => $validated['student_number'],
            'card_code' => $cardCode,
            'gender' => $validated['gender'],
            'classroom' => $validated['classroom'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($plainPassword),
            'role' => 'student',
            'qr_code' => $cardCode,
            'violations_count' => 0,
            'is_active' => true,
        ]);

        debug_event('Admin:Students', 'Siswa baru dibuat', ['student' => $student->student_number]);

        $redirect = redirect()
            ->route('admin.students.index')
            ->with('status', 'Siswa berhasil ditambahkan.');

        if (empty($validated['password'])) {
            $redirect->with('generated_password', $plainPassword);
        }

        return $redirect;
    }

    public function show(User $student)
    {
        abort_unless($student->isStudent(), 404);

        $activeBorrowings = $student->borrowTransactionsAsStudent()
            ->active()
            ->with('laptop')
            ->get();

        $ownedLaptops = $student->ownedLaptops()->orderBy('code')->get();

        $history = $student->borrowTransactionsAsStudent()
            ->with('laptop')
            ->orderByDesc('borrowed_at')
            ->limit(20)
            ->get();

        debug_event('Admin:Students', 'Melihat detail siswa', [
            'student' => $student->student_number,
            'active_borrowings' => $activeBorrowings->count(),
        ]);

        return view('admin.students.show', compact('student', 'activeBorrowings', 'ownedLaptops', 'history'));
    }

    public function edit(User $student)
    {
        abort_unless($student->isStudent(), 404);

        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, User $student)
    {
        abort_unless($student->isStudent(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $student->id],
            'student_number' => ['required', 'string', 'max:50', 'unique:users,student_number,' . $student->id],
            'card_code' => ['nullable', 'string', 'max:255', 'unique:users,card_code,' . $student->id],
            'gender' => ['required', 'in:male,female'],
            'classroom' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $student->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'student_number' => $validated['student_number'],
            'card_code' => $validated['card_code'] ?? $student->card_code ?? Str::random(64),
            'gender' => $validated['gender'],
            'classroom' => $validated['classroom'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (!empty($validated['password'])) {
            $student->password = Hash::make($validated['password']);
        }

        if ($student->isDirty('card_code')) {
            $student->qr_code = $student->card_code;
        }

        $student->save();

        debug_event('Admin:Students', 'Data siswa diperbarui', ['student' => $student->student_number]);

        return redirect()
            ->route('admin.students.show', $student)
            ->with('status', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(User $student)
    {
        abort_unless($student->isStudent(), 404);

        $hasActiveBorrow = BorrowTransaction::where('student_id', $student->id)
            ->whereIn('status', ['borrowed', 'late'])
            ->exists();

        if ($hasActiveBorrow) {
            return redirect()
                ->route('admin.students.index')
                ->withErrors('Tidak dapat menghapus siswa yang masih memiliki peminjaman aktif.');
        }

        $student->delete();

        debug_event('Admin:Students', 'Siswa dihapus', ['student' => $student->student_number]);

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Siswa berhasil dihapus.');
    }

    public function qr(User $student)
    {
        abort_unless($student->isStudent(), 404);

        if (!$student->card_code) {
            $student->card_code = Str::random(64);
        }

        if (!$student->qr_code || $student->qr_code !== $student->card_code) {
            $student->qr_code = $student->card_code;
        }

        $student->save();

        $qrSvg = QrCode::format('svg')
            ->size(240)
            ->margin(1)
            ->generate($student->card_code);

        debug_event('Admin:Students', 'Menampilkan QR siswa', ['student' => $student->student_number]);

        return view('admin.students.qr', compact('student', 'qrSvg'));
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
            'default_password' => ['nullable', 'string', 'min:6'],
        ]);

        $defaultPassword = $validated['default_password'] ?? 'password';

        Excel::import(new UsersImport($defaultPassword), $validated['file']);

        debug_event('Admin:Students', 'Import Excel berhasil', ['filename' => $validated['file']->getClientOriginalName()]);

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Data siswa berhasil diimport.');
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/import-templates/users_template.csv');

        abort_unless(file_exists($path), 404);

        debug_event('Admin:Students', 'Download template import', []);

        return response()->download($path, 'template-import-siswa.csv');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', 'student')],
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
        ]);

        $students = User::students()->whereIn('id', $validated['student_ids'])->get();

        if ($validated['action'] === 'delete') {
            $blocked = [];
            $deletedCount = 0;

            foreach ($students as $student) {
                $hasActiveBorrow = $student->borrowTransactionsAsStudent()
                    ->whereIn('status', ['borrowed', 'late'])
                    ->exists();

                if ($hasActiveBorrow) {
                    $blocked[] = $student->student_number ?? $student->name;
                    continue;
                }

                $student->delete();
                $deletedCount++;
            }

            $message = $deletedCount > 0
                ? "{$deletedCount} siswa berhasil dihapus."
                : 'Tidak ada siswa yang dapat dihapus.';

            if (!empty($blocked)) {
                $message .= ' Beberapa siswa tidak dihapus karena masih memiliki peminjaman aktif: ' . implode(', ', array_slice($blocked, 0, 5)) . (count($blocked) > 5 ? '…' : '');
            }

            return redirect()
                ->route('admin.students.index')
                ->with('status', $message);
        }

        $isActive = $validated['action'] === 'activate';

        $updated = User::students()
            ->whereIn('id', $students->pluck('id'))
            ->update(['is_active' => $isActive]);

        $message = $isActive
            ? "{$updated} siswa berhasil diaktifkan."
            : "{$updated} siswa berhasil dinonaktifkan.";

        return redirect()
            ->route('admin.students.index')
            ->with('status', $message);
    }
}
