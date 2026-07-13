<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kullanıcı tablosuna SMS bakiye alanı ekler.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('sms_balance', 12, 4)->default(0)->after('status');
            $table->string('sms_sender_id', 11)->nullable()->after('sms_balance');
        });
    }

    /**
     * SMS bakiye alanını geri alır.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['sms_balance', 'sms_sender_id']);
        });
    }
};
