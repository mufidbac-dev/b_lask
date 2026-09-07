<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_account_id');
            $table->string('provider_chat_id');
            $table->string('display_name')->nullable();
            $table->string('status', 32)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['provider', 'provider_account_id']);
            $table->unique(['provider', 'provider_chat_id']);
            $table->index(['user_id', 'provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_accounts');
    }
};
