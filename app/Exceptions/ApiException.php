<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class ApiException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        string $message,
        public readonly string $errorCode = 'ERROR',
        public readonly int $status = Response::HTTP_BAD_REQUEST,
        public readonly ?array $errors = null,
        public readonly ?array $meta = null,
    ) {
        parent::__construct($message, $status);
    }
}
