<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService
{
    private string $secret;
    private string $iss;
    private string $aud;
    private int $ttl;

    public function __construct()
    {
        $this->secret = getenv('JWT_SECRET');
        $this->iss    = getenv('JWT_ISSUER') ?: '';
        $this->aud    = getenv('JWT_AUDIENCE') ?: '';
        $this->ttl    = (int)(getenv('JWT_ACCESS_TTL') ?: 600);
    }

    public function issueAccessToken(int $uid, string $email, array $extra = []): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => $this->iss,
            'aud' => $this->aud,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttl,
            'sub' => (string)$uid,
            'email' => $email,
        ], $extra);

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function verify(string $jwt): object
    {
        return JWT::decode($jwt, new Key($this->secret, 'HS256'));
    }
}