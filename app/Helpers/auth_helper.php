<?php
function base64url(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}
function newRefreshToken(): string
{
    return base64url(random_bytes(64));
}
