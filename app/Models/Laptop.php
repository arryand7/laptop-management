<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laptop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'model',
        'serial_number',
        'specifications',
        'status',
        'is_missing',
        'owner_id',
        'qr_code',
        'notes',
        'last_checked_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (Laptop $laptop) {
            if (!empty($laptop->serial_number) && empty($laptop->qr_code)) {
                $laptop->qr_code = $laptop->serial_number;
            }
        });
    }

    public function getCodeAttribute(): ?string
    {
        return $this->serial_number;
    }

    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['serial_number'] = $value;
    }

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'last_checked_at' => 'datetime',
            'is_missing' => 'boolean',
        ];
    }

    public function borrowTransactions()
    {
        return $this->hasMany(BorrowTransaction::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function updateRequests()
    {
        return $this->hasMany(LaptopUpdateRequest::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function markBorrowed(): void
    {
        $this->update([
            'status' => 'borrowed',
            'is_missing' => false,
            'last_checked_at' => now(),
        ]);
    }

    public function markAvailable(): void
    {
        $this->update([
            'status' => 'available',
            'is_missing' => false,
            'last_checked_at' => now(),
        ]);
    }

    public function markMissing(): void
    {
        $this->update([
            'is_missing' => true,
            'last_checked_at' => now(),
        ]);
    }
}
