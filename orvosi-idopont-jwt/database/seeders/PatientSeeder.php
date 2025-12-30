<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        Patient::create([
            'name' => 'Teszt Elek',
            'email' => 'teszt.elek@mail.hu',
            'birth_date' => '1990-05-10',
        ]);

        Patient::create([
            'name' => 'Minta Júlia',
            'email' => 'minta.julia@mail.hu',
            'birth_date' => '1995-02-10',
        ]);
    }
}
