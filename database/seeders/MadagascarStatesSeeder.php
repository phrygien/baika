<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;

class MadagascarStatesSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ["name" => "Analamanga", "code" => "ANA"],
            ["name" => "Vakinankaratra", "code" => "VAK"],
            ["name" => "Itasy", "code" => "ITA"],
            ["name" => "Bongolava", "code" => "BON"],
            ["name" => "Alaotra-Mangoro", "code" => "ALM"],
            ["name" => "Amoron’i Mania", "code" => "AMM"],
            ["name" => "Haute Matsiatra", "code" => "HMA"],
            ["name" => "Vatovavy", "code" => "VAT"],
            ["name" => "Fitovinany", "code" => "FIT"],
            ["name" => "Atsimo-Atsinanana", "code" => "AAN"],
            ["name" => "Anosy", "code" => "ANO"],
            ["name" => "Androy", "code" => "AND"],
            ["name" => "Atsimo-Andrefana", "code" => "AAR"],
            ["name" => "Menabe", "code" => "MEN"],
            ["name" => "Melaky", "code" => "MEL"],
            ["name" => "Boeny", "code" => "BOE"],
            ["name" => "Betsiboka", "code" => "BET"],
            ["name" => "Sofia", "code" => "SOF"],
            ["name" => "Diana", "code" => "DIA"],
            ["name" => "SAVA", "code" => "SAV"],
            ["name" => "Atsinanana", "code" => "ATS"],
            ["name" => "Analanjirofo", "code" => "ANL"],
            ["name" => "Atsimo-Atsinanana", "code" => "AAT"], // doublon corrigé plus bas
        ];

        foreach ($states as $state) {
            State::updateOrCreate(
                [
                    "name" => $state["name"],
                    "country_id" => 1,
                ],
                [
                    "code" => $state["code"],
                ],
            );
        }
    }
}
