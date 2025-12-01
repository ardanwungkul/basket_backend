<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        DB::beginTransaction();

        // CONFIG: size tunable
        $numUsers = 50;
        $numGuardians = 120;
        $numMembers = 220;
        $numPayments = 200;
        // ages allowed for members
        $ageMin = 7;
        $ageMax = 18;

        // Create Users (admins, coaches, parents)
        $users = [];
        $roles = ['admin', 'coach', 'parent'];
        for ($i = 0; $i < $numUsers; $i++) {
            $id = (string) Str::uuid();
            $role = $roles[array_rand($roles)];
            $email = $faker->unique()->safeEmail;

            DB::table('users')->insert([
                'id' => $id,
                'name' => $faker->name,
                'username' => $faker->unique()->userName,
                'email' => $email,
                'email_verified_at' => $faker->boolean(80) ? Carbon::now()->toDateTimeString() : null,
                'password' => Hash::make('password'),
                'role' => $role,
                'remember_token' => Str::random(10),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $users[] = ['id' => $id, 'role' => $role, 'email' => $email];
        }

        // Create Guardians (parents)
        $guardians = [];
        // collect parent user ids if any
        $parentUserIds = array_map(function ($u) {
            return $u['id'];
        }, array_filter($users, fn($u) => $u['role'] === 'parent'));
        for ($i = 0; $i < $numGuardians; $i++) {
            // ensure at least one parent user exists
            if (count($parentUserIds) === 0) {
                $userId = (string) Str::uuid();
                DB::table('users')->insert([
                    'id' => $userId,
                    'name' => $faker->name,
                    'username' => $faker->unique()->userName,
                    'email' => $faker->unique()->safeEmail,
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('password'),
                    'role' => 'parent',
                    'remember_token' => Str::random(10),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                $parentUserIds[] = $userId;
            }

            $linkUserId = $parentUserIds[array_rand($parentUserIds)];

            $id = (string) Str::uuid();
            $email = $faker->unique()->safeEmail;

            DB::table('guardians')->insert([
                'id' => $id,
                'user_id' => $linkUserId,
                'name' => $faker->name,
                'address' => $faker->address,
                'phone_number' => $faker->phoneNumber,
                'email' => $email,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $guardians[] = ['id' => $id, 'email' => $email];
        }

        // Create Members - ages between $ageMin and $ageMax
        // NOTE: we do NOT add any 'age' column to DB. We store date_of_birth in DB and compute age at runtime in PHP.
        $members = [];
        for ($i = 0; $i < $numMembers; $i++) {
            $id = (string) Str::uuid();
            $parent = $guardians[array_rand($guardians)];
            $gender = $faker->randomElement(['Laki Laki', 'Perempuan']);

            // generate an age between ageMin and ageMax
            $age = $faker->numberBetween($ageMin, $ageMax);
            // date_of_birth approximated by subtracting years + random months/days
            $dob = Carbon::now()->subYears($age)->subMonths($faker->numberBetween(0, 11))->subDays($faker->numberBetween(0, 27));

            $isFormer = $faker->boolean(20);
            $formerClub = $isFormer ? $faker->company : null;
            $formerYear = $isFormer ? $faker->numberBetween(2000, 2024) : null;

            DB::table('members')->insert([
                'id' => $id,
                'name' => $faker->name,
                'gender' => $gender,
                'place_of_birth' => $faker->city,
                'date_of_birth' => $dob->format('Y-m-d'),
                'school' => $faker->company . ' School',
                'school_grade' => (string) $faker->numberBetween(1, 12),
                'disease' => $faker->boolean(8) ? $faker->word : null,
                'is_former_club' => $isFormer,
                'former_club' => $formerClub,
                'former_club_year' => $formerYear,

                'parent_name' => $faker->name,
                'parent_phone_number' => $faker->phoneNumber,
                'parent_email' => $faker->safeEmail,
                'parent_address' => $faker->address,

                'parent_id' => $parent['id'],
                'registration_fee' => 400000.00,
                'monthly_fee' => 350000.00,
                'status' => $faker->randomElement(['active', 'inactive']),
                'join_date' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // store id + parent + date_of_birth in PHP array for seeder logic
            $members[] = [
                'id' => $id,
                'parent_id' => $parent['id'],
                'date_of_birth' => $dob->format('Y-m-d'),
            ];
        }

        // Create Member Files for each member
        foreach ($members as $m) {
            DB::table('member_files')->insert([
                'id' => (string) Str::uuid(),
                'member_id' => $m['id'],
                'photo' => 'photos/' . Str::random(12) . '.jpg',
                'birth_certificate' => 'documents/birth_' . Str::random(8) . '.pdf',
                'family_card' => 'documents/card_' . Str::random(8) . '.pdf',
                'club_release_letter' => $faker->boolean(12) ? 'documents/release_' . Str::random(8) . '.pdf' : null,
                'bpjs' => $faker->boolean(8) ? 'documents/bpjs_' . Str::random(8) . '.pdf' : null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Create Training Schedules - one schedule per age group (U-7 ... U-18)
        // We DO NOT add an 'age' column to the DB; but we keep 'age' info in local $schedules array only.
        $schedules = [];
        for ($age = $ageMin; $age <= $ageMax; $age++) {
            // create multiple sessions per age (e.g., 1-3 sessions), tweak as needed
            $sessionsPerAge = $faker->numberBetween(1, 3);
            for ($s = 0; $s < $sessionsPerAge; $s++) {
                $id = (string) Str::uuid();
                // include U-age in title so frontend can parse if needed
                $title = 'Latihan U-' . $age . ' - Sesi ' . ($s + 1);
                $date = $faker->dateTimeBetween('-1 months', '+2 months')->format('Y-m-d');

                DB::table('training_schedule')->insert([
                    'id' => $id,
                    'title' => $title,
                    'date' => $date,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // keep age meta in PHP array so we can attach only matching-aged members
                $schedules[] = [
                    'id' => $id,
                    'age' => $age, // only in PHP memory, NOT in DB
                    'title' => $title,
                    'date' => $date,
                ];
            }
        }

        // Pivot member_training_schedule: attach each member to 1-4 schedules that match their age (strict equality)
        foreach ($members as $m) {
            // compute member age from date_of_birth at runtime (no DB age field)
            $memberDob = Carbon::parse($m['date_of_birth']);
            $memberAge = Carbon::now()->diffInYears($memberDob);

            // get schedules matching member age
            $matching = array_values(array_filter($schedules, fn($sch) => $sch['age'] === $memberAge));
            if (count($matching) === 0) {
                continue; // no schedule for that age
            }

            $attachCount = $faker->numberBetween(1, 4);
            // if attachCount > available, cap it
            $attachCount = min($attachCount, count($matching));
            $picked = $faker->randomElements($matching, $attachCount);
            foreach ($picked as $p) {
                DB::table('pivot_member_training_schedule')->insert([
                    'member_id' => $m['id'],
                    'training_schedule_id' => $p['id'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        // Create Member Bills: for each member create 1 registration bill and 6 monthly bills
        $bills = [];
        foreach ($members as $m) {
            // registration
            $billId = (string) Str::uuid();
            DB::table('member_bills')->insert([
                'id' => $billId,
                'member_id' => $m['id'],
                'bill_type' => 'registration',
                'period_from' => null,
                'period_to' => null,
                'amount' => 400000.00,
                'due_date' => Carbon::now()->addDays($faker->numberBetween(0, 30))->format('Y-m-d'),
                'status' => $faker->boolean(70) ? 'PAID' : 'UNPAID',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            $bills[] = ['id' => $billId, 'member_id' => $m['id']];

            // 6 monthly bills
            for ($mth = 0; $mth < 6; $mth++) {
                $billId = (string) Str::uuid();
                $periodFrom = Carbon::now()->subMonths($mth + 1)->startOfMonth();
                $periodTo = (clone $periodFrom)->endOfMonth();
                DB::table('member_bills')->insert([
                    'id' => $billId,
                    'member_id' => $m['id'],
                    'bill_type' => 'monthly',
                    'period_from' => $periodFrom->format('Y-m-d'),
                    'period_to' => $periodTo->format('Y-m-d'),
                    'amount' => 350000.00,
                    'due_date' => $periodTo->addDays(7)->format('Y-m-d'),
                    'status' => $faker->boolean(60) ? 'PAID' : 'UNPAID',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                $bills[] = ['id' => $billId, 'member_id' => $m['id']];
            }
        }

        // Create Payments - randomly create payments for some guardians
        $payments = [];
        for ($i = 0; $i < $numPayments; $i++) {
            $id = (string) Str::uuid();
            $parent = $guardians[array_rand($guardians)];
            $status = $faker->randomElement(['UNPAID', 'PENDING', 'SUCCESS', 'FAILED']);
            $total = $faker->randomFloat(2, 100000, 2000000);

            DB::table('payments')->insert([
                'id' => $id,
                'parent_id' => $parent['id'],
                'payment_date' => $faker->boolean(80) ? $faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d') : null,
                'total_amount' => $total,
                'payment_method' => $faker->randomElement(['transfer', 'qris']),
                'reference_code' => strtoupper(Str::random(10)),
                'status' => $status,
                'file' => $faker->boolean(30) ? 'payments/receipt_' . Str::random(8) . '.jpg' : null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $payments[] = ['id' => $id, 'parent_id' => $parent['id']];
        }

        // Create Payment Details - link payments to bills (only if bills exist)
        foreach ($payments as $p) {
            if (count($bills) === 0) {
                break;
            }
            $pickCount = $faker->numberBetween(1, min(3, count($bills)));
            $candidateBills = $faker->randomElements($bills, $pickCount);
            foreach ($candidateBills as $b) {
                $id = (string) Str::uuid();
                $amount = $faker->randomFloat(2, 50000, 400000);
                DB::table('payment_details')->insert([
                    'id' => $id,
                    'payment_id' => $p['id'],
                    'bill_id' => $b['id'],
                    'amount' => $amount,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        // Create Attendances - generate records for members in schedules by random coaches
        $coachUsers = array_filter($users, function ($u) {
            return $u['role'] === 'coach';
        });
        if (count($coachUsers) === 0) {
            // create a coach user
            $coachId = (string) Str::uuid();
            DB::table('users')->insert([
                'id' => $coachId,
                'name' => $faker->name,
                'username' => $faker->unique()->userName,
                'email' => $faker->unique()->safeEmail,
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'role' => 'coach',
                'remember_token' => Str::random(10),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            $coachUsers = [['id' => $coachId]];
        }
        $coachUsers = array_values($coachUsers);

        // For each schedule, create attendance rows for random members of matching age
        foreach ($schedules as $s) {
            // pick members with matching age (compute age from date_of_birth)
            $matchingMembers = array_values(array_filter($members, function ($m) use ($s) {
                $memberAge = Carbon::now()->diffInYears(Carbon::parse($m['date_of_birth']));

                return $memberAge === $s['age'];
            }));

            if (count($matchingMembers) === 0) {
                continue;
            }

            $attendanceCount = min(count($matchingMembers), $faker->numberBetween(5, 25));
            $pickedMembers = $faker->randomElements($matchingMembers, $attendanceCount);
            foreach ($pickedMembers as $pm) {
                $id = (string) Str::uuid();
                $coach = $coachUsers[array_rand($coachUsers)];

                // decide status first, then set reason accordingly:
                $status = $faker->randomElement(['present', 'absent']);

                // if absent, MUST provide a reason (non-null); if present, reason is null
                $reason = $status === 'absent' ? $faker->sentence() : null;

                DB::table('attendances')->insert([
                    'id' => $id,
                    'member_id' => $pm['id'],
                    'coach_id' => $coach['id'],
                    'training_schedule_id' => $s['id'],
                    'date' => Carbon::now()->subDays($faker->numberBetween(0, 60))->format('Y-m-d'),
                    'time' => $faker->time('H:i:s'),
                    'method' => $faker->randomElement(['qr', 'manual']),
                    'status' => $status,
                    'reason' => $reason,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        DB::commit();

        $this->command->info('Database seeded: users(' . count($users) . '), guardians(' . count($guardians) . '), members(' . count($members) . '), bills(' . count($bills) . '), payments(' . count($payments) . ')');
    }
}
