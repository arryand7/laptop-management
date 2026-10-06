<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowTransaction;
use App\Models\User;
use App\Services\Students\StudentImportService;
use App\Support\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
            'nisn' => ['nullable', 'string', 'max:20', 'unique:users,nisn'],
            'card_code' => ['nullable', 'string', 'max:255', 'unique:users,card_code'],
            'gender' => ['required', 'in:male,female'],
            'classroom' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ]);

        $plainPassword = $validated['password'] ?? Str::random(10);
        $cardCode = $validated['card_code'] ?? Str::random(64);

        $student = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'student_number' => $validated['student_number'],
            'nisn' => $validated['nisn'] ?? null,
            'card_code' => $cardCode,
            'gender' => $validated['gender'],
            'classroom' => $validated['classroom'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($plainPassword),
            'role' => 'student',
            'qr_code' => $cardCode,
            'violations_count' => 0,
            'is_active' => true,
            'avatar_path' => $request->file('avatar')?->store('avatars', 'public'),
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
            'nisn' => ['nullable', 'string', 'max:20', 'unique:users,nisn,' . $student->id],
            'card_code' => ['nullable', 'string', 'max:255', 'unique:users,card_code,' . $student->id],
            'gender' => ['required', 'in:male,female'],
            'classroom' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ]);

        $student->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'student_number' => $validated['student_number'],
            'nisn' => $validated['nisn'] ?? null,
            'card_code' => $validated['card_code'] ?? $student->card_code ?? Str::random(64),
            'gender' => $validated['gender'],
            'classroom' => $validated['classroom'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (!empty($validated['password'])) {
            $student->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($student->avatar_path && Storage::disk('public')->exists($student->avatar_path)) {
                Storage::disk('public')->delete($student->avatar_path);
            }
            $student->avatar_path = $request->file('avatar')->store('avatars', 'public');
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

    private const IMPORT_DIR = 'import-tmp';

    /**
     * Langkah 1: upload file lalu tampilkan hasil dry run (tidak ada data yang diubah).
     */
    public function import(Request $request, StudentImportService $service)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        $this->purgeOldImportFiles();
        $this->discardPendingImport($request);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        $path = $file->storeAs(self::IMPORT_DIR, Str::uuid() . '.' . $extension, 'local');

        try {
            $rows = $service->readRows($path);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            return redirect()
                ->route('admin.students.index')
                ->withErrors('File tidak dapat dibaca: ' . $e->getMessage());
        }

        if (empty($rows)) {
            Storage::disk('local')->delete($path);

            return redirect()
                ->route('admin.students.index')
                ->withErrors('File kosong atau baris heading tidak ditemukan. Gunakan template import.');
        }

        if (count($rows) > StudentImportService::MAX_ROWS) {
            Storage::disk('local')->delete($path);

            return redirect()
                ->route('admin.students.index')
                ->withErrors('Maksimal ' . StudentImportService::MAX_ROWS . ' baris per import.');
        }

        $request->session()->put('student_import', [
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
        ]);

        debug_event('Admin:Students', 'Dry run import siswa', ['filename' => $file->getClientOriginalName(), 'rows' => count($rows)]);

        return redirect()->route('admin.students.import.preview');
    }

    /**
     * Halaman hasil dry run.
     */
    public function importPreview(Request $request, StudentImportService $service)
    {
        $pending = $request->session()->get('student_import');

        if (!$pending || !Storage::disk('local')->exists($pending['path'])) {
            $request->session()->forget('student_import');

            return redirect()
                ->route('admin.students.index')
                ->withErrors('Sesi import tidak ditemukan atau sudah kedaluwarsa. Silakan upload ulang file.');
        }

        $plan = $service->analyze($service->readRows($pending['path']));
        $filename = $pending['filename'];
        $labels = StudentImportService::FIELD_LABELS;

        return view('admin.students.import-preview', compact('plan', 'filename', 'labels'));
    }

    /**
     * Langkah 2: eksekusi import. File dianalisis ulang agar data selalu terkini.
     */
    public function importCommit(Request $request, StudentImportService $service)
    {
        $validated = $request->validate([
            'default_password' => ['nullable', 'string', 'min:6'],
            'abort_on_error' => ['nullable', 'boolean'],
        ]);

        $pending = $request->session()->get('student_import');

        if (!$pending || !Storage::disk('local')->exists($pending['path'])) {
            $request->session()->forget('student_import');

            return redirect()
                ->route('admin.students.index')
                ->withErrors('Sesi import tidak ditemukan atau sudah kedaluwarsa. Silakan upload ulang file.');
        }

        $plan = $service->analyze($service->readRows($pending['path']));
        $result = $service->commit($plan, $validated['default_password'] ?? null, $request->boolean('abort_on_error'));

        if ($result['aborted']) {
            return redirect()
                ->route('admin.students.import.preview')
                ->withErrors("Import dibatalkan karena {$result['errors']} baris error. Tidak ada data yang diubah.");
        }

        $this->discardPendingImport($request);

        $errorLines = collect($plan['rows'])
            ->where('status', StudentImportService::STATUS_ERROR)
            ->map(fn ($row) => "Baris {$row['line']} ({$row['identifier']}): " . implode(' ', $row['errors']))
            ->values()
            ->all();

        debug_event('Admin:Students', 'Import siswa dieksekusi', $result);

        return redirect()
            ->route('admin.students.index')
            ->with('status', "Import selesai: {$result['created']} ditambahkan, {$result['updated']} diperbarui, {$result['unchanged']} tidak berubah, {$result['errors']} dilewati karena error.")
            ->with('import_errors', $errorLines);
    }

    public function importCancel(Request $request)
    {
        $this->discardPendingImport($request);

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Import dibatalkan. Tidak ada data yang diubah.');
    }

    private function discardPendingImport(Request $request): void
    {
        $pending = $request->session()->pull('student_import');

        if ($pending && !empty($pending['path'])) {
            Storage::disk('local')->delete($pending['path']);
        }
    }

    private function purgeOldImportFiles(): void
    {
        $disk = Storage::disk('local');

        foreach ($disk->files(self::IMPORT_DIR) as $file) {
            if ($disk->lastModified($file) < now()->subDay()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/import-templates/users_template.csv');

        abort_unless(file_exists($path), 404);

        debug_event('Admin:Students', 'Download template import', []);

        return response()->download($path, 'template-import-siswa.csv');
    }

    /**
     * Ekspor data siswa saat ini dengan format yang sama dengan template import,
     * sehingga bisa diisi lalu diimpor kembali untuk update massal.
     */
    public function export()
    {
        debug_event('Admin:Students', 'Export data siswa', []);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['name', 'email', 'student_number', 'nisn', 'classroom', 'gender', 'phone', 'is_active', 'card_code', 'password']);

            User::students()->orderBy('name')->chunk(200, function ($students) use ($out) {
                foreach ($students as $s) {
                    fputcsv($out, [
                        $s->name,
                        $s->email,
                        $s->student_number,
                        $s->nisn,
                        $s->classroom,
                        $s->gender === 'male' ? 'L' : ($s->gender === 'female' ? 'P' : ''),
                        $s->phone,
                        $s->is_active ? 'true' : 'false',
                        $s->card_code,
                        '',
                    ]);
                }
            });

            fclose($out);
        }, 'data-siswa-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
