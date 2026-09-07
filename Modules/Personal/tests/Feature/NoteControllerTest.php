<?php

namespace Modules\Personal\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_success()
    {
        $response = $this->getJson('/api/notes');

        $response->assertStatus(200);
    }
}
