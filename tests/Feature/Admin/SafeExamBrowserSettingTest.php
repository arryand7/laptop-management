<?php

namespace Tests\Feature\Admin;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SafeExamBrowserSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::query()->create([
            'site_name' => 'Test Site',
        ]);
    }

    public function test_admin_can_view_safe_exam_browser_setting_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.settings.safe-exam-browser'));

        $response->assertOk()
            ->assertSee('Safe Exam Browser');
    }

    public function test_admin_can_update_safe_exam_browser_settings(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        $file = UploadedFile::fake()->create('ujian.seb', 24, 'application/xml');

        $response = $this->actingAs($admin)->put(route('admin.settings.safe-exam-browser.update'), [
            'seb_enabled' => '1',
            'seb_config_link' => 'https://example.com/config.seb',
            'seb_browser_exam_key' => 'KEY-123',
            'seb_exit_key_combination' => 'CTRL+ALT+SHIFT+Q',
            'seb_config_password' => 'secret',
            'seb_additional_notes' => 'Catatan ujian',
            'seb_config_file' => $file,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('status');

        /** @var AppSetting $setting */
        $setting = AppSetting::query()->first();
        $setting->refresh();

        $this->assertTrue($setting->seb_enabled);
        $this->assertSame('https://example.com/config.seb', $setting->seb_config_link);
        $this->assertSame('KEY-123', $setting->seb_browser_exam_key);
        $this->assertSame('CTRL+ALT+SHIFT+Q', $setting->seb_exit_key_combination);
        $this->assertSame('secret', $setting->seb_config_password);
        $this->assertSame('Catatan ujian', $setting->seb_additional_notes);
        $this->assertNotNull($setting->seb_client_config_path);
        Storage::disk('public')->assertExists($setting->seb_client_config_path);
    }
}
