<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Personal\Models\Project;
use Tests\TestCase;

class PersonalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_only_owned_projects(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->assignRole('owner');
        $otherUser = User::factory()->create();
        $otherUser->assignRole('owner');

        $this->actingAs($user);
        $response = $this->postJson('/api/v1/projects', [
            'name' => 'My project',
            'description' => 'Private project',
            'status' => 'active',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('projects', [
            'name' => 'My project',
            'user_id' => $user->id,
        ]);

        $otherProject = Project::create([
            'user_id' => $otherUser->id,
            'name' => 'Other project',
        ]);

        $this->getJson('/api/v1/projects/'.$otherProject->id)->assertForbidden();
        $this->getJson('/api/v1/projects')->assertOk()->assertJsonCount(1, 'data');
    }
}
