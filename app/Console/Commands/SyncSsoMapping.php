<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SyncSsoMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sso:sync {--file=storage/sso_mapping.csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync sso_sub on local users using exported mapping from Sabira Connect';

    /**
     * Execute the console command.
     */
    public function handle()
    {
            $path = $this->option('file');
            if (str_starts_with($path, 'storage/')) {
                $relative = str_replace('storage/', '', $path);
                if (! Storage::exists($relative)) {
                    $this->error("File not found at {$path}");

                    return self::FAILURE;
                }
                $content = Storage::get($relative);
            } else {
                if (! file_exists($path)) {
                    $this->error("File not found at {$path}");

                    return self::FAILURE;
                }
                $content = file_get_contents($path);
            }

            $rows = array_filter(array_map('trim', explode("\n", (string) $content)));
            if (count($rows) < 2) {
                $this->error('No data rows found.');

                return self::FAILURE;
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

            $this->info("Processed: {$processed}, Updated: {$updated}");

            return self::SUCCESS;
    }
}
