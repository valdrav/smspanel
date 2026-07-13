<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cüzdan işlemleri tablosunu oluşturur.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 12, 4);
            $table->decimal('balance_before', 12, 4);
            $table->decimal('balance_after', 12, 4);
            $table->string('description');
            $table->nullableMorphs('reference');
            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
            $table->index(['organization_id', 'created_at']);
        });
    }

    /**
     * Cüzdan işlemleri tablosunu siler.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
