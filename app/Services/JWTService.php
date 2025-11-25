<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

class JWTService
{
    private string $algorithm;
    private string $secret;
    private string $publicKey;

    public function __construct()
    {
        $this->algorithm = 'RS256';
        $privateKeyPath = base_path('keys/private.pem');
        $privateKey = file_get_contents($privateKeyPath);
        $publicKeyPath = base_path('keys/public.pem');
        $this->publicKey = file_get_contents($publicKeyPath);
        $this->secret = $privateKey;
    }

    public function createToken(array $payload): string
    {
        $payload['iat'] = time();
        $payload['nbf'] = time();
        $payload['exp'] = time() + 3600;

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function verifyToken(string $token): stdClass
    {
        return JWT::decode($token, new Key($this->publicKey, $this->algorithm));
    }

}
