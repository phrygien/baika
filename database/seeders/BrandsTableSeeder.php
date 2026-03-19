<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandsTableSeeder extends Seeder
{
    private array $realBrands = [
        // Tech
        ["name" => "Apple", "website" => "https://apple.com"],
        ["name" => "Samsung", "website" => "https://samsung.com"],
        ["name" => "Sony", "website" => "https://sony.com"],
        ["name" => "LG", "website" => "https://lg.com"],
        ["name" => "Microsoft", "website" => "https://microsoft.com"],
        ["name" => "Dell", "website" => "https://dell.com"],
        ["name" => "HP", "website" => "https://hp.com"],
        ["name" => "Lenovo", "website" => "https://lenovo.com"],
        ["name" => "Asus", "website" => "https://asus.com"],
        ["name" => "Acer", "website" => "https://acer.com"],
        ["name" => "Huawei", "website" => "https://huawei.com"],
        ["name" => "Xiaomi", "website" => "https://mi.com"],
        ["name" => "OnePlus", "website" => "https://oneplus.com"],
        ["name" => "Google", "website" => "https://google.com"],
        ["name" => "Intel", "website" => "https://intel.com"],
        ["name" => "AMD", "website" => "https://amd.com"],
        ["name" => "Nvidia", "website" => "https://nvidia.com"],
        ["name" => "Canon", "website" => "https://canon.com"],
        ["name" => "Nikon", "website" => "https://nikon.com"],
        ["name" => "Panasonic", "website" => "https://panasonic.com"],

        // Fashion
        ["name" => "Nike", "website" => "https://nike.com"],
        ["name" => "Adidas", "website" => "https://adidas.com"],
        ["name" => "Puma", "website" => "https://puma.com"],
        ["name" => "Gucci", "website" => "https://gucci.com"],
        ["name" => "Louis Vuitton", "website" => "https://louisvuitton.com"],
        ["name" => "Zara", "website" => "https://zara.com"],
        ["name" => "H&M", "website" => "https://hm.com"],
        ["name" => 'Levi\'s', "website" => "https://levis.com"],
        ["name" => "Calvin Klein", "website" => "https://calvinklein.com"],
        ["name" => "Ralph Lauren", "website" => "https://ralphlauren.com"],
        ["name" => "Tommy Hilfiger", "website" => "https://tommy.com"],
        ["name" => "Versace", "website" => "https://versace.com"],
        ["name" => "Balenciaga", "website" => "https://balenciaga.com"],
        ["name" => "Chanel", "website" => "https://chanel.com"],
        ["name" => "Prada", "website" => "https://prada.com"],

        // Sports
        ["name" => "Under Armour", "website" => "https://underarmour.com"],
        ["name" => "New Balance", "website" => "https://newbalance.com"],
        ["name" => "Reebok", "website" => "https://reebok.com"],
        ["name" => "Decathlon", "website" => "https://decathlon.com"],
        ["name" => "The North Face", "website" => "https://thenorthface.com"],
        ["name" => "Columbia", "website" => "https://columbia.com"],
        ["name" => "Salomon", "website" => "https://salomon.com"],
        ["name" => "Speedo", "website" => "https://speedo.com"],
        ["name" => "Wilson", "website" => "https://wilson.com"],
        ["name" => "Head", "website" => "https://head.com"],

        // Beauty
        ["name" => 'L\'Oréal', "website" => "https://loreal.com"],
        ["name" => "Nivea", "website" => "https://nivea.com"],
        ["name" => "Dove", "website" => "https://dove.com"],
        ["name" => "Maybelline", "website" => "https://maybelline.com"],
        ["name" => "MAC", "website" => "https://maccosmetics.com"],
        ["name" => "Clinique", "website" => "https://clinique.com"],
        ["name" => "Lancôme", "website" => "https://lancome.com"],
        ["name" => "Garnier", "website" => "https://garnier.com"],
        ["name" => "Neutrogena", "website" => "https://neutrogena.com"],
        ["name" => "Olay", "website" => "https://olay.com"],

        // Home
        ["name" => "IKEA", "website" => "https://ikea.com"],
        ["name" => "Dyson", "website" => "https://dyson.com"],
        ["name" => "Philips", "website" => "https://philips.com"],
        ["name" => "Bosch", "website" => "https://bosch.com"],
        ["name" => "Whirlpool", "website" => "https://whirlpool.com"],
        ["name" => "KitchenAid", "website" => "https://kitchenaid.com"],
        ["name" => "Nespresso", "website" => "https://nespresso.com"],
        ["name" => "Tefal", "website" => "https://tefal.com"],
        ["name" => "Moulinex", "website" => "https://moulinex.com"],
        ["name" => "Rowenta", "website" => "https://rowenta.com"],

        // Automotive
        ["name" => "Michelin", "website" => "https://michelin.com"],
        ["name" => "Bridgestone", "website" => "https://bridgestone.com"],
        ["name" => "Bosch Auto", "website" => "https://bosch-automotive.com"],
        ["name" => "Castrol", "website" => "https://castrol.com"],
        ["name" => "Shell", "website" => "https://shell.com"],
        ["name" => "3M", "website" => "https://3m.com"],
        ["name" => 'Meguiar\'s', "website" => "https://meguiars.com"],
        ["name" => "Garmin", "website" => "https://garmin.com"],
        ["name" => "Pioneer", "website" => "https://pioneer.com"],
        ["name" => "JVC", "website" => "https://jvc.com"],

        // Food
        ["name" => "Nestlé", "website" => "https://nestle.com"],
        ["name" => "Danone", "website" => "https://danone.com"],
        ["name" => 'Kellogg\'s', "website" => "https://kelloggs.com"],
        ["name" => "Heinz", "website" => "https://heinz.com"],
        ["name" => "Coca-Cola", "website" => "https://coca-cola.com"],
        ["name" => "Pepsi", "website" => "https://pepsi.com"],
        ["name" => "Lipton", "website" => "https://lipton.com"],
        ["name" => "Nescafé", "website" => "https://nescafe.com"],
        ["name" => "Ferrero", "website" => "https://ferrero.com"],
        ["name" => "Haribo", "website" => "https://haribo.com"],

        // Audio
        ["name" => "Bose", "website" => "https://bose.com"],
        ["name" => "JBL", "website" => "https://jbl.com"],
        ["name" => "Sennheiser", "website" => "https://sennheiser.com"],
        ["name" => "Beats", "website" => "https://beatsbydre.com"],
        ["name" => "Marshall", "website" => "https://marshallheadphones.com"],
        ["name" => "Sonos", "website" => "https://sonos.com"],
        ["name" => "Bang & Olufsen", "website" => "https://bang-olufsen.com"],
        ["name" => "Audio-Technica", "website" => "https://audio-technica.com"],
        ["name" => "Shure", "website" => "https://shure.com"],
        ["name" => "AKG", "website" => "https://akg.com"],
    ];

    public function run(): void
    {
        $this->command->info("Creating 200 brands...");
        $bar = $this->command->getOutput()->createProgressBar(200);
        $bar->start();

        $now = now();
        $usedSlugs = Brand::pluck("slug")->toArray();
        $usedNames = Brand::pluck("name")->toArray();
        $data = [];

        // ── 1. Insérer les vraies marques (100) ───────────────────────────
        foreach ($this->realBrands as $brand) {
            if (in_array($brand["name"], $usedNames)) {
                $bar->advance();
                continue;
            }

            $slug = $this->uniqueSlug($brand["name"], $usedSlugs);
            $usedNames[] = $brand["name"];

            $data[] = [
                "name" => $brand["name"],
                "slug" => $slug,
                "logo" => null,
                "website" => $brand["website"],
                "description" => fake()->boolean(60)
                    ? fake()->sentence()
                    : null,
                "is_active" => fake()->boolean(90),
                "is_featured" => fake()->boolean(20),
                "created_at" => fake()->dateTimeBetween("-2 years", "now"),
                "updated_at" => $now,
            ];

            $bar->advance();
        }

        // ── 2. Générer les marques restantes (100) ────────────────────────
        $remaining = 200 - count($data);

        while ($remaining > 0) {
            $name = ucwords(
                fake()
                    ->unique()
                    ->words(fake()->numberBetween(1, 2), true),
            );

            if (in_array($name, $usedNames)) {
                continue;
            }

            $slug = $this->uniqueSlug($name, $usedSlugs);
            $usedNames[] = $name;

            $data[] = [
                "name" => $name,
                "slug" => $slug,
                "logo" => null,
                "website" => fake()->boolean(50) ? fake()->url() : null,
                "description" => fake()->boolean(50)
                    ? fake()->sentence()
                    : null,
                "is_active" => fake()->boolean(85),
                "is_featured" => fake()->boolean(10),
                "created_at" => fake()->dateTimeBetween("-2 years", "now"),
                "updated_at" => $now,
            ];

            $bar->advance();
            $remaining--;
        }

        // ── 3. Insert en masse ────────────────────────────────────────────
        foreach (array_chunk($data, 50) as $chunk) {
            Brand::insert($chunk);
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ 200 brands created:");
        $this->command->info(
            "   → " . count($this->realBrands) . " real brands",
        );
        $this->command->info(
            "   → " . (200 - count($this->realBrands)) . " generated brands",
        );
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
