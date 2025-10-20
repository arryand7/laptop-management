<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laptop;
use App\Models\LaptopUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class LaptopUpdateRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', LaptopUpdateRequest::STATUS_PENDING);

        $query = LaptopUpdateRequest::query()
            ->with(['laptop.owner', 'student'])
            ->latest('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('admin.laptop_update_requests.index', [
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function show(LaptopUpdateRequest $laptopUpdateRequest)
    {
        $laptopUpdateRequest->load(['laptop.owner', 'student', 'admin']);

        return view('admin.laptop_update_requests.show', [
            'request' => $laptopUpdateRequest,
            'fieldDiffs' => $this->buildFieldDifferences($laptopUpdateRequest),
            'specDiffs' => $this->buildSpecificationDifferences($laptopUpdateRequest),
        ]);
    }

    public function approve(Request $request, LaptopUpdateRequest $laptopUpdateRequest)
    {
        abort_unless($laptopUpdateRequest->isPending(), 400, 'Permintaan sudah diproses.');

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $proposed = $laptopUpdateRequest->proposed_data ?? [];
        $specifications = Arr::where($proposed['specifications'] ?? [], fn ($value) => !empty($value));

        $laptop = $laptopUpdateRequest->laptop;

        if (!empty($proposed['serial_number']) && $proposed['serial_number'] !== $laptop->serial_number) {
            $serialExists = Laptop::where('serial_number', $proposed['serial_number'])
                ->where('id', '!=', $laptop->id)
                ->exists();

            if ($serialExists) {
                return back()
                    ->withErrors(['admin_notes' => 'Serial number baru sudah digunakan oleh laptop lain.'])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($laptopUpdateRequest, $laptop, $proposed, $specifications, $validated, $request) {
            $laptop->update([
                'name' => $proposed['name'] ?? $laptop->name,
                'brand' => $proposed['brand'] ?? null,
                'model' => $proposed['model'] ?? null,
                'serial_number' => $proposed['serial_number'] ?? null,
                'notes' => $proposed['notes'] ?? null,
                'specifications' => $specifications ?: null,
            ]);

            $laptopUpdateRequest->update([
                'status' => LaptopUpdateRequest::STATUS_APPROVED,
                'admin_id' => $request->user()->id,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'processed_at' => now(),
            ]);
        });

        debug_event('Admin:LaptopRequests', 'Permintaan perubahan laptop disetujui', [
            'request_id' => $laptopUpdateRequest->id,
            'laptop_id' => $laptopUpdateRequest->laptop_id,
        ]);

        return redirect()
            ->route('admin.laptop-requests.show', $laptopUpdateRequest)
            ->with('status', 'Perubahan laptop telah disetujui dan data diperbarui.');
    }

    public function reject(Request $request, LaptopUpdateRequest $laptopUpdateRequest)
    {
        abort_unless($laptopUpdateRequest->isPending(), 400, 'Permintaan sudah diproses.');

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $laptopUpdateRequest->update([
            'status' => LaptopUpdateRequest::STATUS_REJECTED,
            'admin_id' => $request->user()->id,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'processed_at' => now(),
        ]);

        debug_event('Admin:LaptopRequests', 'Permintaan perubahan laptop ditolak', [
            'request_id' => $laptopUpdateRequest->id,
            'laptop_id' => $laptopUpdateRequest->laptop_id,
        ]);

        return redirect()
            ->route('admin.laptop-requests.show', $laptopUpdateRequest)
            ->with('status', 'Permintaan perubahan ditolak.');
    }

    private function buildFieldDifferences(LaptopUpdateRequest $request): array
    {
        $fields = [
            'name' => 'Nama Laptop',
            'brand' => 'Brand',
            'model' => 'Model',
            'serial_number' => 'Serial Number',
            'notes' => 'Catatan',
        ];

        $original = $request->original_data ?? [];
        $proposed = $request->proposed_data ?? [];

        $diffs = [];
        foreach ($fields as $key => $label) {
            $originalValue = $original[$key] ?? null;
            $proposedValue = $proposed[$key] ?? null;
            $diffs[] = [
                'key' => $key,
                'label' => $label,
                'original' => $this->displayValue($originalValue),
                'proposed' => $this->displayValue($proposedValue),
                'changed' => $originalValue !== $proposedValue,
            ];
        }

        return $diffs;
    }

    private function buildSpecificationDifferences(LaptopUpdateRequest $request): array
    {
        $labels = [
            'cpu' => 'CPU',
            'ram' => 'RAM',
            'storage' => 'Storage',
            'os' => 'OS',
        ];

        $originalSpecs = $request->original_data['specifications'] ?? [];
        $proposedSpecs = $request->proposed_data['specifications'] ?? [];

        $keys = collect(array_keys($labels))
            ->merge(array_keys($originalSpecs))
            ->merge(array_keys($proposedSpecs))
            ->unique()
            ->all();

        $diffs = [];
        foreach ($keys as $key) {
            $originalValue = $originalSpecs[$key] ?? null;
            $proposedValue = $proposedSpecs[$key] ?? null;

            if ($originalValue === null && $proposedValue === null) {
                continue;
            }

            $diffs[] = [
                'key' => $key,
                'label' => $labels[$key] ?? ucfirst($key),
                'original' => $this->displayValue($originalValue),
                'proposed' => $this->displayValue($proposedValue),
                'changed' => $originalValue !== $proposedValue,
            ];
        }

        return $diffs;
    }

    private function displayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    }
}
