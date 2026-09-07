<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_short_lived_link_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/integrations/telegram/link-token');

        $response->assertCreated()
            ->assertJsonStructure(['provider', 'token', 'expires_at']);
        $this->assertDatabaseHas('channel_link_tokens', [
            'user_id' => $user->id,
            'provider' => 'telegram',
            'status' => 'pending',
        ]);
    }

    public function test_webhook_rejects_invalid_secret_and_deduplicates_updates(): void
    {
        config(['services.telegram.webhook_secret' => 'test-secret']);
        Http::fake();

        $payload = [
            'update_id' => 101,
            'message' => [
                'message_id' => 7,
                'chat' => ['id' => 991],
                'from' => ['id' => 991, 'first_name' => 'Test'],
                'text' => '/help',
            ],
        ];

        $this->postJson('/api/v1/integrations/telegram/webhook', $payload)
            ->assertUnauthorized();

        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'];
        $this->withHeaders($headers)
            ->postJson('/api/v1/integrations/telegram/webhook', $payload)
            ->assertOk();
        $this->withHeaders($headers)
            ->postJson('/api/v1/integrations/telegram/webhook', $payload)
            ->assertJson(['duplicate' => true]);

        $this->assertDatabaseHas('inbound_messages', [
            'provider' => 'telegram',
            'provider_event_id' => '101',
        ]);
    }

    public function test_webhook_command_registers_webhook_and_command_menu(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.webhook_secret' => 'test-secret',
        ]);
        Http::fake([
            'https://api.telegram.org/*/setWebhook' => Http::response(['ok' => true]),
            'https://api.telegram.org/*/setMyCommands' => Http::response(['ok' => true]),
        ]);

        Artisan::call('telegram:set-webhook', ['url' => 'https://example.test/api/v1/integrations/telegram/webhook']);

        Http::assertSentCount(2);
        $this->assertStringContainsString('registered', Artisan::output());
    }

    public function test_commands_command_only_updates_the_command_menu(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake(['https://api.telegram.org/*/setMyCommands' => Http::response(['ok' => true])]);

        Artisan::call('telegram:set-commands');

        Http::assertSentCount(1);
        $this->assertStringContainsString('configured', Artisan::output());
    }
}
