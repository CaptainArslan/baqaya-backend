<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Baqaya API",
 *     version="1.0.0",
 *     description="Production API for Baqaya — offline-first ledger, customers, payments, sync, statements, and reminders."
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Baqaya API Server (same host as this documentation)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum",
 *     description="Paste the access_token from OTP verify (Bearer prefix optional)"
 * )
 */
abstract class Controller
{
    //
}
