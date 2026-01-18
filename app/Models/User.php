<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'student_number',
        'card_code',
        'role',
        'gender',
        'classroom',
        'phone',
        'avatar_path',
        'qr_code',
        'violations_count',
        'sanction_ends_at',
        'is_active',
        'sso_sub',
        'sso_synced_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'sanction_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'sso_synced_at' => 'datetime',
        ];
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            return asset('storage/' . $this->avatar_path);
        }

        return asset('images/default-avatar.png');
    }

    public function borrowTransactionsAsStudent()
    {
        return $this->hasMany(BorrowTransaction::class, 'student_id');
    }

    public function latestBorrowTransaction()
    {
        return $this->hasOne(BorrowTransaction::class, 'student_id')->latestOfMany('borrowed_at');
    }

    public function borrowTransactionsHandled()
    {
        return $this->hasMany(BorrowTransaction::class, 'staff_id');
    }

    public function returnTransactionsHandled()
    {
        return $this->hasMany(BorrowTransaction::class, 'return_staff_id');
    }

    public function violations()
    {
        return $this->hasMany(Violation::class);
    }

    public function sanctions()
    {
        return $this->hasMany(Sanction::class);
    }

    public function ownedLaptops()
    {
        return $this->hasMany(Laptop::class, 'owner_id');
    }

    public function laptopUpdateRequests()
    {
        return $this->hasMany(LaptopUpdateRequest::class, 'student_id');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class)->withTimestamps();
    }

    public function hasModule(string $moduleKey): bool
    {
        if (!$this->relationLoaded('modules')) {
            $this->load('modules');
        }

        if ($this->modules->isEmpty()) {
            $defaultKeys = collect(config('modules.list', []))
                ->filter(fn (array $definition) => in_array($this->role, $definition['default_roles'] ?? [], true))
                ->pluck('key');

            return $defaultKeys->contains($moduleKey);
        }

        return $this->modules->contains(fn ($module) => $module->key === $moduleKey);
    }

    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeStaff($query)
    {
        return $query->where('role', 'staff');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function hasActiveSanction(): bool
    {
        if (!$this->sanction_ends_at) {
            return false;
        }

        return now()->lessThan($this->sanction_ends_at);
    }
}
