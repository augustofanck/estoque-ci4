<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table          = 'users';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;

    protected $allowedFields  = ['email', 'password_hash', 'name', 'is_active'];

    protected $validationRules = [
        'email'         => 'required|valid_email|max_length[190]',
        'password_hash' => 'required|max_length[255]',
        'name'          => 'required|min_length[2]|max_length[150]',
        'is_active'     => 'in_list[0,1]',
    ];
}
