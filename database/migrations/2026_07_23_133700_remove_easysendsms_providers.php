<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * EasySendSMS kaldırıldığı için eski kayıtları temizler; Texcell'i varsayılan yapar.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sms_providers')
            ->where('driver', 'easysendsms')
            ->orWhere('code', 'easysendsms')
            ->delete();

        if (DB::table('sms_providers')->where('code', 'texcell')->exists()) {
            DB::table('sms_providers')->update(['is_default' => false]);
            DB::table('sms_providers')
                ->where('code', 'texcell')
                ->update([
                    'is_active' => true,
                    'is_default' => true,
                    'priority' => 1,
                ]);
        }
    }

    public function down(): void
    {
        // EasySendSMS geri yüklenmez.
    }
};
