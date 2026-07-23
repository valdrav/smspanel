<?php

use App\Services\Sms\EnsureTexcellProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Tek sağlayıcı: Texcell hesabını DB’ye yazar, diğerlerini varsayılan/aktif olmaktan çıkarır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms_providers')) {
            return;
        }

        if (app()->environment('testing')) {
            return;
        }

        app(EnsureTexcellProvider::class)->ensure();
    }

    public function down(): void
    {
        //
    }
};
