<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use App\Libraries\JWTService;

class ApiAuth implements FilterInterface
{
    public function before($request, $arguments = null)
    {
        $hdr = service('request')->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.+)$/i', $hdr, $m)) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'missing_token']);
        }

        try {
            $claims = (new \App\Libraries\JWTService())->verify($m[1]);
            $iss = getenv('JWT_ISSUER');
            $aud = getenv('JWT_AUDIENCE');
            if (($claims->iss ?? null) !== $iss || ($claims->aud ?? null) !== $aud) {
                return service('response')->setStatusCode(401)->setJSON(['error' => 'invalid_claims']);
            }
            // disponibiliza o usuário para a controller
            service('request')->user = $claims;
        } catch (\Throwable $e) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'invalid_token']);
        }
    }
    public function after($request, $response, $arguments = null) {}
}
