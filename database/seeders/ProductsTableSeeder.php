<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductsTableSeeder extends Seeder
{
    private array $colors = [
        "Black",
        "White",
        "Red",
        "Blue",
        "Green",
        "Yellow",
        "Pink",
        "Purple",
        "Orange",
        "Grey",
        "Brown",
        "Navy",
        "Beige",
        "Gold",
        "Silver",
    ];
    private array $sizes = [
        "XS",
        "S",
        "M",
        "L",
        "XL",
        "XXL",
        "3XL",
        "36",
        "37",
        "38",
        "39",
        "40",
        "41",
        "42",
        "43",
        "44",
        "45",
    ];
    private array $storages = [
        "32GB",
        "64GB",
        "128GB",
        "256GB",
        "512GB",
        "1TB",
    ];
    private array $rams = ["4GB", "6GB", "8GB", "12GB", "16GB", "32GB"];

    private array $currencies = ["USD", "EUR", "MAD"];
    private array $statuses = [
        "approved",
        "approved",
        "approved",
        "pending",
        "rejected",
    ];

    public function run(): void
    {
        // Détecter les valeurs ENUM disponibles
        $enumValues = $this->getStatusEnumValues();
        if (!empty($enumValues)) {
            $this->statuses = array_values(
                array_merge(
                    array_fill(0, 3, "approved"),
                    array_filter(
                        $enumValues,
                        fn($v) => in_array($v, [
                            "pending",
                            "rejected",
                            "suspended",
                        ]),
                    ),
                ),
            );
            $this->command->info("Status ENUM: " . implode(", ", $enumValues));
        }

        $total = 30000;

        $this->command->info("Loading references...");

        $supplierIds = Supplier::approved()->pluck("id")->toArray();
        $categoryIds = Category::pluck("id")->toArray();
        $brandIds = Brand::pluck("id")->toArray();
        $brandIds[] = null;

        if (empty($supplierIds)) {
            $this->command->error("No approved suppliers found.");
            return;
        }

        if (empty($categoryIds)) {
            $this->command->error("No categories found.");
            return;
        }

        $this->command->info(
            "Creating {$total} products with variants and images...",
        );
        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        $usedSlugs = Product::pluck("slug")->toArray();
        $usedSkus = Product::pluck("sku")->filter()->toArray();
        $now = now();
        $chunks = array_chunk(range(1, $total), 200);

        foreach ($chunks as $chunk) {
            $productsData = [];
            $productNames = [];

            foreach ($chunk as $i) {
                $name = $this->generateProductName();
                $slug = $this->uniqueSlug($name, $usedSlugs);
                $sku = $this->uniqueSku($usedSkus);
                $basePrice = fake()->randomFloat(2, 5, 2000);
                $status = fake()->randomElement($this->statuses);
                $currency = fake()->randomElement($this->currencies);
                $isApproved = $status === "approved";

                $comparePrice = fake()->boolean(30)
                    ? round($basePrice * fake()->randomFloat(2, 1.1, 1.5), 2)
                    : null;

                $productsData[] = [
                    "supplier_id" => fake()->randomElement($supplierIds),
                    "category_id" => fake()->randomElement($categoryIds),
                    "brand_id" => fake()->randomElement($brandIds),
                    "name" => $name,
                    "slug" => $slug,
                    "sku" => $sku,
                    "short_description" => fake()->sentence(),
                    "description" => fake()->paragraphs(3, true),
                    "base_price" => $basePrice,
                    "compare_at_price" => $comparePrice,
                    "cost_price" => round(
                        $basePrice * fake()->randomFloat(2, 0.4, 0.7),
                        2,
                    ),
                    "currency" => $currency,
                    "weight_kg" => fake()->boolean(80)
                        ? fake()->randomFloat(2, 0.1, 50)
                        : null,
                    "length_cm" => fake()->boolean(50)
                        ? fake()->randomFloat(1, 1, 200)
                        : null,
                    "width_cm" => fake()->boolean(50)
                        ? fake()->randomFloat(1, 1, 200)
                        : null,
                    "height_cm" => fake()->boolean(50)
                        ? fake()->randomFloat(1, 1, 200)
                        : null,
                    "requires_shipping" => fake()->boolean(85),
                    "is_digital" => fake()->boolean(5),
                    "digital_file" => null,
                    "status" => $status,
                    "rejection_reason" =>
                        $status === "rejected" ? fake()->sentence() : null,
                    "is_featured" => fake()->boolean(10),
                    "is_active" => fake()->boolean(90),
                    "track_inventory" => fake()->boolean(80),
                    "low_stock_threshold" => fake()->numberBetween(1, 20),
                    "origin_country" => fake()->countryCode(),
                    "hs_code" => fake()->boolean(40)
                        ? fake()->numerify("####.##.##")
                        : null,
                    "barcode" => fake()->boolean(60) ? fake()->ean13() : null,
                    "meta_title" => $name,
                    "meta_description" => fake()->sentence(),
                    "meta_keywords" => fake()->words(5, true),
                    "average_rating" => $isApproved
                        ? fake()->randomFloat(1, 1, 5)
                        : 0,
                    "total_reviews" => $isApproved
                        ? fake()->numberBetween(0, 500)
                        : 0,
                    "total_sold" => $isApproved
                        ? fake()->numberBetween(0, 5000)
                        : 0,
                    "total_views" => $isApproved
                        ? fake()->numberBetween(0, 50000)
                        : 0,
                    "published_at" => $isApproved
                        ? fake()->dateTimeBetween("-1 year", "now")
                        : null,
                    "approved_at" => $isApproved
                        ? fake()->dateTimeBetween("-1 year", "now")
                        : null,
                    "approved_by" => null,
                    "created_at" => fake()->dateTimeBetween("-2 years", "now"),
                    "updated_at" => $now,
                ];

                $productNames[] = $name;
            }

            try {
                Product::insert($productsData);
            } catch (\Exception $e) {
                $this->command->error("Insert error: " . $e->getMessage());
                continue;
            }

            // Récupérer les produits insérés
            $insertedProducts = Product::whereIn("name", $productNames)
                ->whereDoesntHave("variants")
                ->get(["id", "base_price", "name"]);

            $productIds = $insertedProducts->pluck("id")->toArray();

            // ── Images ────────────────────────────────────────────────────
            $this->insertImages($productIds, $now);

            // ── Variantes ─────────────────────────────────────────────────
            $variantsData = [];

            foreach ($insertedProducts as $product) {
                $variantType = fake()->randomElement([
                    "color_size",
                    "storage_ram",
                    "color_only",
                    "size_only",
                    "none",
                ]);
                $combinations = $this->getVariantCombinations($variantType);

                foreach ($combinations as $sortOrder => $combo) {
                    $variantSku = $this->uniqueSku($usedSkus);
                    $variantPrice = round(
                        $product->base_price * fake()->randomFloat(2, 0.8, 1.3),
                        2,
                    );

                    $variantsData[] = [
                        "product_id" => $product->id,
                        "sku" => $variantSku,
                        "name" => $combo["name"],
                        "price" => $variantPrice,
                        "compare_at_price" => fake()->boolean(20)
                            ? round($variantPrice * 1.2, 2)
                            : null,
                        "cost_price" => round(
                            $variantPrice * fake()->randomFloat(2, 0.4, 0.7),
                            2,
                        ),
                        "weight_kg" => fake()->boolean(60)
                            ? fake()->randomFloat(2, 0.1, 10)
                            : null,
                        "barcode" => fake()->boolean(40)
                            ? fake()->ean13()
                            : null,
                        "image" => null,
                        "sort_order" => $sortOrder,
                        "is_active" => fake()->boolean(95),
                        "created_at" => $now,
                        "updated_at" => $now,
                    ];
                }
            }

            if (!empty($variantsData)) {
                foreach (array_chunk($variantsData, 500) as $variantChunk) {
                    ProductVariant::insert($variantChunk);
                }
            }

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ " . Product::count() . " products created.");
        $this->command->info(
            "   → " . ProductVariant::count() . " variants created.",
        );
        $this->command->info(
            "   → " . ProductImage::count() . " images created.",
        );
    }

    // ── Images ─────────────────────────────────────────────────────────────

    protected function insertImages(array $productIds, $now): void
    {
        $imagesData = [];

        // Pool de seeds picsum pour varier les images
        $seeds = range(1, 1000);
        shuffle($seeds);
        $seedIndex = 0;

        foreach ($productIds as $productId) {
            $imageCount = fake()->numberBetween(1, 5);

            for ($i = 0; $i < $imageCount; $i++) {
                $seed = $seeds[$seedIndex % count($seeds)];
                $seedIndex++;

                $imagesData[] = [
                    "product_id" => $productId,
                    "image_path" => "https://picsum.photos/seed/{$seed}/800/800",
                    "alt_text" => fake()->boolean(60)
                        ? fake()->words(3, true)
                        : null,
                    "sort_order" => $i,
                    "is_primary" => $i === 0,
                    "created_at" => $now,
                    "updated_at" => $now,
                ];
            }
        }

        foreach (array_chunk($imagesData, 1000) as $chunk) {
            ProductImage::insert($chunk);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    protected function getStatusEnumValues(): array
    {
        try {
            $result = \DB::select("SHOW COLUMNS FROM products LIKE 'status'");
            if (!empty($result)) {
                $type = $result[0]->Type ?? "";
                preg_match("/^enum\((.+)\)$/", $type, $matches);
                if (!empty($matches[1])) {
                    return array_map(
                        fn($v) => trim($v, "'"),
                        explode(",", $matches[1]),
                    );
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return [];
    }

    protected function generateProductName(): string
    {
        $patterns = [
            fn() => fake()->randomElement([
                "Premium",
                "Ultra",
                "Pro",
                "Classic",
                "Elite",
                "Max",
                "Mini",
                "Plus",
                "Slim",
                "Lite",
            ]) .
                " " .
                ucwords(fake()->words(fake()->numberBetween(1, 3), true)),
            fn() => ucwords(fake()->words(fake()->numberBetween(2, 4), true)),
            fn() => fake()->company() .
                " " .
                fake()->randomElement([
                    "Series",
                    "Edition",
                    "Collection",
                    "Line",
                ]),
            fn() => ucwords(fake()->word()) .
                " " .
                strtoupper(fake()->lexify("??")) .
                "-" .
                fake()->numerify("####"),
        ];

        return ucwords(fake()->randomElement($patterns)());
    }

    protected function getVariantCombinations(string $type): array
    {
        return match ($type) {
            "color_size" => $this->buildCombinations(
                fake()->randomElements(
                    $this->colors,
                    fake()->numberBetween(2, 5),
                ),
                fake()->randomElements(
                    $this->sizes,
                    fake()->numberBetween(2, 4),
                ),
                fn($c, $s) => "{$c} / {$s}",
            ),
            "storage_ram" => $this->buildCombinations(
                fake()->randomElements(
                    $this->storages,
                    fake()->numberBetween(2, 4),
                ),
                fake()->randomElements(
                    $this->rams,
                    fake()->numberBetween(2, 3),
                ),
                fn($s, $r) => "{$s} / {$r} RAM",
            ),
            "color_only" => $this->buildSingle(
                fake()->randomElements(
                    $this->colors,
                    fake()->numberBetween(2, 6),
                ),
            ),
            "size_only" => $this->buildSingle(
                fake()->randomElements(
                    $this->sizes,
                    fake()->numberBetween(2, 5),
                ),
            ),
            default => [["name" => "Default"]],
        };
    }

    protected function buildCombinations(
        array $optionsA,
        array $optionsB,
        callable $format,
    ): array {
        $combinations = [];
        $idx = 0;

        foreach ($optionsA as $a) {
            foreach ($optionsB as $b) {
                $combinations[] = ["name" => $format($a, $b)];
                if (++$idx >= 20) {
                    break 2;
                }
            }
        }

        return $combinations;
    }

    protected function buildSingle(array $options): array
    {
        return array_map(fn($o) => ["name" => $o], $options);
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

    protected function uniqueSku(array &$usedSkus): string
    {
        do {
            $sku = strtoupper(fake()->bothify("??###-####??"));
        } while (in_array($sku, $usedSkus));

        $usedSkus[] = $sku;
        return $sku;
    }
}
