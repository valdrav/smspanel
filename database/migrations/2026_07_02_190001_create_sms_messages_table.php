<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SMS mesajları tablosunu oluşturur.
     */
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('batch_id')->nullable();
            $table->string('recipient', 20);
            $table->text('message');
            $table->string('sender_id', 11)->nullable();
            $table->string('provider', 50);
            $table->string('provider_message_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('segments')->default(1);
            $table->decimal('cost', 10, 4)->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('recipient');
            $table->index('batch_id');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * SMS mesajları tablosunu siler.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
