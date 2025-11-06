<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'started_at',
        'completed_at',
        'total_laptops',
        'found_count',
        'missing_count',
        'borrowed_count',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ChecklistDetail::class);
    }
}
