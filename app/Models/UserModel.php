<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;

    protected $allowedFields    = ['name', 'email', 'password', 'role'];

    protected $validationRules = [
        'name'     => 'required|min_length[2]|max_length[150]',
        'email'    => 'required|valid_email|max_length[150]|is_unique[users.email,id,{id}]',
        'password' => 'permit_empty|min_length[6]',
        'role'     => 'required|in_list[0,1,2]',
    ];

    protected $validationMessages = [];
}
