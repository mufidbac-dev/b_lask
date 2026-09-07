<?php

namespace Modules\Personal\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_success()
    {
        $response = $this->getJson('/api/calendar-events');

        $response->assertStatus(200);
    }
}
