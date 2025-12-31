<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user)
    {
        $token = auth('api')->login($user);
        return ['Authorization' => "Bearer $token"];
    }
    
    /** @test */
    public function admin_can_create_doctor()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->postJson('/api/doctors', [
            'name' => 'Dr Teszt',
            'specialization' => 'Kardiológia',
            'room' => '101'
        ], $this->authHeader($admin));

        $response->assertStatus(201);

        $this->assertDatabaseHas('doctors', [
            'name' => 'Dr Teszt'
        ]);
    }

}
