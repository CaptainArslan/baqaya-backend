<?php

declare(strict_types=1);

use App\Jobs\GenerateCustomerStatementPdf;
use App\Jobs\SendWhatsAppReminder;
use App\Models\Customer;
use Illuminate\Support\Facades\Queue;

it('queues pdf statement generation', function (): void {
    Queue::fake();

    ['shop' => $shop, 'token' => $token] = createUserWithShop();
    $customer = Customer::factory()->create(['shop_id' => $shop->id]);

    $this->withToken($token)
        ->postJson("/api/v1/customers/{$customer->uuid}/statement", [
            'from_date' => now()->subMonth()->toDateString(),
            'to_date' => now()->toDateString(),
        ])
        ->assertStatus(202);

    Queue::assertPushed(GenerateCustomerStatementPdf::class);
});

it('queues whatsapp reminder', function (): void {
    Queue::fake();

    ['shop' => $shop, 'token' => $token] = createUserWithShop();
    $customer = Customer::factory()->create(['shop_id' => $shop->id]);

    $this->withToken($token)
        ->postJson('/api/v1/reminders/send', [
            'customer_uuid' => $customer->uuid,
            'message' => 'Please clear your baqaya.',
        ])
        ->assertStatus(202);

    Queue::assertPushed(SendWhatsAppReminder::class);
});
