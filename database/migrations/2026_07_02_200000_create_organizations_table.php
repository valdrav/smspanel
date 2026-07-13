<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Organizasyonlar tablosunu oluşturur.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tax_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active');
            $table->decimal('sms_balance', 12, 4)->default(0);
            $table->string('sms_sender_id', 11)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('name');
        });
    }

    /**
     * Organizasyonlar tablosunu siler.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
