<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaptopUpdateRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'laptop_id',
        'student_id',
        'original_data',
        'proposed_data',
        'status',
        'admin_id',
        'admin_notes',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'original_data' => 'array',
            'proposed_data' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function laptop(): BelongsTo
    {
        return $this->belongsTo(Laptop::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
