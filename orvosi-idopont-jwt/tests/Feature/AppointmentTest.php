<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user)
    {
        $token = auth('api')->login($user);
        return ['Authorization' => "Bearer $token"];
    }

    /** @test */
    public function admin_can_see_all_appointments()
    {
        $admin = User::factory()->admin()->create();
        Appointment::factory()->count(3)->create();

        $response = $this->getJson(
            '/api/appointments',
            $this->authHeader($admin)
        );

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    /** @test */
    public function user_sees_only_own_appointments()
    {
        $user = User::factory()->create();

        $patient = Patient::factory()->create();
        $doctor  = Doctor::factory()->create();

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Appointment::factory()->create(); // másik páciens időpontja

        $response = $this->getJson(
            '/api/appointments',
            $this->authHeader($user)
        );

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }
}
?>