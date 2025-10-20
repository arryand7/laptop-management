<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\LaptopsImport;
use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Maatwebsite\Excel\Facades\Excel;

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

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        Excel::import(new LaptopsImport(), $validated['file']);

        debug_event('Admin:Laptops', 'Import Excel berhasil', ['filename' => $validated['file']->getClientOriginalName()]);

        return redirect()
            ->route('admin.laptops.index')
            ->with('status', 'Data laptop berhasil diimport.');
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/import-templates/laptops_template.csv');

        abort_unless(file_exists($path), 404);

        debug_event('Admin:Laptops', 'Download template import', []);

        return response()->download($path, 'template-import-laptop.csv');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'laptop_ids' => ['required', 'array', 'min:1'],
            'laptop_ids.*' => ['integer', Rule::exists('laptops', 'id')],
            'action' => ['required', Rule::in(['status', 'delete'])],
            'status' => ['nullable', Rule::in(['available', 'borrowed', 'maintenance', 'retired'])],
        ]);

        $laptops = Laptop::with('borrowTransactions')->whereIn('id', $validated['laptop_ids'])->get();

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
