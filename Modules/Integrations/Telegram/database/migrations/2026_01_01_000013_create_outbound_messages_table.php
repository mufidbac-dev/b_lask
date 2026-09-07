<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('channel_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inbound_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 32);
            $table->string('provider_message_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->string('message_type', 80);
            $table->text('content_encrypted');
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['channel_account_id', 'status', 'created_at']);
            $table->unique(['provider', 'provider_message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_messages');
    }
};
