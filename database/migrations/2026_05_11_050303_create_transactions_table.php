<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'payment', 'adjustment']);
            $table->decimal('amount', 15, 2);
            $table->enum('direction', ['in', 'out'])->nullable();
            $table->enum('payment_method', ['cash', 'bank', 'easypaisa', 'jazzcash', 'other'])->nullable();
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->decimal('balance_after', 15, 2);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('client_created_at')->nullable();
            $table->timestamp('client_updated_at')->nullable();
            $table->unsignedBigInteger('server_version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'customer_id']);
            $table->index(['shop_id', 'transaction_date']);
            $table->index(['shop_id', 'server_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
