<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SMS sağlayıcı yapılandırma tablosunu oluşturur.
     */
    public function up(): void
    {
        Schema::create('sms_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('driver', 50);
            $table->text('config');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->decimal('last_balance', 12, 4)->nullable();
            $table->timestamp('last_balance_checked_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });
    }

    /**
     * SMS sağlayıcı tablosunu siler.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_providers');
    }
};
