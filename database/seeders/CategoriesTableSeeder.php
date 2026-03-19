<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesTableSeeder extends Seeder
{
    // Catégories racines réalistes pour un marketplace
    private array $rootCategories = [
        ["name" => "Electronics", "icon" => "🔌", "commission" => 8],
        ["name" => "Fashion", "icon" => "👗", "commission" => 12],
        ["name" => "Home & Garden", "icon" => "🏡", "commission" => 10],
        ["name" => "Sports & Outdoors", "icon" => "⚽", "commission" => 10],
        ["name" => "Beauty & Health", "icon" => "💄", "commission" => 15],
        ["name" => "Toys & Games", "icon" => "🎮", "commission" => 12],
        ["name" => "Books & Media", "icon" => "📚", "commission" => 8],
        ["name" => "Food & Grocery", "icon" => "🛒", "commission" => 5],
        ["name" => "Automotive", "icon" => "🚗", "commission" => 8],
        ["name" => "Office & Business", "icon" => "💼", "commission" => 10],
    ];

    // Sous-catégories par root
    private array $subCategories = [
        "Electronics" => [
            "Smartphones",
            "Laptops",
            "Tablets",
            "Cameras",
            "TVs",
            "Audio",
            "Gaming",
            "Wearables",
            "Smart Home",
            "Networking",
            "Printers",
            "Storage",
            "Cables",
            "Batteries",
            "GPS",
        ],
        "Fashion" => [
            "Men Clothing",
            "Women Clothing",
            "Kids Clothing",
            "Shoes",
            "Bags",
            "Watches",
            "Jewelry",
            "Sunglasses",
            "Hats",
            "Belts",
            "Scarves",
            "Swimwear",
            "Lingerie",
            "Socks",
            "Activewear",
        ],
        "Home & Garden" => [
            "Furniture",
            "Bedding",
            "Kitchen",
            "Bathroom",
            "Lighting",
            "Rugs",
            "Curtains",
            "Garden Tools",
            "Plants",
            "Storage",
            "Cleaning",
            "Home Decor",
            "DIY",
            "Appliances",
            "Security",
        ],
        "Sports & Outdoors" => [
            "Running",
            "Cycling",
            "Swimming",
            "Fitness",
            "Hiking",
            "Camping",
            "Fishing",
            "Yoga",
            "Team Sports",
            "Racquet Sports",
            "Winter Sports",
            "Water Sports",
            "Climbing",
            "Boxing",
            "Golf",
        ],
        "Beauty & Health" => [
            "Skincare",
            "Haircare",
            "Makeup",
            "Perfume",
            "Vitamins",
            "Medical",
            "Dental",
            "Eye Care",
            "Personal Care",
            "Massage",
            "Shaving",
            "Baby Care",
            "Feminine Care",
            "Weight Loss",
            "Aromatherapy",
        ],
        "Toys & Games" => [
            "Action Figures",
            "Dolls",
            "Board Games",
            "Puzzles",
            "Educational",
            "Arts & Crafts",
            "Outdoor Toys",
            "Baby Toys",
            "Video Games",
            "RC Toys",
            "Building Sets",
            "Card Games",
            "Plush Toys",
            "Science Kits",
            "Musical Toys",
        ],
        "Books & Media" => [
            "Fiction",
            "Non-Fiction",
            "Children Books",
            "Textbooks",
            "Comics",
            "Magazines",
            "Music CDs",
            "DVDs",
            "Vinyl Records",
            "E-Books",
            "Audiobooks",
            "Art Books",
            "Cookbooks",
            "Travel Guides",
            "Self-Help",
        ],
        "Food & Grocery" => [
            "Fresh Produce",
            "Meat & Seafood",
            "Dairy",
            "Bakery",
            "Beverages",
            "Snacks",
            "Organic",
            "International",
            "Pantry",
            "Frozen Food",
            "Coffee & Tea",
            "Condiments",
            "Baby Food",
            "Pet Food",
            "Dietary",
        ],
        "Automotive" => [
            "Car Parts",
            "Tires",
            "Car Audio",
            "Car Care",
            "Motorcycle",
            "Tools",
            "Lighting",
            "Interior",
            "Exterior",
            "Fluids & Oils",
            "Batteries",
            "GPS & Navigation",
            "Safety",
            "Car Seats",
            "Covers",
        ],
        "Office & Business" => [
            "Stationery",
            "Printers",
            "Furniture",
            "Filing",
            "Whiteboards",
            "Shredders",
            "Laminators",
            "Computers",
            "Phones",
            "Networking",
            "Projectors",
            "Ergonomics",
            "Safes",
            "Labels",
            "Planners",
        ],
    ];

    public function run(): void
    {
        $this->command->info("Creating 500 categories...");
        $bar = $this->command->getOutput()->createProgressBar(500);
        $bar->start();

        $now = now();
        $usedSlugs = [];
        $rootIds = [];

        // ── 1. Créer les 10 catégories racines ────────────────────────────
        foreach ($this->rootCategories as $sortOrder => $root) {
            $slug = $this->uniqueSlug($root["name"], $usedSlugs);

            $category = Category::create([
                "parent_id" => null,
                "name" => $root["name"],
                "slug" => $slug,
                "description" => fake()->sentence(),
                "icon" => $root["icon"],
                "image" => null,
                "meta_title" => $root["name"],
                "meta_description" => fake()->sentence(),
                "meta_keywords" =>
                    Str::lower($root["name"]) . ", " . fake()->words(3, true),
                "sort_order" => $sortOrder + 1,
                "commission_rate" => $root["commission"],
                "is_active" => true,
                "is_featured" => fake()->boolean(30),
                "depth" => 0,
                "path" => "",
                "created_at" => $now,
                "updated_at" => $now,
            ]);

            $category->update(["path" => (string) $category->id]);
            $rootIds[$root["name"]] = $category->id;
            $bar->advance();
        }

        // ── 2. Créer les 150 sous-catégories (level 1) ────────────────────
        $level1Ids = [];

        foreach ($this->subCategories as $rootName => $subs) {
            $parentId = $rootIds[$rootName];
            $parentPath = (string) $parentId;

            foreach ($subs as $sortOrder => $subName) {
                $slug = $this->uniqueSlug($subName, $usedSlugs);

                $category = Category::create([
                    "parent_id" => $parentId,
                    "name" => $subName,
                    "slug" => $slug,
                    "description" => fake()->sentence(),
                    "icon" => null,
                    "image" => null,
                    "meta_title" => $subName,
                    "meta_description" => fake()->sentence(),
                    "meta_keywords" =>
                        Str::lower($subName) . ", " . fake()->words(2, true),
                    "sort_order" => $sortOrder + 1,
                    "commission_rate" => fake()->boolean(60)
                        ? fake()->randomElement([5, 8, 10, 12, 15])
                        : null,
                    "is_active" => fake()->boolean(90),
                    "is_featured" => fake()->boolean(15),
                    "depth" => 1,
                    "path" => $parentPath,
                    "created_at" => $now,
                    "updated_at" => $now,
                ]);

                $category->update([
                    "path" => $parentPath . "/" . $category->id,
                ]);
                $level1Ids[] = [
                    "id" => $category->id,
                    "path" => $parentPath . "/" . $category->id,
                ];
                $bar->advance();
            }
        }

        // ── 3. Créer 340 sous-sous-catégories (level 2) ───────────────────
        $remaining = 500 - 10 - 150; // 340
        $level1Count = count($level1Ids);

        for ($i = 0; $i < $remaining; $i++) {
            $parent = $level1Ids[$i % $level1Count];
            $parentId = $parent["id"];
            $parentPath = $parent["path"];

            $name = fake()
                ->unique()
                ->words(fake()->numberBetween(1, 3), true);
            $name = ucwords($name);
            $slug = $this->uniqueSlug($name, $usedSlugs);

            $category = Category::create([
                "parent_id" => $parentId,
                "name" => $name,
                "slug" => $slug,
                "description" => fake()->boolean(60)
                    ? fake()->sentence()
                    : null,
                "icon" => null,
                "image" => null,
                "meta_title" => $name,
                "meta_description" => fake()->boolean(50)
                    ? fake()->sentence()
                    : null,
                "meta_keywords" => fake()->boolean(50)
                    ? fake()->words(3, true)
                    : null,
                "sort_order" => ($i % 20) + 1,
                "commission_rate" => fake()->boolean(50)
                    ? fake()->randomElement([5, 8, 10, 12, 15])
                    : null,
                "is_active" => fake()->boolean(85),
                "is_featured" => fake()->boolean(10),
                "depth" => 2,
                "path" => $parentPath,
                "created_at" => $now,
                "updated_at" => $now,
            ]);

            $category->update(["path" => $parentPath . "/" . $category->id]);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ 500 categories created:");
        $this->command->info("   → 10  root categories");
        $this->command->info("   → 150 level-1 subcategories");
        $this->command->info("   → 340 level-2 subcategories");
    }

    protected function uniqueSlug(string $name, array &$usedSlugs): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (in_array($slug, $usedSlugs)) {
            $slug = $base . "-" . $i++;
        }

        $usedSlugs[] = $slug;
        return $slug;
    }
}
