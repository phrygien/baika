<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuppliersTableSeeder extends Seeder
{
    public function run(): void
    {
        $supplierRole = Role::firstOrCreate(
            ["name" => "supplier"],
            ["display_name" => "Supplier", "description" => "Supplier role"],
        );

        $countryIds = Country::pluck("id")->toArray();

        if (empty($countryIds)) {
            $this->command->warn(
                "No countries found. Please seed countries first.",
            );
            return;
        }

        $total = 20000;
        $password = Hash::make("password");
        $now = now();

        $statuses = [
            "approved",
            "approved",
            "approved",
            "pending",
            "rejected",
            "suspended",
        ];
        $businessTypes = ["individual", "company", "freelance", "agency", null];
        $commissionRates = [5, 8, 10, 12, 15, 20];

        // ── Pré-générer les emails uniques ─────────────────────────────────
        $this->command->info("Generating {$total} unique emails...");
        $existingEmails = User::pluck("email")->toArray();
        $emailPool = [];

        while (count($emailPool) < $total) {
            $email = fake()->safeEmail();
            if (
                !in_array($email, $emailPool) &&
                !in_array($email, $existingEmails)
            ) {
                $emailPool[] = $email;
            }
        }

        // ── Pré-générer les shop names uniques ─────────────────────────────
        $this->command->info("Generating {$total} unique shop names...");
        $existingShopNames = Supplier::pluck("shop_name")->toArray();
        $shopNamePool = $existingShopNames;
        $uniqueShopNames = [];

        while (count($uniqueShopNames) < $total) {
            $candidate =
                fake()->company() .
                " " .
                fake()->randomElement([
                    "Ltd",
                    "Inc",
                    "LLC",
                    "Group",
                    "Co",
                    "SAS",
                    "SARL",
                ]) .
                " " .
                Str::random(4);
            if (
                !in_array($candidate, $uniqueShopNames) &&
                !in_array($candidate, $shopNamePool)
            ) {
                $uniqueShopNames[] = $candidate;
                $shopNamePool[] = $candidate;
            }
        }

        $this->command->info("Creating {$total} suppliers with users...");
        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        $existingPhones = User::whereNotNull("phone")
            ->pluck("phone")
            ->toArray();
        $phonePool = $existingPhones;
        $existingSlugs = Supplier::pluck("slug")->toArray();
        $slugPool = $existingSlugs;

        $chunks = array_chunk(range(0, $total - 1), 200);
        $globalIndex = 0;

        foreach ($chunks as $chunk) {
            // ── 1. Créer les users ─────────────────────────────────────────
            $usersData = [];
            $chunkEmails = [];

            foreach ($chunk as $i) {
                $firstName = fake()->firstName();
                $lastName = fake()->lastName();
                $email = $emailPool[$i];
                $chunkEmails[] = $email;

                $phone = null;
                if (fake()->boolean(70)) {
                    $candidate = fake()->numerify("0#########");
                    if (!in_array($candidate, $phonePool)) {
                        $phone = $candidate;
                        $phonePool[] = $candidate;
                    }
                }

                $usersData[] = [
                    "first_name" => $firstName,
                    "last_name" => $lastName,
                    "name" => trim("{$firstName} {$lastName}"),
                    "email" => $email,
                    "password" => $password,
                    "phone" => $phone,
                    "gender" => fake()->randomElement([
                        "male",
                        "female",
                        "other",
                        null,
                    ]),
                    "status" => "active",
                    "avatar" => null,
                    "locale" => fake()->randomElement(["fr", "en", "ar"]),
                    "currency" => fake()->randomElement(["USD", "EUR", "MAD"]),
                    "email_verified_at" => $now,
                    "created_at" => fake()->dateTimeBetween("-2 years", "now"),
                    "updated_at" => $now,
                ];
            }

            User::insert($usersData);

            // ── 2. Récupérer les IDs créés ─────────────────────────────────
            $createdUsers = User::whereIn("email", $chunkEmails)
                ->whereDoesntHave("userRoles")
                ->get(["id", "email"]);

            // ── 3. Assigner le rôle supplier ───────────────────────────────
            $userRolesData = $createdUsers
                ->map(
                    fn($user) => [
                        "user_id" => $user->id,
                        "role_id" => $supplierRole->id,
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
                    ],
                )
                ->toArray();

            UserRole::insert($userRolesData);

            // ── 4. Créer les suppliers ─────────────────────────────────────
            $suppliersData = [];

            foreach ($createdUsers as $user) {
                $shopName = $uniqueShopNames[$globalIndex];
                $status = fake()->randomElement($statuses);
                $isApproved = $status === "approved";

                $baseSlug = Str::slug($shopName);
                $slug = $baseSlug . "-" . Str::random(5);
                while (in_array($slug, $slugPool)) {
                    $slug = $baseSlug . "-" . Str::random(6);
                }
                $slugPool[] = $slug;

                $suppliersData[] = [
                    "user_id" => $user->id,
                    "country_id" => fake()->randomElement($countryIds),
                    "shop_name" => $shopName,
                    "slug" => $slug,
                    "logo" => null,
                    "banner" => null,
                    "description" => fake()->boolean(70)
                        ? fake()->paragraph()
                        : null,
                    "business_type" => fake()->randomElement($businessTypes),
                    "registration_number" => fake()->boolean(60)
                        ? fake()->numerify("REG-#######")
                        : null,
                    "tax_number" => fake()->boolean(50)
                        ? fake()->numerify("TAX-#######")
                        : null,
                    "website" => fake()->boolean(40) ? fake()->url() : null,
                    "status" => $status,
                    "rejection_reason" =>
                        $status === "rejected" ? fake()->sentence() : null,
                    "commission_rate" => fake()->randomElement(
                        $commissionRates,
                    ),
                    "is_featured" => fake()->boolean(10),
                    "is_verified" => $isApproved && fake()->boolean(40),
                    "average_rating" => $isApproved
                        ? fake()->randomFloat(1, 1, 5)
                        : 0,
                    "total_reviews" => $isApproved
                        ? fake()->numberBetween(0, 500)
                        : 0,
                    "total_sales" => $isApproved
                        ? fake()->numberBetween(0, 10000)
                        : 0,
                    "total_products" => $isApproved
                        ? fake()->numberBetween(0, 200)
                        : 0,
                    "approved_at" => $isApproved
                        ? fake()->dateTimeBetween("-1 year", "now")
                        : null,
                    "approved_by" => null,
                    "created_at" => fake()->dateTimeBetween("-2 years", "now"),
                    "updated_at" => $now,
                ];

                $globalIndex++;
            }

            Supplier::insert($suppliersData);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ {$total} suppliers created successfully.");
        $this->command->info("   → Default password: password");
    }
}
