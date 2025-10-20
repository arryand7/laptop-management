<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'student_id',
        'laptop_id',
        'staff_id',
        'return_staff_id',
        'usage_purpose',
        'status',
        'was_late',
        'borrowed_at',
        'due_at',
        'returned_at',
        'late_minutes',
        'staff_notes',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
            'was_late' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function laptop(): BelongsTo
    {
        return $this->belongsTo(Laptop::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function returnStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'return_staff_id');
    }

    public function violation()
    {
        return $this->hasOne(Violation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'borrowed');
    }

    public function isLate(): bool
    {
        if ($this->was_late) {
            return true;
        }

        if ($this->status !== 'borrowed') {
            return false;
        }

        return $this->due_at ? now()->greaterThan($this->due_at) : false;
    }

    public function markAsReturned(?User $staff = null): void
    {
        $now = now();
        $isLate = $this->due_at ? $now->greaterThan($this->due_at) : false;
        $lateMinutes = $isLate && $this->due_at ? $this->due_at->diffInMinutes($now) : null;

        $this->update([
            'status' => 'returned',
            'was_late' => $isLate,
            'returned_at' => $now,
            'return_staff_id' => $staff?->getKey(),
            'late_minutes' => $lateMinutes,
        ]);
    }
}
