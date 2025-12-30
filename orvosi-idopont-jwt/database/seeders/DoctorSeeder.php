<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        Doctor::create([
            'name' => 'Dr. Kovács Béla',
            'specialization' => 'Kardiológia',
            'room' => '101'
        ]);

        Doctor::create([
            'name' => 'Dr. Szabó Anna',
            'specialization' => 'Bőrgyógyászat',
            'room' => '102'
        ]);
    }
}
?>