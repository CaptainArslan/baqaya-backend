<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('business_type')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->string('timezone', 50)->default('Asia/Karachi');
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
