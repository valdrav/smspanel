<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('message');
            $table->string('sender_id', 20)->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedSmallInteger('chunk_size')->default(500);
            $table->unsignedSmallInteger('chunk_delay_seconds')->default(1);
            $table->uuid('batch_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('sms_campaign_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sms_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 20);
            $table->string('name')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('sms_message_id')->nullable()->constrained('sms_messages')->nullOnDelete();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['sms_campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_campaign_recipients');
        Schema::dropIfExists('sms_campaigns');
    }
};
