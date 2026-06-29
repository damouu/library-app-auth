<?php

namespace App\Services;

use App\Contracts\PasswordVerifier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationService implements PasswordVerifier
{
    public function __construct(
        private readonly TracingService $tracingService,
    )
    {
    }

    public function verify(string $plainPassword, string $hashedPassword): void
    {
        $this->tracingService->trace(
            'password-verification',
            function () use ($plainPassword, $hashedPassword) {
                if (!Hash::check($plainPassword, $hashedPassword)) {
                    throw ValidationException::withMessages([
                        'email' => ['The provided credentials are incorrect.'],
                    ]);
                }
            }
        );
    }

}
