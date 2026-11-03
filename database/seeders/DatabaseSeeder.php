<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Datos base de una BD nueva (ver DatosBaseSeeder). */
    public function run(): void
    {
        $this->call(DatosBaseSeeder::class);
    }
}
