<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->whereIn('role', ['admin', 'staff'])
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        debug_event('Admin:Users', 'Menampilkan daftar user', [
            'total' => $users->count(),
            'search' => $search,
        ]);

        return view('admin.users.index', compact('users', 'search'));
    }

    public function create()
    {
        $modules = Module::orderBy('name')->get();

        return view('admin.users.create', [
            'modules' => $modules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::exists('modules', 'key')],
        ]);

        $usingDefaultPassword = empty($validated['password']);
        $password = $validated['password'] ?? 'password';

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($password),
            'is_active' => $request->boolean('is_active', true),
            'qr_code' => null,
            'avatar_path' => $request->file('avatar')?->store('avatars', 'public'),
        ]);

        $moduleKeys = collect($request->input('modules', []))
            ->filter()
            ->values();

        if ($moduleKeys->isEmpty()) {
            $moduleKeys = collect(config('modules.list', []))
                ->filter(fn (array $definition) => in_array($user->role, $definition['default_roles'] ?? [], true))
                ->pluck('key');
        }

        if ($moduleKeys->isNotEmpty()) {
            $moduleIds = Module::whereIn('key', $moduleKeys)->pluck('id');
            $user->modules()->sync($moduleIds);
        }

        debug_event('Admin:Users', 'User baru dibuat', [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        $message = $usingDefaultPassword
            ? 'User baru berhasil ditambahkan dengan kata sandi default: ' . $password
            : 'User baru berhasil ditambahkan.';

        return redirect()
            ->route('admin.users.index')
            ->with('status', $message);
    }

    public function edit(User $user)
    {
        abort_unless(in_array($user->role, ['admin', 'staff'], true), 404);

        $user->load('modules');
        $modules = Module::orderBy('name')->get();

        return view('admin.users.edit', [
            'user' => $user,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(in_array($user->role, ['admin', 'staff'], true), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::exists('modules', 'key')],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        $moduleKeys = collect($request->input('modules', []))
            ->filter()
            ->values();

        if ($moduleKeys->isEmpty()) {
            $moduleKeys = collect(config('modules.list', []))
                ->filter(fn (array $definition) => in_array($user->role, $definition['default_roles'] ?? [], true))
                ->pluck('key');
        }

        $moduleIds = Module::whereIn('key', $moduleKeys)->pluck('id');
        $user->modules()->sync($moduleIds);

        debug_event('Admin:Users', 'User diperbarui', [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Data user berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless(in_array($user->role, ['admin', 'staff'], true), 404);

        if ($request->user()->id === $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors('Tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        debug_event('Admin:Users', 'User dihapus', ['user_id' => $user->id]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User berhasil dihapus.');
    }
}
