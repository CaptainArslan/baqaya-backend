<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_devices', function (Blueprint $table): void {
            $table->uuid('device_uuid')->nullable()->unique()->after('id');
            $table->timestamp('last_seen_at')->nullable()->after('last_used_at');
            $table->timestamp('last_sync_at')->nullable()->after('last_seen_at');
            $table->timestamp('revoked_at')->nullable()->after('last_sync_at');
        });

        Schema::table('shops', function (Blueprint $table): void {
            $table->unsignedBigInteger('server_version')->default(1)->after('terms_accepted_at');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->string('status', 20)->default('active')->after('type');
            $table->foreignId('reversal_of_transaction_id')->nullable()->after('status')
                ->constrained('transactions')->nullOnDelete();
            $table->foreignId('corrected_by_transaction_id')->nullable()->after('reversal_of_transaction_id')
                ->constrained('transactions')->nullOnDelete();
            $table->text('correction_reason')->nullable()->after('corrected_by_transaction_id');
            $table->timestamp('corrected_at')->nullable()->after('correction_reason');
            $table->foreignId('corrected_by')->nullable()->after('corrected_at')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->after('corrected_by')
                ->constrained('user_devices')->nullOnDelete();
            $table->string('sync_operation_id')->nullable()->after('device_id');
            $table->text('notes')->nullable()->after('description');
            $table->string('category')->nullable()->after('notes');
            $table->string('attachment_path')->nullable()->after('category');

            $table->index('status');
            $table->index('reversal_of_transaction_id');
            $table->index('corrected_by_transaction_id');
            $table->index('deleted_at');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank', 'easypaisa', 'jazzcash', 'other'])->default('cash');
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('category')->nullable();
            $table->string('attachment_path')->nullable();
            $table->date('payment_date');
            $table->decimal('balance_after', 15, 2);
            $table->string('status', 20)->default('active');
            $table->foreignId('reversal_of_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('corrected_by_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->text('correction_reason')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('user_devices')->nullOnDelete();
            $table->string('sync_operation_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('client_created_at')->nullable();
            $table->timestamp('client_updated_at')->nullable();
            $table->unsignedBigInteger('server_version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'customer_id']);
            $table->index(['shop_id', 'payment_date']);
            $table->index(['shop_id', 'server_version']);
            $table->index('status');
            $table->index('deleted_at');
        });

        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('entry_type', 50);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->nullableMorphs('source');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('sync_operation_id')->nullable();
            $table->unsignedBigInteger('server_version')->default(1);
            $table->timestamps();

            $table->index(['shop_id', 'customer_id']);
            $table->index(['shop_id', 'server_version']);
        });

        Schema::create('transaction_corrections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->foreignId('reversal_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('corrected_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->text('correction_reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('sync_operation_id')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_corrections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_payment_id')->constrained('payments')->restrictOnDelete();
            $table->foreignId('reversal_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('corrected_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->text('correction_reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('sync_operation_id')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('user_devices')->nullOnDelete();
            $table->string('request_id')->unique();
            $table->unsignedInteger('operations_count')->default(0);
            $table->json('response_payload')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->index(['shop_id', 'device_id']);
        });

        Schema::create('sync_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sync_request_id')->constrained()->cascadeOnDelete();
            $table->string('operation_id')->unique();
            $table->string('operation_type', 80);
            $table->json('payload');
            $table->string('status', 30)->default('pending');
            $table->json('result')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('operation_id');
        });

        Schema::create('reminders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('message');
            $table->string('status', 30)->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('server_version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'server_version']);
        });

        Schema::create('reminder_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reminder_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 30)->default('whatsapp');
            $table->string('status', 30);
            $table->text('provider_response')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();
        });

        Schema::create('failed_syncs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_id')->nullable();
            $table->string('operation_id')->nullable();
            $table->string('operation_type', 80)->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message');
            $table->timestamps();

            $table->index('request_id');
            $table->index('operation_id');
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('user_devices')->nullOnDelete();
            $table->string('action_type', 50);
            $table->string('entity_type', 80);
            $table->string('entity_uuid')->nullable();
            $table->boolean('sync_origin')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'action_type']);
            $table->index('entity_uuid');
        });

        Schema::table('customer_statement_pdfs', function (Blueprint $table): void {
            $table->string('status', 30)->default('pending')->after('to_date');
            $table->foreignId('requested_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_statement_pdfs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropColumn('status');
        });

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('failed_syncs');
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('sync_operations');
        Schema::dropIfExists('sync_requests');
        Schema::dropIfExists('payment_corrections');
        Schema::dropIfExists('transaction_corrections');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('payments');

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['reversal_of_transaction_id']);
            $table->dropForeign(['corrected_by_transaction_id']);
            $table->dropForeign(['corrected_by']);
            $table->dropForeign(['device_id']);
            $table->dropColumn([
                'status', 'reversal_of_transaction_id', 'corrected_by_transaction_id',
                'correction_reason', 'corrected_at', 'corrected_by', 'device_id',
                'sync_operation_id', 'notes', 'category', 'attachment_path',
            ]);
        });

        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn('server_version');
        });

        Schema::table('user_devices', function (Blueprint $table): void {
            $table->dropColumn(['device_uuid', 'last_seen_at', 'last_sync_at', 'revoked_at']);
        });
    }
};
