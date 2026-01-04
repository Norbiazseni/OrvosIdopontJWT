<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Patient;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user)
    {
        $token = auth('api')->login($user);
        return ['Authorization' => "Bearer $token"];
    }

    /** @test */
    public function admin_can_create_patient()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->postJson('/api/patients', [
            'name' => 'Teszt Páciens',
            'birth_date' => '2000-01-01',
            'phone' => '123456789',
        ], $this->authHeader($admin));

        $response->assertStatus(201);

        $this->assertDatabaseHas('patients', [
            'name' => 'Teszt Páciens',
        ]);
    }

    /** @test */
    public function normal_user_can_create_own_patient()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/patients', [
            'name' => 'Saját Páciens',
            'birth_date' => '1999-05-05',
            'phone' => '987654321',
        ], $this->authHeader($user));

        $response->assertStatus(201);

        $this->assertDatabaseHas('patients', [
            'name' => 'Saját Páciens',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function normal_user_cannot_create_patient_for_other_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->postJson('/api/patients', [
            'name' => 'Illegális Páciens',
            'birth_date' => '1990-01-01',
            'phone' => '111111111',
            'user_id' => $otherUser->id,
        ], $this->authHeader($user));

        $response->assertStatus(201);

        // user_id-t felülírja a controller -> saját lesz
        $this->assertDatabaseHas('patients', [
            'name' => 'Illegális Páciens',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function admin_can_see_all_patients()
    {
        $admin = User::factory()->admin()->create();
        Patient::factory()->count(3)->create();

        $response = $this->getJson(
            '/api/patients',
            $this->authHeader($admin)
        );

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    /** @test */
    public function user_sees_only_own_patient()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Patient::factory()->create(['user_id' => $user->id]);
        Patient::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson(
            '/api/patients',
            $this->authHeader($user)
        );

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }
}
