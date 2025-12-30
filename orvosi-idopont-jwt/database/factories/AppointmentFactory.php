<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition()
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'appointment_time' => $this->faker->dateTimeBetween('+1 days', '+1 month'),
            'status' => $this->faker->randomElement(['pending', 'approved', 'cancelled']),
        ];
    }
}
?>