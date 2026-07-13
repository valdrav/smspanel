<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kullanıcı tablosuna ek alanlar ekler.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('status', 20)->default('active')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');

            $table->index('status');
            $table->index('phone');
        });
    }

    /**
     * Eklenen alanları geri alır.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['phone']);
            $table->dropColumn(['phone', 'status', 'last_login_at']);
        });
    }
};
