<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAiSettingRequest;
use App\Http\Requests\Admin\UpdateApplicationSettingRequest;
use App\Http\Requests\Admin\UpdateLendingSettingRequest;
use App\Http\Requests\Admin\UpdateMailSettingRequest;
use App\Models\AppSetting;
use App\Support\AppSettingManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AppSettingController extends Controller
{
    public function application(): View
    {
        return view('admin.settings.application', [
            'setting' => $this->setting(),
        ]);
    }

    public function updateApplication(UpdateApplicationSettingRequest $request): RedirectResponse
    {
        $setting = $this->setting();

        DB::transaction(function () use ($setting, $request): void {
            $setting->fill($request->validated());

            if ($request->hasFile('logo')) {
                if ($setting->logo_path && Storage::disk('public')->exists($setting->logo_path)) {
                    Storage::disk('public')->delete($setting->logo_path);
                }
                $setting->logo_path = $request->file('logo')->store('logos', 'public');
            }

            $setting->save();
        });

        AppSettingManager::refreshCache();

        return back()->with('status', 'Identitas aplikasi berhasil diperbarui.');
    }

    public function lending(): View
    {
        return view('admin.settings.lending', [
            'setting' => $this->setting(),
        ]);
    }

    public function updateLending(UpdateLendingSettingRequest $request): RedirectResponse
    {
        $setting = $this->setting();

        $mode = $request->input('lending_due_mode');

        $dueTimeInput = $request->input('lending_due_time') ?? $request->input('lending_due_time_daily');

        $setting->lending_due_mode = $mode;
        $setting->lending_due_days = $mode === 'relative' ? $request->integer('lending_due_days') : null;
        $setting->lending_due_time = in_array($mode, ['relative', 'daily'], true) ? $dueTimeInput : null;
        $setting->lending_due_date = $mode === 'fixed' ? $request->input('lending_due_date') : null;
        $setting->save();

        AppSettingManager::refreshCache();

        return back()->with('status', 'Peraturan batas pengembalian berhasil diperbarui.');
    }

    public function mail(): View
    {
        return view('admin.settings.mail', [
            'setting' => $this->setting(),
        ]);
    }

    public function updateMail(UpdateMailSettingRequest $request): RedirectResponse
    {
        $setting = $this->setting();
        $validated = $request->validated();

        $setting->smtp_host = $validated['smtp_host'] ?? $setting->smtp_host;
        $setting->smtp_port = $validated['smtp_port'] ?? $setting->smtp_port;
        $setting->smtp_encryption = $validated['smtp_encryption'] === 'none' ? null : ($validated['smtp_encryption'] ?? $setting->smtp_encryption);
        $setting->smtp_username = $validated['smtp_username'] ?? $setting->smtp_username;

        if (!empty($validated['smtp_password'])) {
            $setting->smtp_password = $validated['smtp_password'];
        }

        $setting->save();
        AppSettingManager::refreshCache();

        return back()->with('status', 'Pengaturan email SMTP diperbarui.');
    }

    public function ai(): View
    {
        return view('admin.settings.ai', [
            'setting' => $this->setting(),
        ]);
    }

    public function updateAi(UpdateAiSettingRequest $request): RedirectResponse
    {
        $setting = $this->setting();
        $validated = $request->validated();

        $setting->ai_default_provider = $validated['ai_default_provider'] ?? $setting->ai_default_provider;
        $setting->openai_model = $validated['openai_model'] ?? $setting->openai_model;
        $setting->gemini_model = $validated['gemini_model'] ?? $setting->gemini_model;
        $setting->huggingface_model = $validated['huggingface_model'] ?? $setting->huggingface_model;

        if (!empty($validated['openai_api_key'])) {
            $setting->openai_api_key = $validated['openai_api_key'];
        }
        if (!empty($validated['gemini_api_key'])) {
            $setting->gemini_api_key = $validated['gemini_api_key'];
        }
        if (!empty($validated['huggingface_api_key'])) {
            $setting->huggingface_api_key = $validated['huggingface_api_key'];
        }

        $setting->save();
        AppSettingManager::refreshCache();

        return back()->with('status', 'Integrasi AI berhasil diperbarui.');
    }

    protected function setting(): AppSetting
    {
        /** @var AppSetting|null $setting */
        $setting = AppSetting::query()->first();

        if (!$setting) {
            $setting = AppSetting::create([
                'site_name' => config('app.name', 'Laptop Management'),
            ]);
        }

        return $setting;
    }
}
