<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public static function syncFromConfig(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }

        $configured = collect(config('modules.list', []))
            ->keyBy(fn ($item) => $item['key']);

        if ($configured->isEmpty()) {
            return;
        }

        $modules = static::query()->get()->keyBy('key');

        $configured->each(function (array $definition) use ($modules) {
            $existingModule = $modules->get($definition['key']);
            $wasCreated = false;

            $attributes = [
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
            ];

            if ($existingModule) {
                $existingModule->update($attributes);
                $module = $existingModule;
            } else {
                $module = static::create(array_merge(['key' => $definition['key']], $attributes));
                $wasCreated = true;
            }

            $defaultRoles = $definition['default_roles'] ?? [];
            if ($wasCreated && !empty($defaultRoles)) {
                $users = User::whereIn('role', $defaultRoles)->get();
                foreach ($users as $user) {
                    $user->modules()->syncWithoutDetaching([$module->id]);
                }
            }
        });
    }
}
