<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\BorrowTransaction;
use App\Models\ChecklistDetail;
use App\Models\ChecklistSession;
use App\Models\Laptop;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChecklistController extends Controller
{
    public function create(Request $request): View
    {
        $filters = $this->extractFilters($request);

        $laptops = $this->laptopQueryWithFilters($filters)
            ->orderBy('code')
            ->get();

        $total = $laptops->count();
        $borrowedCount = $laptops->where('status', 'borrowed')->count();

        $classrooms = User::students()
            ->whereNotNull('classroom')
            ->distinct()
            ->orderBy('classroom')
            ->pluck('classroom');

        $genders = User::students()
            ->whereNotNull('gender')
            ->distinct()
            ->orderBy('gender')
            ->pluck('gender');

        return view('staff.checklist.create', [
            'laptops' => $laptops,
            'totalCount' => $total,
            'initialBorrowedCount' => $borrowedCount,
            'recentSession' => ChecklistSession::with('staff')->latest()->first(),
            'classrooms' => $classrooms,
            'genders' => $genders,
            'selectedFilters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'found_laptops' => ['array'],
            'found_laptops.*' => ['integer', 'exists:laptops,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $staff */
        $staff = $request->user();

        $foundIds = collect($validated['found_laptops'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $filters = $this->extractFilters($request);

        $laptops = $this->laptopQueryWithFilters($filters)
            ->orderBy('code')
            ->get();

        if ($laptops->isEmpty()) {
            return back()->withErrors('Tidak ada laptop untuk checklist berdasarkan filter yang dipilih.')->withInput();
        }

        $missingLaptops = collect();
        $borrowedLaptops = collect();

        $session = DB::transaction(function () use ($laptops, $foundIds, $staff, $validated, &$missingLaptops, &$borrowedLaptops) {
            $session = ChecklistSession::create([
                'staff_id' => $staff->id,
                'started_at' => now(),
                'note' => $validated['note'] ?? null,
                'total_laptops' => $laptops->count(),
            ]);

            $this->processChecklist(
                laptops: $laptops,
                foundIds: $foundIds,
                session: $session,
                missingLaptops: $missingLaptops,
                borrowedLaptops: $borrowedLaptops,
            );

            return $session;
        });

        return redirect()
            ->route('staff.checklist.show', $session)
            ->with('status', 'Checklist berhasil disimpan.')
            ->with('checklist_borrowed', $borrowedLaptops->pluck('code')->all())
            ->with('checklist_missing', $missingLaptops->pluck('code')->all());
    }

    public function history(): View
    {
        $sessions = ChecklistSession::with('staff')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('staff.checklist.history', [
            'sessions' => $sessions,
        ]);
    }

    public function show(ChecklistSession $session): View
    {
        $session->load([
            'staff',
            'details.laptop.owner',
        ]);

        $found = $session->details->where('status', 'found');
        $missing = $session->details->where('status', 'missing');
        $borrowed = $session->details->where('status', 'borrowed');

        return view('staff.checklist.show', [
            'session' => $session,
            'foundDetails' => $found,
            'missingDetails' => $missing,
            'borrowedDetails' => $borrowed,
        ]);
    }

    public function edit(ChecklistSession $session): View
    {
        $session->load(['details.laptop.owner']);

        $laptops = $session->details
            ->map(fn (ChecklistDetail $detail) => $detail->laptop)
            ->filter()
            ->sortBy('code')
            ->values();

        $foundIds = $session->details
            ->where('status', 'found')
            ->pluck('laptop_id')
            ->map(fn ($id) => (int) $id);

        $borrowedCount = $session->details->where('status', 'borrowed')->count();

        return view('staff.checklist.edit', [
            'session' => $session,
            'laptops' => $laptops,
            'foundIds' => $foundIds,
            'borrowedCount' => $borrowedCount,
        ]);
    }

    public function update(Request $request, ChecklistSession $session)
    {
        $validated = $request->validate([
            'found_laptops' => ['array'],
            'found_laptops.*' => ['integer', 'exists:laptops,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $foundIds = collect($validated['found_laptops'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $session->load('details.laptop');
        $laptopIds = $session->details->pluck('laptop_id')->all();

        $laptops = Laptop::with('owner')
            ->whereIn('id', $laptopIds)
            ->orderBy('code')
            ->get();

        if ($laptops->isEmpty()) {
            return redirect()->route('staff.checklist.history')->withErrors('Checklist tidak memiliki data laptop untuk diperbarui.');
        }

        DB::transaction(function () use ($session, $laptops, $foundIds, $validated) {
            $session->details->each(function (ChecklistDetail $detail) use ($session) {
                if ($detail->status === 'missing' && $detail->laptop) {
                    $detail->laptop->update(['is_missing' => false]);
                }

                $this->removeMissingViolation($detail->laptop, $session);
            });

            $session->details()->delete();

            $missingLaptops = collect();
            $borrowedLaptops = collect();

            $session->update([
                'note' => $validated['note'] ?? $session->note,
                'total_laptops' => $laptops->count(),
                'completed_at' => now(),
            ]);

            $this->processChecklist(
                laptops: $laptops,
                foundIds: $foundIds,
                session: $session,
                missingLaptops: $missingLaptops,
                borrowedLaptops: $borrowedLaptops,
            );
        });

        return redirect()
            ->route('staff.checklist.show', $session)
            ->with('status', 'Checklist berhasil diperbarui.');
    }

    public function destroy(ChecklistSession $session)
    {
        DB::transaction(function () use ($session) {
            $session->load('details.laptop');

            $session->details->each(function (ChecklistDetail $detail) use ($session) {
                if ($detail->status === 'missing' && $detail->laptop) {
                    $detail->laptop->update(['is_missing' => false]);
                }

                $this->removeMissingViolation($detail->laptop, $session);
            });

            $session->details()->delete();
            $session->delete();
        });

        return redirect()->route('staff.checklist.history')->with('status', 'Checklist berhasil dihapus.');
    }

    protected function recordMissingViolation(Laptop $laptop, ChecklistSession $session): void
    {
        $subjectUser = $laptop->owner;
        $linkedTransaction = null;

        if (!$subjectUser) {
            $linkedTransaction = BorrowTransaction::query()
                ->where('laptop_id', $laptop->id)
                ->latest('borrowed_at')
                ->first();

            if ($linkedTransaction && $linkedTransaction->student) {
                $subjectUser = $linkedTransaction->student;
            }
        }

        if (!$subjectUser) {
            return;
        }

        $existing = Violation::query()
            ->where('user_id', $subjectUser->id)
            ->whereNull('borrow_transaction_id')
            ->where('status', 'active')
            ->where('notes', 'like', '%Laptop ' . $laptop->code . '%')
            ->exists();

        if ($existing) {
            return;
        }

        Violation::create([
            'user_id' => $subjectUser->id,
            'borrow_transaction_id' => $linkedTransaction?->id,
            'status' => 'active',
            'points' => 1,
            'notes' => 'Laptop ' . $laptop->code . ' tidak ditemukan pada checklist #' . $session->id,
            'occurred_at' => now(),
        ]);
    }

    protected function removeMissingViolation(?Laptop $laptop, ChecklistSession $session): void
    {
        if (!$laptop) {
            return;
        }

        Violation::query()
            ->where('notes', 'like', 'Laptop ' . $laptop->code . ' tidak ditemukan pada checklist #' . $session->id . '%')
            ->delete();
    }

    protected function processChecklist(
        $laptops,
        $foundIds,
        ChecklistSession $session,
        &$missingLaptops,
        &$borrowedLaptops
    ): void {
        $foundCount = 0;
        $missingCount = 0;
        $borrowedCount = 0;

        /** @var Laptop $laptop */
        foreach ($laptops as $laptop) {
            if ($laptop->status === 'borrowed') {
                $status = 'borrowed';
                $borrowedCount++;
                $borrowedLaptops->push($laptop);
                $laptop->update(['last_checked_at' => now()]);
            } else {
                $isFound = $foundIds->contains($laptop->id);

                if ($isFound) {
                    $status = 'found';
                    $foundCount++;
                    if ($laptop->is_missing) {
                        $laptop->markAvailable();
                    } else {
                        $laptop->update(['last_checked_at' => now()]);
                    }
                } else {
                    $status = 'missing';
                    $missingCount++;
                    $missingLaptops->push($laptop);
                    if (!$laptop->is_missing) {
                        $laptop->markMissing();
                    } else {
                        $laptop->update(['last_checked_at' => now()]);
                    }
                }
            }

            ChecklistDetail::create([
                'checklist_session_id' => $session->id,
                'laptop_id' => $laptop->id,
                'status' => $status,
            ]);

            if ($status === 'missing') {
                $this->recordMissingViolation($laptop, $session);
            }
        }

        $session->update([
            'completed_at' => now(),
            'found_count' => $foundCount,
            'missing_count' => $missingCount,
            'borrowed_count' => $borrowedCount,
        ]);
    }

    protected function extractFilters(Request $request): array
    {
        $classrooms = collect($request->input('classrooms', []))
            ->filter(fn ($value) => filled($value))
            ->values()
            ->all();

        $gender = $request->input('gender');
        if ($gender === '') {
            $gender = null;
        }

        $status = $request->input('status');
        if ($status === '') {
            $status = null;
        }

        return [
            'classrooms' => $classrooms,
            'gender' => $gender,
            'status' => $status,
        ];
    }

    protected function laptopQueryWithFilters(array $filters)
    {
        return Laptop::with('owner')
            ->when($filters['classrooms'], function ($query, $classrooms) {
                $query->whereHas('owner', fn ($owner) => $owner->whereIn('classroom', $classrooms));
            })
            ->when($filters['gender'], function ($query, $gender) {
                $query->whereHas('owner', fn ($owner) => $owner->where('gender', $gender));
            })
            ->when($filters['status'], function ($query, $status) {
                if ($status === 'missing') {
                    $query->where('is_missing', true);
                } else {
                    $query->where('status', $status);
                }
            });
    }
}
