<?php

use App\Enums\RoleName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 20)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });

        Schema::create('support_ticket_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at']);
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::query()->where('name', RoleName::Admin->value)->first();
        if ($admin && $admin->hasPermissionTo('packages.manage')) {
            $admin->revokePermissionTo('packages.manage');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_status_logs');
        Schema::dropIfExists('support_ticket_attachments');
    }
};
