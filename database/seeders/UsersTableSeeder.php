<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer ou créer le rôle customer
        $customerRole = Role::firstOrCreate(
            ["name" => "customer"],
            [
                "display_name" => "Customer",
                "description" => "Regular customer",
            ],
        );

        $this->command->info("Creating 10000 users...");
        $bar = $this->command->getOutput()->createProgressBar(4000);
        $bar->start();

        $statuses = [
            "active",
            "active",
            "active",
            "inactive",
            "pending",
            "banned",
        ];
        $genders = ["male", "female", "other", null];
        $password = Hash::make("password");

        // Chunker par 500 pour éviter les memory issues
        $chunks = array_chunk(range(1, 10000), 500);

        foreach ($chunks as $chunk) {
            $users = [];

            foreach ($chunk as $i) {
                $firstName = fake()->firstName();
                $lastName = fake()->lastName();

                $users[] = [
                    "first_name" => $firstName,
                    "last_name" => $lastName,
                    "name" => trim("{$firstName} {$lastName}"),
                    "email" => fake()->unique()->safeEmail(),
                    "password" => $password,
                    "phone" => fake()->boolean(70)
                        ? fake()->unique()->numerify("0#########")
                        : null,
                    "gender" => fake()->randomElement([
                        "male",
                        "female",
                        "other",
                        null,
                    ]),
                    "date_of_birth" => fake()->boolean(80)
                        ? fake()
                            ->dateTimeBetween("-60 years", "-18 years")
                            ->format("Y-m-d")
                        : null,
                    "status" => fake()->randomElement($statuses),
                    "avatar" => null,
                    "locale" => fake()->randomElement(["fr", "en", "ar"]), // plus de null
                    "currency" => fake()->randomElement(["USD", "EUR", "MAD"]), // plus de null
                    "last_login_at" => fake()->boolean(60)
                        ? fake()->dateTimeBetween("-1 year", "now")
                        : null,
                    "last_login_ip" => fake()->boolean(60)
                        ? fake()->ipv4()
                        : null,
                    "email_verified_at" => fake()->boolean(85) ? now() : null,
                    "created_at" => fake()->dateTimeBetween("-2 years", "now"),
                    "updated_at" => now(),
                ];
            }

            // Insert en masse
            User::insert($users);

            // Récupérer les IDs insérés pour assigner les rôles
            $insertedUsers = User::whereIn(
                "email",
                array_column($users, "email"),
            )
                ->whereDoesntHave("userRoles")
                ->get();

            $userRoles = [];
            $now = now();

            foreach ($insertedUsers as $user) {
                $userRoles[] = [
                    "user_id" => $user->id,
                    "role_id" => $customerRole->id,
                    "assigned_by" => null,
                    "assigned_at" => $now,
                    "expires_at" => null,
                    "revoked_at" => null,
                    "revoked_by" => null,
                    "status" => "active",
                    "is_primary" => true,
                    "notes" => null,
                    "metadata" => null,
                    "created_at" => $now,
                    "updated_at" => $now,
                ];
            }

            // Insert rôles en masse
            \App\Models\UserRole::insert($userRoles);

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ 10000 users created with customer role.");
    }
}
