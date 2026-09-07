<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense', 'both'])->default('expense');
            $table->string('color', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'name']);
            $table->index(['user_id', 'type']);
        });

        Schema::create('receipt_scans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inbound_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 32)->default('web');
            $table->string('idempotency_key', 64)->unique();
            $table->string('original_sha256', 64);
            $table->text('original_path_encrypted');
            $table->enum('status', ['queued', 'processing', 'awaiting_confirmation', 'pending_review', 'confirmed', 'failed', 'cancelled'])->default('queued');
            $table->decimal('confidence_score', 5, 2)->unsigned()->nullable();
            $table->json('ocr_result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['original_sha256', 'user_id']);
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('receipt_scan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('note_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->string('description', 255);
            $table->decimal('amount', 15, 2)->unsigned();
            $table->char('currency', 3)->default('IDR');
            $table->date('transaction_date');
            $table->string('payment_method', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'type', 'transaction_date']);
            $table->index(['transaction_category_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('receipt_scans');
        Schema::dropIfExists('transaction_categories');
    }
};
