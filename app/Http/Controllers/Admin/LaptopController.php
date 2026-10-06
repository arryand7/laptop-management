<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use App\Services\Laptops\LaptopImportService;
use App\Support\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LaptopController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        $laptops = Laptop::query()
            ->with('owner')
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('code')
            ->get();

        debug_event('Admin:Laptops', 'Menampilkan daftar laptop', [
            'total' => $laptops->count(),
            'search' => $search,
            'status' => $status,
        ]);

        return view('admin.laptops.index', compact('laptops', 'search', 'status'));
    }

    public function create()
    {
        $students = User::students()->orderBy('name')->get();

        return view('admin.laptops.create', compact('students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100', 'unique:laptops,serial_number'],
            'status' => ['required', 'string', 'in:available,borrowed,maintenance,retired'],
            'notes' => ['nullable', 'string'],
            'spec_cpu' => ['nullable', 'string', 'max:100'],
            'spec_ram' => ['nullable', 'string', 'max:100'],
            'spec_storage' => ['nullable', 'string', 'max:100'],
            'spec_os' => ['nullable', 'string', 'max:100'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'student')],
        ]);

        $specifications = Arr::where([
            'cpu' => $validated['spec_cpu'] ?? null,
            'ram' => $validated['spec_ram'] ?? null,
            'storage' => $validated['spec_storage'] ?? null,
            'os' => $validated['spec_os'] ?? null,
        ], fn ($value) => !empty($value));

        $ownerId = $validated['owner_id'] ?? null;
        $ownerStudentNumber = null;
        if ($ownerId) {
            $ownerStudentNumber = User::students()->whereKey($ownerId)->value('student_number');
        }

        $qrCode = $ownerStudentNumber ?: CodeGenerator::laptopQr();

        $laptop = Laptop::create([
            'code' => CodeGenerator::laptopCode(),
            'name' => $validated['name'],
            'brand' => $validated['brand'] ?? null,
            'model' => $validated['model'] ?? null,
            'serial_number' => $validated['serial_number'] ?? null,
            'status' => $validated['status'],
            'owner_id' => $validated['owner_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'specifications' => $specifications ?: null,
            'qr_code' => $qrCode,
            'last_checked_at' => now(),
        ]);

        debug_event('Admin:Laptops', 'Laptop baru dibuat', ['code' => $laptop->code]);

        return redirect()
            ->route('admin.laptops.index')
            ->with('status', 'Data laptop berhasil ditambahkan.');
    }

    public function show(Laptop $laptop)
    {
        $activeBorrow = $laptop->borrowTransactions()->active()->with('student')->first();

        return view('admin.laptops.show', compact('laptop', 'activeBorrow'));
    }

    public function edit(Laptop $laptop)
    {
        $students = User::students()->orderBy('name')->get();

        return view('admin.laptops.edit', compact('laptop', 'students'));
    }

    public function update(Request $request, Laptop $laptop)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100', 'unique:laptops,serial_number,' . $laptop->id],
            'status' => ['required', 'string', 'in:available,borrowed,maintenance,retired'],
            'notes' => ['nullable', 'string'],
            'spec_cpu' => ['nullable', 'string', 'max:100'],
            'spec_ram' => ['nullable', 'string', 'max:100'],
            'spec_storage' => ['nullable', 'string', 'max:100'],
            'spec_os' => ['nullable', 'string', 'max:100'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'student')],
        ]);

        $specifications = Arr::where([
            'cpu' => $validated['spec_cpu'] ?? null,
            'ram' => $validated['spec_ram'] ?? null,
            'storage' => $validated['spec_storage'] ?? null,
            'os' => $validated['spec_os'] ?? null,
        ], fn ($value) => !empty($value));

        $ownerId = $validated['owner_id'] ?? null;
        $ownerStudentNumber = null;
        if ($ownerId) {
            $ownerStudentNumber = User::students()->whereKey($ownerId)->value('student_number');
        }

        $laptop->update([
            'name' => $validated['name'],
            'brand' => $validated['brand'] ?? null,
            'model' => $validated['model'] ?? null,
            'serial_number' => $validated['serial_number'] ?? null,
            'status' => $validated['status'],
            'owner_id' => $validated['owner_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'specifications' => $specifications ?: null,
        ]);

        if ($ownerStudentNumber) {
            $laptop->update(['qr_code' => $ownerStudentNumber]);
        }

        if ($request->boolean('regenerate_qr')) {
            $laptop->update(['qr_code' => $ownerStudentNumber ?: CodeGenerator::laptopQr()]);
        }

        debug_event('Admin:Laptops', 'Laptop diperbarui', ['code' => $laptop->code]);

        return redirect()
            ->route('admin.laptops.show', $laptop)
            ->with('status', 'Data laptop berhasil diperbarui.');
    }

    public function destroy(Laptop $laptop)
    {
        $hasActiveBorrow = $laptop->borrowTransactions()->whereIn('status', ['borrowed', 'late'])->exists();
        if ($hasActiveBorrow) {
            return redirect()
                ->route('admin.laptops.index')
                ->withErrors('Tidak dapat menghapus laptop yang masih dipinjam.');
        }

        $laptop->delete();

        debug_event('Admin:Laptops', 'Laptop dihapus', ['code' => $laptop->code]);

        return redirect()
            ->route('admin.laptops.index')
            ->with('status', 'Laptop berhasil dihapus.');
    }

    public function qr(Laptop $laptop)
    {
        $qrSvg = QrCode::format('svg')
            ->size(240)
            ->margin(1)
            ->generate($laptop->qr_code);

        return view('admin.laptops.qr', compact('laptop', 'qrSvg'));
    }

    private const IMPORT_DIR = 'import-tmp';
    private const IMPORT_SESSION_KEY = 'laptop_import';

    /**
     * Langkah 1: upload file lalu tampilkan hasil dry run (tidak ada data yang diubah).
     */
    public function import(Request $request, LaptopImportService $service)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        $this->purgeOldImportFiles();
        $this->discardPendingImport($request);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        $path = $file->storeAs(self::IMPORT_DIR, 'laptop-' . Str::uuid() . '.' . $extension, 'local');

        try {
            $rows = $service->readRows($path);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            return redirect()
                ->route('admin.laptops.index')
                ->withErrors('File tidak dapat dibaca: ' . $e->getMessage());
        }

        if (empty($rows)) {
            Storage::disk('local')->delete($path);

            return redirect()
                ->route('admin.laptops.index')
                ->withErrors('File kosong atau baris heading tidak ditemukan. Gunakan template import.');
        }

        if (count($rows) > LaptopImportService::MAX_ROWS) {
            Storage::disk('local')->delete($path);

            return redirect()
                ->route('admin.laptops.index')
                ->withErrors('Maksimal ' . LaptopImportService::MAX_ROWS . ' baris per import.');
        }

        $request->session()->put(self::IMPORT_SESSION_KEY, [
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
        ]);

        debug_event('Admin:Laptops', 'Dry run import laptop', ['filename' => $file->getClientOriginalName(), 'rows' => count($rows)]);

        return redirect()->route('admin.laptops.import.preview');
    }

    public function importPreview(Request $request, LaptopImportService $service)
    {
        $pending = $this->pendingImport($request);

        if (!$pending) {
            return redirect()
                ->route('admin.laptops.index')
                ->withErrors('Sesi import tidak ditemukan atau sudah kedaluwarsa. Silakan upload ulang file.');
        }

        $plan = $service->analyze($service->readRows($pending['path']));
        $filename = $pending['filename'];
        $labels = LaptopImportService::FIELD_LABELS;

        return view('admin.laptops.import-preview', compact('plan', 'filename', 'labels'));
    }

    /**
     * Langkah 2: eksekusi import. File dianalisis ulang agar data selalu terkini.
     */
    public function importCommit(Request $request, LaptopImportService $service)
    {
        $request->validate(['abort_on_error' => ['nullable', 'boolean']]);

        $pending = $this->pendingImport($request);

        if (!$pending) {
            return redirect()
                ->route('admin.laptops.index')
                ->withErrors('Sesi import tidak ditemukan atau sudah kedaluwarsa. Silakan upload ulang file.');
        }

        $plan = $service->analyze($service->readRows($pending['path']));
        $result = $service->commit($plan, $request->boolean('abort_on_error'));

        if ($result['aborted']) {
            return redirect()
                ->route('admin.laptops.import.preview')
                ->withErrors("Import dibatalkan karena {$result['errors']} baris error. Tidak ada data yang diubah.");
        }

        $this->discardPendingImport($request);

        $errorLines = collect($plan['rows'])
            ->where('status', LaptopImportService::STATUS_ERROR)
            ->map(fn ($row) => "Baris {$row['line']} ({$row['identifier']}): " . implode(' ', $row['errors']))
            ->values()
            ->all();

        debug_event('Admin:Laptops', 'Import laptop dieksekusi', $result);

        return redirect()
            ->route('admin.laptops.index')
            ->with('status', "Import selesai: {$result['created']} ditambahkan, {$result['updated']} diperbarui, {$result['unchanged']} tidak berubah, {$result['errors']} dilewati karena error.")
            ->with('import_errors', $errorLines);
    }

    public function importCancel(Request $request)
    {
        $this->discardPendingImport($request);

        return redirect()
            ->route('admin.laptops.index')
            ->with('status', 'Import dibatalkan. Tidak ada data yang diubah.');
    }

    /**
     * @return array{path: string, filename: string}|null
     */
    private function pendingImport(Request $request): ?array
    {
        $pending = $request->session()->get(self::IMPORT_SESSION_KEY);

        if (!$pending || !Storage::disk('local')->exists($pending['path'])) {
            $request->session()->forget(self::IMPORT_SESSION_KEY);

            return null;
        }

        return $pending;
    }

    private function discardPendingImport(Request $request): void
    {
        $pending = $request->session()->pull(self::IMPORT_SESSION_KEY);

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

    private const TEMPLATE_HEADERS = ['code', 'name', 'brand', 'model', 'serial_number', 'status', 'owner_student_number', 'notes', 'spec_cpu', 'spec_ram', 'spec_storage', 'spec_os', 'qr_code'];

    public function downloadTemplate()
    {
        debug_event('Admin:Laptops', 'Download template import', []);

        $samples = [
            ['LP-0001', 'Laptop Perpustakaan', 'Dell', 'Inspiron 14', 'DELL-001', 'available', '', '', 'Intel Core i5', '8GB', '512GB SSD', 'Windows 11', ''],
            ['LP-0002', 'Laptop Kelas XI', 'Acer', 'Swift 3', 'ACER-002', 'maintenance', '', 'Contoh baris update', 'Intel Core i7', '16GB', '512GB SSD', 'Windows 11', ''],
        ];

        return $this->streamCsv('template-import-laptop.csv', $samples);
    }

    /**
     * Ekspor data laptop saat ini dengan format yang sama dengan template import,
     * sehingga bisa diedit lalu diimpor kembali untuk update massal.
     */
    public function export()
    {
        debug_event('Admin:Laptops', 'Export data laptop', []);

        $rows = Laptop::query()->with('owner:id,student_number')->orderBy('code')->get()->map(fn (Laptop $l) => [
            $l->code,
            $l->name,
            $l->brand,
            $l->model,
            $l->serial_number,
            $l->status,
            $l->owner?->student_number,
            $l->notes,
            $l->specifications['cpu'] ?? null,
            $l->specifications['ram'] ?? null,
            $l->specifications['storage'] ?? null,
            $l->specifications['os'] ?? null,
            $l->qr_code,
        ])->all();

        return $this->streamCsv('data-laptop-' . now()->format('Ymd-His') . '.csv', $rows);
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function streamCsv(string $filename, array $rows)
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::TEMPLATE_HEADERS);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'laptop_ids' => ['required', 'array', 'min:1'],
            'laptop_ids.*' => ['integer', Rule::exists('laptops', 'id')],
            'action' => ['required', Rule::in(['status', 'delete', 'print_qr'])],
            'status' => ['nullable', Rule::in(['available', 'borrowed', 'maintenance', 'retired'])],
        ]);

        $laptops = Laptop::with(['borrowTransactions', 'owner'])->whereIn('id', $validated['laptop_ids'])->get();

        if ($validated['action'] === 'print_qr') {
            $entries = $laptops->map(function (Laptop $laptop) {
                return [
                    'laptop' => $laptop,
                    'qrSvg' => QrCode::format('svg')
                        ->size(180)
                        ->margin(0)
                        ->generate($laptop->qr_code),
                ];
            });

            return view('admin.laptops.qr-bulk', [
                'entries' => $entries,
                'generatedAt' => now(),
            ]);
        }

        if ($validated['action'] === 'delete') {
            $blocked = [];
            $deletedCount = 0;

            foreach ($laptops as $laptop) {
                $hasActiveBorrow = $laptop->borrowTransactions
                    ->contains(fn ($trx) => in_array($trx->status, ['borrowed', 'late'], true));

                if ($hasActiveBorrow) {
                    $blocked[] = $laptop->code;
                    continue;
                }

                $laptop->delete();
                $deletedCount++;
            }

            $message = $deletedCount > 0
                ? "{$deletedCount} laptop berhasil dihapus."
                : 'Tidak ada laptop yang dapat dihapus.';

            if (!empty($blocked)) {
                $message .= ' Beberapa laptop tidak dihapus karena masih dipinjam: ' . implode(', ', array_slice($blocked, 0, 5)) . (count($blocked) > 5 ? '…' : '');
            }

            return redirect()
                ->route('admin.laptops.index')
                ->with('status', $message);
        }

        if ($validated['action'] === 'status' && empty($validated['status'])) {
            return redirect()
                ->route('admin.laptops.index')
                ->withErrors('Pilih status baru sebelum menerapkan perubahan.');
        }

        $status = $validated['status'] ?? 'available';

        $updated = Laptop::whereIn('id', $laptops->pluck('id'))
            ->update(['status' => $status]);

        debug_event('Admin:Laptops', 'Bulk status update', [
            'count' => $updated,
            'status' => $status,
        ]);

        return redirect()
            ->route('admin.laptops.index')
            ->with('status', "{$updated} laptop berhasil diperbarui ke status {$status}.");
    }
}
