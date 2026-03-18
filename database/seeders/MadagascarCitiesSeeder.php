<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\State;

class MadagascarCitiesSeeder extends Seeder
{
    public function run(): void
    {
        $countryId = 1;

        $cities = [
            "Analamanga" => [
                "Antananarivo",
                "Ambohidratrimo",
                "Ankazobe",
                "Manjakandriana",
                "Andramasina",
            ],
            "Vakinankaratra" => [
                "Antsirabe",
                "Betafo",
                "Ambatolampy",
                "Faratsiho",
            ],
            "Atsinanana" => [
                "Toamasina",
                "Brickaville",
                "Vatomandry",
                "Mahanoro",
            ],
            "Boeny" => ["Mahajanga", "Marovoay", "Mitsinjo"],
            "Diana" => ["Antsiranana", "Ambilobe", "Ambanja", "Nosy Be"],
            "SAVA" => ["Sambava", "Antalaha", "Vohemar", "Andapa"],
            "Haute Matsiatra" => ["Fianarantsoa", "Ambalavao", "Ambohimahasoa"],
            "Atsimo-Andrefana" => ["Toliara", "Morombe", "Betioky"],
            "Anosy" => ["Taolagnaro", "Amboasary"],
            "Androy" => ["Ambovombe", "Bekily"],
            "Menabe" => ["Morondava", "Mahabo"],
            "Ihorombe" => ["Ihosy", "Iakora"],
        ];

        foreach ($cities as $stateName => $cityList) {
            $state = State::where("name", $stateName)
                ->where("country_id", $countryId)
                ->first();

            if (!$state) {
                continue;
            }

            foreach ($cityList as $cityName) {
                City::updateOrCreate(
                    [
                        "name" => $cityName,
                        "state_id" => $state->id,
                        "country_id" => $countryId,
                    ],
                    [
                        "postal_code" => null,
                        "latitude" => null,
                        "longitude" => null,
                        "is_active" => true,
                    ],
                );
            }
        }
    }
}
