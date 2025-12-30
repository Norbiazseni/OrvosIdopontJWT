<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🟢 Admin user
        User::firstOrCreate(
            ['email' => 'admin@email.hu'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin'
            ]
        );


        // 🔵 Normál userek
        User::factory(10)->create();
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        // 🟣 Appointmentek
        Appointment::factory()->count(5)->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);

}
}
