<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('channel_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 32);
            $table->string('provider_event_id');
            $table->string('provider_message_id')->nullable();
            $table->string('sender_id');
            $table->string('payload_hash', 64);
            $table->text('payload_encrypted');
            $table->text('normalized_text')->nullable();
            $table->string('status', 32)->default('received');
            $table->string('error_code', 80)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_event_id']);
            $table->index(['channel_account_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_messages');
    }
};
