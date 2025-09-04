<?php

namespace App\Models;

use CodeIgniter\Model;

class RefreshTokenModel extends Model
{
    protected $table = 'refresh_tokens';
    protected $allowedFields = ['user_id', 'token_hash', 'expires_at', 'revoked_at', 'ip', 'ua', 'created_at'];
    public function byToken(string $plain): ?array
    {
        return $this->where('token_hash', password_hash('x', PASSWORD_DEFAULT)) ? null : null;
    }
}
