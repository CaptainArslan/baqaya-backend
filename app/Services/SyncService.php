<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditActionType;
use App\Enums\SyncOperationType;
use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\FailedSync;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\Shop;
use App\Models\SyncCursor;
use App\Models\SyncOperation;
use App\Models\SyncRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SyncService
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly TransactionService $transactionService,
        private readonly PaymentService $paymentService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  array{request_id: string, operations: list<array{operation_id: string, type: string, payload: array<string, mixed>}>}  $payload
     * @return array<string, mixed>
     */
    public function push(
        Shop $shop,
        User $user,
        UserDevice $device,
        array $payload,
        Request $request,
    ): array {
        $existing = SyncRequest::query()->where('request_id', $payload['request_id'])->first();

        if ($existing !== null && $existing->response_payload !== null) {
            return $existing->response_payload;
        }

        return DB::transaction(function () use ($shop, $user, $device, $payload, $request) {
            $syncRequest = SyncRequest::query()->create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'device_id' => $device->id,
                'request_id' => $payload['request_id'],
                'operations_count' => count($payload['operations']),
                'status' => 'processing',
            ]);

            $results = [];

            foreach ($payload['operations'] as $operation) {
                $results[] = $this->processOperation($shop, $user, $device, $syncRequest, $operation, $request);
            }

            $response = [
                'request_id' => $payload['request_id'],
                'results' => $results,
            ];

            $syncRequest->update([
                'status' => 'completed',
                'response_payload' => $response,
            ]);

            $device->update(['last_sync_at' => now()]);

            $this->auditService->log(
                AuditActionType::SyncPush,
                SyncRequest::class,
                $syncRequest->uuid,
                $shop,
                $user,
                $device,
                syncOrigin: true,
                request: $request,
            );

            return $response;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function pull(Shop $shop, User $user, UserDevice $device, int $sinceVersion = 0): array
    {
        $cursor = SyncCursor::query()->firstOrCreate(
            ['shop_id' => $shop->id, 'device_id' => $device->device_id],
            ['user_id' => $user->id, 'last_server_version' => 0],
        );

        $customers = Customer::query()
            ->where('shop_id', $shop->id)
            ->where('server_version', '>', $sinceVersion)
            ->get();

        $transactions = Transaction::query()
            ->where('shop_id', $shop->id)
            ->where('server_version', '>', $sinceVersion)
            ->get();

        $payments = Payment::query()
            ->where('shop_id', $shop->id)
            ->where('server_version', '>', $sinceVersion)
            ->get();

        $reminders = Reminder::query()
            ->where('shop_id', $shop->id)
            ->where('server_version', '>', $sinceVersion)
            ->get();

        $maxVersion = max(
            $sinceVersion,
            (int) $customers->max('server_version'),
            (int) $transactions->max('server_version'),
            (int) $payments->max('server_version'),
            (int) $reminders->max('server_version'),
        );

        $cursor->update([
            'last_server_version' => $maxVersion,
            'last_synced_at' => now(),
            'user_id' => $user->id,
        ]);

        $device->update(['last_sync_at' => now()]);

        return [
            'cursor' => $maxVersion,
            'customers' => $customers,
            'transactions' => $transactions,
            'payments' => $payments,
            'reminders' => $reminders,
        ];
    }

    /**
     * @param  array{operation_id: string, type: string, payload: array<string, mixed>}  $operation
     * @return array<string, mixed>
     */
    private function processOperation(
        Shop $shop,
        User $user,
        UserDevice $device,
        SyncRequest $syncRequest,
        array $operation,
        Request $request,
    ): array {
        $existing = SyncOperation::query()->where('operation_id', $operation['operation_id'])->first();

        if ($existing !== null) {
            return [
                'operation_id' => $operation['operation_id'],
                'status' => 'duplicate',
                'result' => $existing->result,
            ];
        }

        $record = SyncOperation::query()->create([
            'sync_request_id' => $syncRequest->id,
            'operation_id' => $operation['operation_id'],
            'operation_type' => $operation['type'],
            'payload' => $operation['payload'],
            'status' => 'processing',
        ]);

        try {
            $result = $this->dispatch($shop, $user, $device, $operation, $request);
            $record->update(['status' => 'completed', 'result' => $result]);

            return [
                'operation_id' => $operation['operation_id'],
                'status' => 'completed',
                'result' => $result,
            ];
        } catch (ApiException $e) {
            $record->update([
                'status' => 'failed',
                'error_code' => $e->errorCode,
                'error_message' => $e->getMessage(),
            ]);

            FailedSync::query()->create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'request_id' => $syncRequest->request_id,
                'operation_id' => $operation['operation_id'],
                'operation_type' => $operation['type'],
                'payload' => $operation['payload'],
                'error_message' => $e->getMessage(),
            ]);

            return [
                'operation_id' => $operation['operation_id'],
                'status' => 'failed',
                'code' => $e->errorCode,
                'message' => $e->getMessage(),
                'meta' => $e->meta,
            ];
        }
    }

    /**
     * @param  array{operation_id: string, type: string, payload: array<string, mixed>}  $operation
     * @return array<string, mixed>
     */
    private function dispatch(Shop $shop, User $user, UserDevice $device, array $operation, Request $request): array
    {
        $type = SyncOperationType::tryFrom($operation['type']);
        $payload = $operation['payload'];
        $operationId = $operation['operation_id'];

        if ($type === null) {
            throw new ApiException('Unknown sync operation.', 'UNKNOWN_OPERATION', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return match ($type) {
            SyncOperationType::CustomerCreate => $this->wrap($this->customerService->create($shop, $user, $payload, true)),
            SyncOperationType::CustomerUpdate => $this->wrap($this->customerService->update(
                $shop,
                $this->customerService->findByUuid($shop, $payload['uuid']),
                $user,
                $payload,
                true,
            )),
            SyncOperationType::CustomerDelete => $this->deleteCustomerSync($shop, $user, $payload),
            SyncOperationType::TransactionCreate => $this->wrap($this->transactionService->createCredit(
                $shop,
                $this->customerService->findByUuid($shop, $payload['customer_uuid']),
                $user,
                $payload,
                $device,
                $operationId,
                true,
                $request,
            )),
            SyncOperationType::TransactionCorrect => $this->wrap($this->transactionService->correct(
                $shop,
                Transaction::query()->where('uuid', $payload['transaction_uuid'])->where('shop_id', $shop->id)->firstOrFail(),
                $user,
                $payload,
                $device,
                $operationId,
                true,
                $request,
            )),
            SyncOperationType::TransactionReverse => $this->wrap($this->transactionService->reverse(
                $shop,
                Transaction::query()->where('uuid', $payload['transaction_uuid'])->where('shop_id', $shop->id)->firstOrFail(),
                $user,
                $payload['correction_reason'],
                $device,
                $operationId,
                true,
                $request,
            )),
            SyncOperationType::TransactionMetadataUpdate => $this->wrap($this->transactionService->updateMetadata(
                $shop,
                Transaction::query()->where('uuid', $payload['transaction_uuid'])->where('shop_id', $shop->id)->firstOrFail(),
                $user,
                $payload,
                $device,
                $request,
            )),
            SyncOperationType::PaymentCreate => $this->wrap($this->paymentService->create(
                $shop,
                $this->customerService->findByUuid($shop, $payload['customer_uuid']),
                $user,
                $payload,
                $device,
                $operationId,
                true,
                $request,
            )),
            SyncOperationType::PaymentCorrect => $this->wrap($this->paymentService->correct(
                $shop,
                Payment::query()->where('uuid', $payload['payment_uuid'])->where('shop_id', $shop->id)->firstOrFail(),
                $user,
                $payload,
                $device,
                $operationId,
                true,
                $request,
            )),
            SyncOperationType::PaymentReverse => $this->wrap($this->paymentService->reverse(
                $shop,
                Payment::query()->where('uuid', $payload['payment_uuid'])->where('shop_id', $shop->id)->firstOrFail(),
                $user,
                $payload['correction_reason'],
                $device,
                $operationId,
                true,
                $request,
            )),
            SyncOperationType::PaymentMetadataUpdate => $this->wrap($this->paymentService->updateMetadata(
                $shop,
                Payment::query()->where('uuid', $payload['payment_uuid'])->where('shop_id', $shop->id)->firstOrFail(),
                $user,
                $payload,
                $device,
                $request,
            )),
            SyncOperationType::ReminderCreate => $this->wrap(Reminder::query()->create([
                'shop_id' => $shop->id,
                'customer_id' => $this->customerService->findByUuid($shop, $payload['customer_uuid'])->id,
                'created_by' => $user->id,
                'message' => $payload['message'],
                'status' => 'pending',
            ])),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function deleteCustomerSync(Shop $shop, User $user, array $payload): array
    {
        $this->customerService->delete(
            $shop,
            $this->customerService->findByUuid($shop, $payload['uuid']),
            $user,
            true,
        );

        return ['deleted' => true];
    }

    /**
     * @return array<string, mixed>
     */
    private function wrap(mixed $model): array
    {
        if ($model === null) {
            return [];
        }

        return ['uuid' => $model->uuid ?? null, 'server_version' => $model->server_version ?? null];
    }
}
