<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * L'ordre compte : les blocs suivants dependent des precedents.
     */
    public function run(): void
    {
        $this->call([
            UtilisateurSeeder::class,
            BesoinSeeder::class,
            RecrutementSeeder::class,
            EmbaucheSeeder::class,
            ContratSeeder::class,
        ]);
    }
}
