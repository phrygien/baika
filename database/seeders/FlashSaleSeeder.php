<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\FlashSale;
use App\Models\FlashSaleSlot;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\User;

class FlashSaleSeeder extends Seeder
{
    public function run(): void
    {
        // ── Grab existing resources ────────────────────────────────────────
        $products = Product::where("status", "approved")
            ->where("is_active", true)
            ->inRandomOrder()
            ->limit(60)
            ->get();
        $category = Category::first();
        $supplier = Supplier::first();
        $adminUser =
            User::where("email", "admin@example.com")->first() ?? User::first();

        if ($products->isEmpty()) {
            $this->command->warn(
                "⚠  No approved products found — run ProductSeeder first.",
            );
            return;
        }

        $this->command->info("🌱 Seeding Flash Sales…");

        // ══════════════════════════════════════════════════════════════════
        //  1 ▸ ACTIVE FEATURED FLASH SALE — ends in ~3 hours
        //      Full featured: countdown, stock bar, sold count, low stock
        // ══════════════════════════════════════════════════════════════════
        $sale1 = FlashSale::create([
            "title" => "⚡ Flash Sale du Jour",
            "slug" => "flash-sale-du-jour-" . now()->format("Ymd"),
            "description" =>
                "Profitez de réductions incroyables pendant 3h seulement !",
            "badge_text" => "🔥 -75% MAX",
            "background_color" => "#CC0C39",
            "text_color" => "#ffffff",
            "organizer_supplier_id" => $supplier?->id,
            "teaser_starts_at" => now()->subHour(),
            "starts_at" => now()->subMinutes(30),
            "ends_at" => now()->addHours(3),
            "is_recurring" => false,
            "is_featured" => true,
            "show_countdown" => true,
            "show_stock_level" => true,
            "show_sold_count" => true,
            "requires_registration" => false,
            "max_orders_per_user" => 3,
            "status" => "active",
            "total_products" => 10,
            "total_views" => rand(1200, 8000),
            "total_orders" => rand(80, 400),
            "total_revenue" => rand(5000, 50000),
            "total_subscribers" => rand(200, 1000),
            "created_by" => $adminUser?->id,
            "approved_by" => $adminUser?->id,
            "approved_at" => now()->subDay(),
        ]);

        // Slot for sale 1
        $slot1 = FlashSaleSlot::create([
            "flash_sale_id" => $sale1->id,
            "category_id" => $category?->id,
            "title" => "Slot Principal",
            "badge_text" => "HOT",
            "starts_at" => now()->subMinutes(30),
            "ends_at" => now()->addHours(3),
            "is_active" => true,
            "sort_order" => 1,
            "products_count" => 10,
        ]);

        // Products for sale 1 — mix of good discounts + low stock
        $sale1Products = $products->take(10);
        foreach ($sale1Products as $i => $product) {
            $originalPrice = $product->base_price ?? 50.0;
            $discountPct = collect([30, 40, 50, 60, 70, 75])->random();
            $flashPrice = round($originalPrice * (1 - $discountPct / 100), 2);
            $totalStock = rand(20, 200);
            $soldStock = (int) (($totalStock * rand(30, 90)) / 100); // 30–90% sold
            $reservedStock = rand(0, min(5, $totalStock - $soldStock));

            FlashSaleProduct::create([
                "flash_sale_id" => $sale1->id,
                "flash_sale_slot_id" => $slot1->id,
                "supplier_id" => $supplier?->id ?? $product->supplier_id,
                "product_id" => $product->id,
                "original_price" => $originalPrice,
                "flash_price" => max(0.01, $flashPrice),
                "discount_percentage" => $discountPct,
                "flash_stock_total" => $totalStock,
                "flash_stock_reserved" => $reservedStock,
                "flash_stock_sold" => $soldStock,
                "max_quantity_per_order" => rand(1, 5),
                "max_quantity_per_user" => rand(1, 3),
                "status" => "active",
                "is_featured" => $i === 0, // first product is featured
                "show_stock_level" => true,
                "low_stock_threshold" => 10,
                "view_count" => rand(100, 5000),
                "add_to_cart_count" => rand(20, 500),
                "restore_stock_after_sale" => true,
                "sort_order" => $i + 1,
            ]);
        }

        $this->command->info(
            "  ✓ Sale 1 created: \"{$sale1->title}\" (active, ends in 3h, {$sale1Products->count()} products)",
        );

        // ══════════════════════════════════════════════════════════════════
        //  2 ▸ ACTIVE SALE — ends in 45 minutes (urgency!)
        //      Different color, mostly sold out stock
        // ══════════════════════════════════════════════════════════════════
        $sale2 = FlashSale::create([
            "title" => "Ventes Flash High-Tech",
            "slug" => "ventes-flash-high-tech-" . now()->format("Ymd-Hi"),
            "description" =>
                "Smartphones, écouteurs, accessoires — dernières minutes !",
            "badge_text" => "⏱ DERNIÈRES MINUTES",
            "background_color" => "#0066cc",
            "text_color" => "#ffffff",
            "teaser_starts_at" => now()->subHours(2),
            "starts_at" => now()->subHours(2),
            "ends_at" => now()->addMinutes(45),
            "is_recurring" => false,
            "is_featured" => false,
            "show_countdown" => true,
            "show_stock_level" => true,
            "show_sold_count" => true,
            "status" => "active",
            "total_products" => 8,
            "total_views" => rand(3000, 15000),
            "total_orders" => rand(300, 800),
            "total_revenue" => rand(20000, 100000),
            "created_by" => $adminUser?->id,
            "approved_by" => $adminUser?->id,
            "approved_at" => now()->subDays(2),
        ]);

        $slot2 = FlashSaleSlot::create([
            "flash_sale_id" => $sale2->id,
            "category_id" => $category?->id,
            "title" => "High-Tech Deals",
            "starts_at" => now()->subHours(2),
            "ends_at" => now()->addMinutes(45),
            "is_active" => true,
            "sort_order" => 1,
        ]);

        $sale2Products = $products->skip(10)->take(8);
        foreach ($sale2Products as $i => $product) {
            $originalPrice = $product->base_price ?? 80.0;
            $discountPct = collect([20, 25, 35, 45, 55])->random();
            $flashPrice = round($originalPrice * (1 - $discountPct / 100), 2);
            $totalStock = rand(10, 50);
            // Mostly sold — only a few left
            $soldStock = (int) (($totalStock * rand(75, 95)) / 100);
            $soldStock = min($soldStock, $totalStock - rand(1, 8));

            FlashSaleProduct::create([
                "flash_sale_id" => $sale2->id,
                "flash_sale_slot_id" => $slot2->id,
                "supplier_id" => $supplier?->id ?? $product->supplier_id,
                "product_id" => $product->id,
                "original_price" => $originalPrice,
                "flash_price" => max(0.01, $flashPrice),
                "discount_percentage" => $discountPct,
                "flash_stock_total" => $totalStock,
                "flash_stock_reserved" => rand(0, 3),
                "flash_stock_sold" => max(0, min($soldStock, $totalStock)),
                "max_quantity_per_order" => 2,
                "max_quantity_per_user" => 1,
                "status" => "active",
                "is_featured" => false,
                "show_stock_level" => true,
                "low_stock_threshold" => 5,
                "view_count" => rand(500, 8000),
                "add_to_cart_count" => rand(100, 1000),
                "restore_stock_after_sale" => false,
                "sort_order" => $i + 1,
            ]);
        }

        $this->command->info(
            "  ✓ Sale 2 created: \"{$sale2->title}\" (active, ends in 45min, {$sale2Products->count()} products)",
        );

        // ══════════════════════════════════════════════════════════════════
        //  3 ▸ ACTIVE SALE — 1 product SOLD OUT, rest available
        //      Tests the "Sold out" overlay
        // ══════════════════════════════════════════════════════════════════
        $sale3 = FlashSale::create([
            "title" => "Soldes Beauté & Bien-être",
            "slug" => "soldes-beaute-" . now()->format("Ymd"),
            "description" => "Cosmétiques, soins, parfums à prix cassés.",
            "badge_text" => "🌸 -60%",
            "background_color" => "#8b0057",
            "text_color" => "#ffffff",
            "teaser_starts_at" => now()->subHours(1),
            "starts_at" => now()->subHour(),
            "ends_at" => now()->addHours(6),
            "is_recurring" => false,
            "is_featured" => false,
            "show_countdown" => true,
            "show_stock_level" => true,
            "show_sold_count" => false,
            "status" => "active",
            "total_products" => 6,
            "total_views" => rand(500, 3000),
            "total_orders" => rand(50, 200),
            "created_by" => $adminUser?->id,
            "approved_by" => $adminUser?->id,
            "approved_at" => now()->subDay(),
        ]);

        $slot3 = FlashSaleSlot::create([
            "flash_sale_id" => $sale3->id,
            "category_id" => $category?->id,
            "title" => "Beauté Flash",
            "starts_at" => now()->subHour(),
            "ends_at" => now()->addHours(6),
            "is_active" => true,
            "sort_order" => 1,
        ]);

        $sale3Products = $products->skip(18)->take(6);
        foreach ($sale3Products as $i => $product) {
            $originalPrice = $product->base_price ?? 35.0;
            $discountPct = collect([40, 50, 55, 60])->random();
            $flashPrice = round($originalPrice * (1 - $discountPct / 100), 2);
            $totalStock = rand(20, 80);

            // Make first product sold out to test overlay
            $soldStock =
                $i === 0 ? $totalStock : rand(5, (int) ($totalStock * 0.7));

            FlashSaleProduct::create([
                "flash_sale_id" => $sale3->id,
                "flash_sale_slot_id" => $slot3->id,
                "supplier_id" => $supplier?->id ?? $product->supplier_id,
                "product_id" => $product->id,
                "original_price" => $originalPrice,
                "flash_price" => max(0.01, $flashPrice),
                "discount_percentage" => $discountPct,
                "flash_stock_total" => $totalStock,
                "flash_stock_reserved" => 0,
                "flash_stock_sold" => $soldStock,
                "max_quantity_per_order" => 3,
                "max_quantity_per_user" => 2,
                "status" => $soldStock >= $totalStock ? "sold_out" : "active",
                "is_featured" => $i === 1,
                "show_stock_level" => true,
                "low_stock_threshold" => 8,
                "view_count" => rand(200, 3000),
                "add_to_cart_count" => rand(50, 600),
                "restore_stock_after_sale" => true,
                "sort_order" => $i + 1,
            ]);
        }

        $this->command->info(
            "  ✓ Sale 3 created: \"{$sale3->title}\" (active, 1 product sold out)",
        );

        // ══════════════════════════════════════════════════════════════════
        //  4 ▸ UPCOMING SALE — starts in 2 hours (teaser)
        // ══════════════════════════════════════════════════════════════════
        $sale4 = FlashSale::create([
            "title" => "Ventes Flash Weekend — Prochainement",
            "slug" => "flash-weekend-" . now()->addHours(2)->format("Ymd-Hi"),
            "description" =>
                'Le meilleur de l\'électronique, mode et maison. Inscrivez-vous pour être alerté !',
            "badge_text" => "🔔 BIENTÔT",
            "background_color" => "#f08804",
            "text_color" => "#111111",
            "teaser_starts_at" => now()->subMinutes(30),
            "starts_at" => now()->addHours(2),
            "ends_at" => now()->addHours(8),
            "is_recurring" => false,
            "is_featured" => false,
            "show_countdown" => true,
            "show_stock_level" => false,
            "show_sold_count" => false,
            "requires_registration" => true,
            "status" => "scheduled",
            "total_products" => 15,
            "total_subscribers" => rand(300, 1200),
            "created_by" => $adminUser?->id,
            "approved_by" => $adminUser?->id,
            "approved_at" => now()->subDays(3),
        ]);

        // Add teaser products (not yet active)
        $sale4Products = $products->skip(24)->take(4);
        foreach ($sale4Products as $i => $product) {
            $originalPrice = $product->base_price ?? 60.0;
            $discountPct = collect([25, 30, 40, 50, 60, 70])->random();

            FlashSaleProduct::create([
                "flash_sale_id" => $sale4->id,
                "supplier_id" => $supplier?->id ?? $product->supplier_id,
                "product_id" => $product->id,
                "original_price" => $originalPrice,
                "flash_price" => round(
                    $originalPrice * (1 - $discountPct / 100),
                    2,
                ),
                "discount_percentage" => $discountPct,
                "flash_stock_total" => rand(30, 100),
                "flash_stock_reserved" => 0,
                "flash_stock_sold" => 0,
                "max_quantity_per_order" => 2,
                "status" => "scheduled",
                "show_stock_level" => false,
                "low_stock_threshold" => 10,
                "sort_order" => $i + 1,
            ]);
        }

        $this->command->info(
            "  ✓ Sale 4 created: \"{$sale4->title}\" (scheduled, starts in 2h)",
        );

        // ══════════════════════════════════════════════════════════════════
        //  5 ▸ UPCOMING SALE — starts tomorrow (longer teaser)
        // ══════════════════════════════════════════════════════════════════
        $sale5 = FlashSale::create([
            "title" => "Méga Flash Sale Demain",
            "slug" => "mega-flash-sale-" . now()->addDay()->format("Ymd"),
            "description" =>
                "Plus de 500 produits en promotion. Ne ratez pas ça !",
            "badge_text" => "🌟 MEGA SALE",
            "background_color" => "#005b96",
            "text_color" => "#ffffff",
            "teaser_starts_at" => now(),
            "starts_at" => now()->addDay()->startOfDay(),
            "ends_at" => now()->addDay()->endOfDay(),
            "is_recurring" => false,
            "is_featured" => true,
            "show_countdown" => true,
            "show_stock_level" => false,
            "show_sold_count" => false,
            "requires_registration" => false,
            "status" => "scheduled",
            "total_products" => 50,
            "total_subscribers" => rand(500, 3000),
            "created_by" => $adminUser?->id,
            "approved_by" => $adminUser?->id,
            "approved_at" => now()->subDays(1),
        ]);

        $sale5Products = $products->skip(28)->take(4);
        foreach ($sale5Products as $i => $product) {
            $originalPrice = $product->base_price ?? 45.0;
            $discountPct = collect([50, 60, 70, 75, 80])->random();

            FlashSaleProduct::create([
                "flash_sale_id" => $sale5->id,
                "supplier_id" => $supplier?->id ?? $product->supplier_id,
                "product_id" => $product->id,
                "original_price" => $originalPrice,
                "flash_price" => round(
                    $originalPrice * (1 - $discountPct / 100),
                    2,
                ),
                "discount_percentage" => $discountPct,
                "flash_stock_total" => rand(50, 300),
                "flash_stock_reserved" => 0,
                "flash_stock_sold" => 0,
                "max_quantity_per_order" => 3,
                "status" => "scheduled",
                "show_stock_level" => false,
                "low_stock_threshold" => 15,
                "sort_order" => $i + 1,
            ]);
        }

        $this->command->info(
            "  ✓ Sale 5 created: \"{$sale5->title}\" (scheduled, starts tomorrow)",
        );

        // ══════════════════════════════════════════════════════════════════
        //  6 ▸ ENDED SALE — for historical data / analytics testing
        // ══════════════════════════════════════════════════════════════════
        $sale6 = FlashSale::create([
            "title" => "Flash Sale Terminée (Hier)",
            "slug" => "flash-sale-terminee-" . now()->subDay()->format("Ymd"),
            "badge_text" => "TERMINÉE",
            "background_color" => "#555555",
            "text_color" => "#ffffff",
            "starts_at" => now()->subDay()->subHours(4),
            "ends_at" => now()->subDay(),
            "is_featured" => false,
            "show_countdown" => false,
            "show_stock_level" => false,
            "show_sold_count" => true,
            "status" => "ended",
            "total_products" => 12,
            "total_views" => rand(8000, 30000),
            "total_orders" => rand(400, 1500),
            "total_revenue" => rand(50000, 200000),
            "created_by" => $adminUser?->id,
            "approved_by" => $adminUser?->id,
            "approved_at" => now()->subDays(5),
        ]);

        $sale6Products = $products->skip(32)->take(6);
        foreach ($sale6Products as $i => $product) {
            $originalPrice = $product->base_price ?? 40.0;
            $discountPct = collect([30, 40, 50, 60])->random();
            $totalStock = rand(50, 150);

            FlashSaleProduct::create([
                "flash_sale_id" => $sale6->id,
                "supplier_id" => $supplier?->id ?? $product->supplier_id,
                "product_id" => $product->id,
                "original_price" => $originalPrice,
                "flash_price" => round(
                    $originalPrice * (1 - $discountPct / 100),
                    2,
                ),
                "discount_percentage" => $discountPct,
                "flash_stock_total" => $totalStock,
                "flash_stock_reserved" => 0,
                "flash_stock_sold" => $totalStock, // all sold
                "max_quantity_per_order" => 2,
                "status" => "sold_out",
                "show_stock_level" => true,
                "low_stock_threshold" => 10,
                "restore_stock_after_sale" => true,
                "stock_restored" => true,
                "stock_restored_at" => now()->subHours(2),
                "sort_order" => $i + 1,
            ]);
        }

        $this->command->info(
            "  ✓ Sale 6 created: \"{$sale6->title}\" (ended, for analytics)",
        );

        // ══════════════════════════════════════════════════════════════════
        //  Summary
        // ══════════════════════════════════════════════════════════════════
        $this->command->newLine();
        $this->command->table(
            ["Sale", "Status", "Products", "Ends / Starts"],
            [
                [
                    $sale1->title,
                    "🟢 active",
                    "10",
                    "in " . now()->diffForHumans($sale1->ends_at, true),
                ],
                [
                    $sale2->title,
                    "🟢 active",
                    "8",
                    "in " . now()->diffForHumans($sale2->ends_at, true),
                ],
                [
                    $sale3->title,
                    "🟢 active",
                    "6",
                    "in " . now()->diffForHumans($sale3->ends_at, true),
                ],
                [
                    $sale4->title,
                    "🔵 scheduled",
                    "4",
                    "starts " . now()->diffForHumans($sale4->starts_at, true),
                ],
                [$sale5->title, "🔵 scheduled", "4", "starts tomorrow"],
                [$sale6->title, "⚫ ended", "6", "ended yesterday"],
            ],
        );
        $this->command->info("✅ FlashSaleSeeder completed successfully!");
    }
}
