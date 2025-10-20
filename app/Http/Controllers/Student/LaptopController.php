<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Laptop;
use App\Models\LaptopUpdateRequest;
use Illuminate\Http\Request;

class LaptopController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();

        $laptops = $student->ownedLaptops()
            ->with([
                'updateRequests' => function ($query) use ($student) {
                    $query->where('student_id', $student->id)
                        ->latest('created_at');
                },
            ])
            ->orderBy('name')
            ->get();

        return view('student.laptops.index', [
            'laptops' => $laptops,
        ]);
    }

    public function edit(Request $request, Laptop $laptop)
    {
        $student = $request->user();
        $this->authorizeLaptop($laptop, $student->id);

        $pendingRequest = $laptop->updateRequests()
            ->where('student_id', $student->id)
            ->where('status', LaptopUpdateRequest::STATUS_PENDING)
            ->latest('created_at')
            ->first();

        $currentPayload = $this->composePayloadFromLaptop($laptop);
        $initialPayload = $pendingRequest?->proposed_data ?? $currentPayload;
        $formValues = $this->payloadToFormValues($initialPayload);

        return view('student.laptops.edit', [
            'laptop' => $laptop,
            'pendingRequest' => $pendingRequest,
            'currentPayload' => $currentPayload,
            'formValues' => $formValues,
        ]);
    }

    public function storeUpdateRequest(Request $request, Laptop $laptop)
    {
        $student = $request->user();
        $this->authorizeLaptop($laptop, $student->id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'spec_cpu' => ['nullable', 'string', 'max:100'],
            'spec_ram' => ['nullable', 'string', 'max:100'],
            'spec_storage' => ['nullable', 'string', 'max:100'],
            'spec_os' => ['nullable', 'string', 'max:100'],
        ]);

        $originalPayload = $this->composePayloadFromLaptop($laptop);
        $proposedPayload = $this->buildPayloadFromInput($validated);

        if ($this->payloadEquals($originalPayload, $proposedPayload)) {
            return back()
                ->withErrors(['name' => 'Tidak ada perubahan data yang diajukan.'])
                ->withInput();
        }

        $updateRequest = LaptopUpdateRequest::firstOrNew([
            'laptop_id' => $laptop->id,
            'student_id' => $student->id,
            'status' => LaptopUpdateRequest::STATUS_PENDING,
        ]);

        $updateRequest->original_data = $originalPayload;
        $updateRequest->proposed_data = $proposedPayload;
        $updateRequest->status = LaptopUpdateRequest::STATUS_PENDING;
        $updateRequest->admin_id = null;
        $updateRequest->admin_notes = null;
        $updateRequest->processed_at = null;
        $updateRequest->save();

        return redirect()
            ->route('student.laptops.index')
            ->with('status', 'Permintaan perubahan laptop telah dikirim dan menunggu konfirmasi admin.');
    }

    private function authorizeLaptop(Laptop $laptop, int $studentId): void
    {
        abort_unless($laptop->owner_id === $studentId, 403);
    }

    private function buildPayloadFromInput(array $input): array
    {
        return $this->normalizePayload([
            'name' => $input['name'],
            'brand' => $input['brand'] ?? null,
            'model' => $input['model'] ?? null,
            'serial_number' => $input['serial_number'] ?? null,
            'notes' => $input['notes'] ?? null,
            'specifications' => [
                'cpu' => $input['spec_cpu'] ?? null,
                'ram' => $input['spec_ram'] ?? null,
                'storage' => $input['spec_storage'] ?? null,
                'os' => $input['spec_os'] ?? null,
            ],
        ]);
    }

    private function composePayloadFromLaptop(Laptop $laptop): array
    {
        $specs = $laptop->specifications ?? [];

        return $this->normalizePayload([
            'name' => $laptop->name,
            'brand' => $laptop->brand,
            'model' => $laptop->model,
            'serial_number' => $laptop->serial_number,
            'notes' => $laptop->notes,
            'specifications' => [
                'cpu' => $specs['cpu'] ?? null,
                'ram' => $specs['ram'] ?? null,
                'storage' => $specs['storage'] ?? null,
                'os' => $specs['os'] ?? null,
            ],
        ]);
    }

    private function payloadToFormValues(array $payload): array
    {
        $specs = $payload['specifications'] ?? [];

        return [
            'name' => $payload['name'] ?? '',
            'brand' => $payload['brand'] ?? '',
            'model' => $payload['model'] ?? '',
            'serial_number' => $payload['serial_number'] ?? '',
            'notes' => $payload['notes'] ?? '',
            'spec_cpu' => $specs['cpu'] ?? '',
            'spec_ram' => $specs['ram'] ?? '',
            'spec_storage' => $specs['storage'] ?? '',
            'spec_os' => $specs['os'] ?? '',
        ];
    }

    private function payloadEquals(array $first, array $second): bool
    {
        return $this->normalizePayload($first) === $this->normalizePayload($second);
    }

    private function normalizePayload(array $payload): array
    {
        $normalizeOptional = fn ($value) => $this->sanitizeOptional($value);

        $normalized = [
            'name' => $this->sanitizeRequired($payload['name'] ?? ''),
            'brand' => $normalizeOptional($payload['brand'] ?? null),
            'model' => $normalizeOptional($payload['model'] ?? null),
            'serial_number' => $normalizeOptional($payload['serial_number'] ?? null),
            'notes' => $normalizeOptional($payload['notes'] ?? null),
        ];

        $specifications = $payload['specifications'] ?? [];
        $normalizedSpecs = [
            'cpu' => $normalizeOptional($specifications['cpu'] ?? null),
            'ram' => $normalizeOptional($specifications['ram'] ?? null),
            'storage' => $normalizeOptional($specifications['storage'] ?? null),
            'os' => $normalizeOptional($specifications['os'] ?? null),
        ];

        // Hanya pertahankan nilai spesifikasi yang terisi
        $normalized['specifications'] = collect($normalizedSpecs)
            ->reject(fn ($value) => $value === null)
            ->toArray();

        return $normalized;
    }

    private function sanitizeOptional(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function sanitizeRequired(mixed $value): string
    {
        $string = trim((string) $value);

        return $string;
    }
}
