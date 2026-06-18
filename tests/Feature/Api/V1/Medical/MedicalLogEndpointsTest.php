<?php

namespace Tests\Feature\Api\V1\Medical;

use App\Models\MedicalLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalLogEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/medical/logs');

        $response->assertStatus(401);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/medical/logs', [
            'amount' => 12,
            'logged_at' => now()->toDateTimeString(),
        ]);

        $response->assertStatus(401);
    }

    public function test_index_returns_only_authenticated_user_logs(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        MedicalLog::query()->create([
            'user_id' => $user->id,
            'amount' => 10,
            'type' => 'insulin',
            'logged_at' => now()->subHour(),
            'note' => 'mine',
        ]);

        MedicalLog::query()->create([
            'user_id' => $otherUser->id,
            'amount' => 99,
            'type' => 'insulin',
            'logged_at' => now(),
            'note' => 'other',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/medical/logs');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.note', 'mine');
    }

    public function test_store_creates_medical_log_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $payload = [
            'amount' => 22.75,
            'type' => 'insulin',
            'logged_at' => now()->toDateTimeString(),
            'note' => 'before breakfast',
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/medical/logs', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'amount', 'type', 'logged_at', 'note'],
            ]);

        $this->assertDatabaseHas('medical_logs', [
            'user_id' => $user->id,
            'amount' => 22.75,
            'type' => 'insulin',
            'note' => 'before breakfast',
        ]);
    }

    public function test_store_returns_validation_errors_for_invalid_payload(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/medical/logs', [
            'amount' => 'not-numeric',
            'logged_at' => 'invalid-date',
            'type' => 123,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'logged_at', 'type']);
    }
}
