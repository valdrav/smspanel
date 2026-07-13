<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::table('sms_campaigns', function (Blueprint $table): void {
            $table->timestamp('scheduled_at')->nullable()->after('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('sms_campaigns', function (Blueprint $table): void {
            $table->dropColumn('scheduled_at');
        });
        Schema::dropIfExists('sms_templates');
    }
};
