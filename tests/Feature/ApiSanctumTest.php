<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiSanctumTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_client_can_login_and_receive_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'api@example.com',
            'password' => 'secret123',
            'device_name' => 'MobileApp',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'token',
            'user' => ['id', 'name', 'email'],
        ]);
    }

    public function test_api_client_can_access_protected_dashboard_summary(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
