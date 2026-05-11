<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->enum('whatsapp_status', ['unknown', 'valid', 'invalid'])->default('unknown');
            $table->text('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('client_created_at')->nullable();
            $table->timestamp('client_updated_at')->nullable();
            $table->unsignedBigInteger('server_version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'name']);
            $table->index(['shop_id', 'phone']);
            $table->index(['shop_id', 'server_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
