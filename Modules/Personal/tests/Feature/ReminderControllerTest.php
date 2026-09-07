<?php

namespace Modules\Personal\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_success()
    {
        $response = $this->getJson('/api/reminders');

        $response->assertStatus(200);
    }
}
