<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RefreshTokenModel;
use App\Libraries\JWTService;

class AuthApi extends BaseController
{
    private function ttlRefresh(): int
    {
        return (int)(getenv('JWT_REFRESH_TTL') ?: 1209600);
    }

    public function login()
    {
        $email = trim((string)$this->request->getPost('email'));
        $pass  = (string)$this->request->getPost('password');

        $key = 'api-login-' . hash('sha256', $this->request->getIPAddress() . '|' . (string)$this->request->getUserAgent());
        if (service('throttler')->check($key, 10, MINUTE) === false) {
            return $this->response->setStatusCode(429)->setJSON(['error' => 'too_many_requests']);
        }

        $u = (new UserModel())->where('email', $email)->first();
        if (!$u || !password_verify($pass, $u['password_hash']) || (int)$u['is_active'] !== 1) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'invalid_credentials']);
        }

        $jwt = (new JWTService())->issueAccessToken((int)$u['id'], $u['email']);
        $rt  = newRefreshToken();

        (new RefreshTokenModel())->insert([
            'user_id'    => $u['id'],
            'token_hash' => password_hash($rt, PASSWORD_DEFAULT),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->ttlRefresh()),
            'ip'         => $this->request->getIPAddress(),
            'ua'         => (string)$this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'access_token'  => $jwt,
            'token_type'    => 'Bearer',
            'expires_in'    => (int)(getenv('JWT_ACCESS_TTL') ?: 600),
            'refresh_token' => $rt,
        ]);
    }

    public function refresh()
    {
        $rt = (string)$this->request->getPost('refresh_token');
        if ($rt === '') return $this->response->setStatusCode(400)->setJSON(['error' => 'missing_refresh']);

        $row = (new RefreshTokenModel())
            ->where('revoked_at', null)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->first(); // checamos todas? Melhor buscar por hash

        // Busca por hash (verificando por lotes não é eficiente). Trate por comparação:
        $model = new RefreshTokenModel();
        $cand = $model->where('revoked_at', null)->where('expires_at >=', date('Y-m-d H:i:s'))->findAll(1000);
        $row = null;
        foreach ($cand as $c) {
            if (password_verify($rt, $c['token_hash'])) {
                $row = $c;
                break;
            }
        }
        if (!$row) return $this->response->setStatusCode(401)->setJSON(['error' => 'invalid_refresh']);

        // Rotação: revoga o antigo e emite novo par
        $model->update($row['id'], ['revoked_at' => date('Y-m-d H:i:s')]);

        $user = (new UserModel())->find($row['user_id']);
        if (!$user || (int)$user['is_active'] !== 1) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'user_inactive']);
        }

        $jwt = (new JWTService())->issueAccessToken((int)$user['id'], $user['email']);
        $newRt = newRefreshToken();
        $model->insert([
            'user_id'    => $user['id'],
            'token_hash' => password_hash($newRt, PASSWORD_DEFAULT),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->ttlRefresh()),
            'ip'         => $this->request->getIPAddress(),
            'ua'         => (string)$this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'access_token'  => $jwt,
            'token_type'    => 'Bearer',
            'expires_in'    => (int)(getenv('JWT_ACCESS_TTL') ?: 600),
            'refresh_token' => $newRt,
        ]);
    }

    public function logout()
    {
        $rt = (string)$this->request->getPost('refresh_token');
        if ($rt === '') return $this->response->setJSON(['ok' => true]); // idempotente

        $model = new RefreshTokenModel();
        $cand = $model->where('revoked_at', null)->findAll(1000);
        foreach ($cand as $c) {
            if (password_verify($rt, $c['token_hash'])) {
                $model->update($c['id'], ['revoked_at' => date('Y-m-d H:i:s')]);
                break;
            }
        }
        return $this->response->setJSON(['ok' => true]);
    }
}
