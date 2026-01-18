<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SsoSyncController extends Controller
{
    public function index()
    {
        return view('admin.settings.sso-sync');
    }

    public function sync(Request $request)
    {
        $request->validate([
            'mapping' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $request->file('mapping')->store('sso-sync');
        $content = Storage::get($path);

        $rows = array_filter(array_map('trim', explode("\n", (string) $content)));
        if (count($rows) < 2) {
            return back()->withErrors(['mapping' => 'File kosong atau format salah.']);
        }

        $headers = str_getcsv(array_shift($rows));
        $processed = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $data = array_combine($headers, str_getcsv($row));
            if (! $data) {
                continue;
            }

            $sub = $data['sub'] ?? null;
            $email = $data['email'] ?? null;
            $nis = $data['nis'] ?? null;
            $type = $data['type'] ?? null;

            /** @var User|null $user */
            $user = null;

            if ($type === 'student' && $nis) {
                $user = User::where('student_number', $nis)->first();
            }

            if (! $user && $email) {
                $user = User::where('email', $email)->first();
            }

            if (! $user || ! $sub) {
                continue;
            }

            if ($user->sso_sub !== $sub) {
                $user->update([
                    'sso_sub' => $sub,
                    'sso_synced_at' => Carbon::now(),
                ]);
                $updated++;
            }

            $processed++;
        }

        return back()->with('status', "Sync selesai. Diproses: {$processed}, diperbarui: {$updated}");
    }
}
