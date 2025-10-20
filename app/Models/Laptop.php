<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laptop extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'brand',
        'model',
        'serial_number',
        'specifications',
        'status',
        'owner_id',
        'qr_code',
        'notes',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'last_checked_at' => 'datetime',
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
        $this->update(['status' => 'borrowed']);
    }

    public function markAvailable(): void
    {
        $this->update(['status' => 'available']);
    }
}
