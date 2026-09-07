<?php

namespace Modules\Personal\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_success()
    {
        $response = $this->getJson('/api/projects');

        $response->assertStatus(200);
    }
}
