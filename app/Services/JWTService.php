<?php

namespace App\Services;

use App\DTO\JwtPayloadDTO;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;
use Throwable;

class JWTService
{
    private string $algorithm;
    private string $secret;
    private string $publicKey;

    public function __construct(
        private readonly TracingService $tracingService,
    )
    {
        $this->algorithm = 'RS256';
        $privateKeyPath = base_path('keys/private.pem');
        $privateKey = file_get_contents($privateKeyPath);
        $publicKeyPath = base_path('keys/public.pem');
        $this->publicKey = file_get_contents($publicKeyPath);
        $this->secret = $privateKey;
    }

    /**
     * @throws Throwable
     */
    public function createToken(JwtPayloadDTO $payload): string
    {
        return $this->tracingService->trace(
            'jwt.create.token',
            function () use ($payload) {
                return JWT::encode(
                    $payload->toArray(),
                    $this->secret,
                    $this->algorithm
                );
            }, [
                'user.member_card' => $payload->memberCardUuid,
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function verifyToken(string $token): stdClass
    {
        return $this->tracingService->trace(
            'jwt.verify',
            function () use ($token) {
                return JWT::decode($token, new Key($this->publicKey, $this->algorithm));
            }, [
                'jwt.algorithm' => $this->algorithm,
            ]
        );
    }

}
