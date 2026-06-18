<?php

namespace Tests\Feature\Api\V1\Glucose;

use App\Models\GlucoseLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlucoseLogEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/glucose/logs');

        $response->assertStatus(401);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/glucose/logs', [
            'glucose_amount' => 120,
            'logged_at' => now()->toDateTimeString(),
        ]);

        $response->assertStatus(401);
    }

    public function test_index_returns_only_authenticated_user_logs(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        GlucoseLog::query()->create([
            'user_id' => $user->id,
            'glucose_amount' => 110,
            'logged_at' => now()->subHour(),
            'note' => 'mine',
        ]);

        GlucoseLog::query()->create([
            'user_id' => $otherUser->id,
            'glucose_amount' => 170,
            'logged_at' => now(),
            'note' => 'other',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/glucose/logs');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.note', 'mine');
    }

    public function test_store_creates_glucose_log_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $payload = [
            'glucose_amount' => 126.5,
            'logged_at' => now()->toDateTimeString(),
            'note' => 'post meal',
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/glucose/logs', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'glucose_amount', 'logged_at', 'note'],
            ]);

        $this->assertDatabaseHas('glucose_logs', [
            'user_id' => $user->id,
            'glucose_amount' => 126.5,
            'note' => 'post meal',
        ]);
    }

    public function test_store_returns_validation_errors_for_invalid_payload(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/glucose/logs', [
            'glucose_amount' => 'not-numeric',
            'logged_at' => 'invalid-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['glucose_amount', 'logged_at']);
    }
}
