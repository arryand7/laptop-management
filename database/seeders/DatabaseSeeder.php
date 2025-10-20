<?php

namespace Database\Seeders;

use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\Sanction;
use App\Models\User;
use App\Models\Violation;
use App\Support\CodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::factory()
                ->admin()
                ->create([
                    'name' => 'Administrator',
                    'email' => 'admin@school.test',
                    'student_number' => null,
                    'qr_code' => 'ADMIN-' . strtoupper(Str::random(6)),
                ]);

            $staff = User::factory()
                ->staff()
                ->create([
                    'name' => 'Petugas Lab',
                    'email' => 'staff@school.test',
                    'student_number' => null,
                    'qr_code' => 'STAFF-' . strtoupper(Str::random(6)),
                ]);

            $studentProfiles = collect([
                [
                    'name' => 'Aisyah Rahma',
                    'email' => 'aisyah.rahma@student.test',
                    'student_number' => 'STD2024001',
                    'card_code' => 'TFExaERBRFdnNmVDQ0htVmZGUU0vNTF6V0k4MDdyb2xBbVRIdGU3Nk52cTZzakhzdlpKdVY0OD0=',
                    'classroom' => 'XII IPA 1',
                    'phone' => '0812 3456 7801',
                ],
                [
                    'name' => 'Bima Saputra',
                    'email' => 'bima.saputra@student.test',
                    'student_number' => 'STD2024002',
                    'card_code' => Str::random(64),
                    'classroom' => 'XI RPL 2',
                    'phone' => '0812 3456 7802',
                ],
                [
                    'name' => 'Clara Wijaya',
                    'email' => 'clara.wijaya@student.test',
                    'student_number' => 'STD2024003',
                    'card_code' => Str::random(64),
                    'classroom' => 'XI IPA 3',
                    'phone' => '0812 3456 7803',
                ],
                [
                    'name' => 'Dimas Pratama',
                    'email' => 'dimas.pratama@student.test',
                    'student_number' => 'STD2024004',
                    'card_code' => Str::random(64),
                    'classroom' => 'XII RPL 1',
                    'phone' => '0812 3456 7804',
                ],
                [
                    'name' => 'Intan Lestari',
                    'email' => 'intan.lestari@student.test',
                    'student_number' => 'STD2024005',
                    'card_code' => Str::random(64),
                    'classroom' => 'X IPA 2',
                    'phone' => '0812 3456 7805',
                ],
            ]);

            $students = $studentProfiles->map(function (array $profile) {
                return User::factory()
                    ->student()
                    ->create([
                        'name' => $profile['name'],
                        'email' => $profile['email'],
                        'student_number' => $profile['student_number'],
                        'card_code' => $profile['card_code'],
                        'classroom' => $profile['classroom'],
                        'phone' => $profile['phone'],
                        'qr_code' => $profile['card_code'],
                    ]);
            })->keyBy('student_number');

            $laptopProfiles = collect([
                [
                    'name' => 'Lenovo ThinkBook 14 Gen 3',
                    'brand' => 'Lenovo',
                    'model' => 'ThinkBook 14',
                    'serial_number' => 'LNV-THB14-2024-01',
                    'owner' => 'STD2024001',
                    'status' => 'available',
                    'specs' => [
                        'cpu' => 'Intel Core i5-1135G7',
                        'ram' => '16GB',
                        'storage' => '512GB SSD',
                        'os' => 'Windows 11 Pro',
                    ],
                ],
                [
                    'name' => 'Acer Swift 3X',
                    'brand' => 'Acer',
                    'model' => 'Swift 3X',
                    'serial_number' => 'ACR-SW3X-2024-02',
                    'owner' => 'STD2024002',
                    'status' => 'borrowed',
                    'specs' => [
                        'cpu' => 'Intel Core i7-1165G7',
                        'ram' => '16GB',
                        'storage' => '1TB SSD',
                        'os' => 'Windows 11 Home',
                    ],
                ],
                [
                    'name' => 'HP ProBook 440 G9',
                    'brand' => 'HP',
                    'model' => 'ProBook 440',
                    'serial_number' => 'HP-PB440-2024-03',
                    'owner' => 'STD2024003',
                    'status' => 'maintenance',
                    'specs' => [
                        'cpu' => 'Intel Core i5-1240P',
                        'ram' => '8GB',
                        'storage' => '512GB SSD',
                        'os' => 'Windows 11 Pro',
                    ],
                ],
                [
                    'name' => 'Asus TUF Gaming A15',
                    'brand' => 'Asus',
                    'model' => 'TUF A15',
                    'serial_number' => 'ASUS-TUF15-2024-04',
                    'owner' => 'STD2024004',
                    'status' => 'borrowed',
                    'specs' => [
                        'cpu' => 'AMD Ryzen 7 7735HS',
                        'ram' => '16GB',
                        'storage' => '1TB SSD',
                        'os' => 'Windows 11 Home',
                    ],
                ],
                [
                    'name' => 'Dell Inspiron 14',
                    'brand' => 'Dell',
                    'model' => 'Inspiron 14 5430',
                    'serial_number' => 'DELL-INSP14-2024-05',
                    'owner' => 'STD2024005',
                    'status' => 'available',
                    'specs' => [
                        'cpu' => 'Intel Core i5-1335U',
                        'ram' => '8GB',
                        'storage' => '512GB SSD',
                        'os' => 'Windows 11 Home',
                    ],
                ],
            ]);

            $laptops = $laptopProfiles->map(function (array $profile) use ($students) {
                $ownerId = optional($students->get($profile['owner']))->id;

                return Laptop::create([
                    'code' => CodeGenerator::laptopCode(),
                    'name' => $profile['name'],
                    'brand' => $profile['brand'],
                    'model' => $profile['model'],
                    'serial_number' => $profile['serial_number'],
                    'status' => $profile['status'],
                    'owner_id' => $ownerId,
                    'specifications' => $profile['specs'],
                    'qr_code' => CodeGenerator::laptopQr(),
                    'last_checked_at' => now()->subDays(rand(1, 10)),
                ]);
            })->keyBy('serial_number');

            // Active borrowing - Bima borrowing his own laptop for competition
            BorrowTransaction::create([
                'transaction_code' => CodeGenerator::transactionCode(),
                'student_id' => $students['STD2024002']->id,
                'laptop_id' => $laptops['ACR-SW3X-2024-02']->id,
                'staff_id' => $staff->id,
                'usage_purpose' => 'Pelatihan LKS RPL',
                'status' => 'borrowed',
                'was_late' => false,
                'borrowed_at' => now()->subHours(2),
                'due_at' => now()->addHours(4),
                'staff_notes' => 'Dipakai di laboratorium RPL',
            ]);

            // Active borrowing - Intan using Dimas's gaming laptop for multimedia class
            BorrowTransaction::create([
                'transaction_code' => CodeGenerator::transactionCode(),
                'student_id' => $students['STD2024005']->id,
                'laptop_id' => $laptops['ASUS-TUF15-2024-04']->id,
                'staff_id' => $staff->id,
                'usage_purpose' => 'Produksi video ekstrakurikuler',
                'status' => 'borrowed',
                'was_late' => false,
                'borrowed_at' => now()->subHours(3),
                'due_at' => now()->addHours(3),
                'staff_notes' => 'Memerlukan GPU untuk rendering',
            ]);

            // Returned late borrowing producing violation and sanction
            $lateBorrow = BorrowTransaction::create([
                'transaction_code' => CodeGenerator::transactionCode(),
                'student_id' => $students['STD2024004']->id,
                'laptop_id' => $laptops['LNV-THB14-2024-01']->id,
                'staff_id' => $staff->id,
                'return_staff_id' => $staff->id,
                'usage_purpose' => 'Simulasi UTBK',
                'status' => 'returned',
                'was_late' => true,
                'borrowed_at' => now()->subDays(2),
                'due_at' => now()->subDay(),
                'returned_at' => now()->subHours(6),
                'late_minutes' => 360,
                'staff_notes' => 'Telat 6 jam karena jadwal tambahan',
            ]);

            $students['STD2024004']->update([
                'violations_count' => 1,
                'sanction_ends_at' => now()->addDays(3),
            ]);

            Violation::create([
                'user_id' => $students['STD2024004']->id,
                'borrow_transaction_id' => $lateBorrow->id,
                'status' => 'active',
                'points' => 1,
                'notes' => 'Pengembalian terlambat 6 jam',
                'occurred_at' => now()->subHours(6),
            ]);

            Sanction::create([
                'user_id' => $students['STD2024004']->id,
                'issued_by' => $admin->id,
                'status' => 'active',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(3),
                'reason' => 'Melebihi batas keterlambatan peminjaman laptop',
            ]);
        });
    }
}
