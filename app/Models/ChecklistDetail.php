<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_session_id',
        'laptop_id',
        'status',
        'note',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChecklistSession::class, 'checklist_session_id');
    }

    public function laptop(): BelongsTo
    {
        return $this->belongsTo(Laptop::class);
    }
}
