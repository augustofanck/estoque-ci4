<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;

    protected $allowedFields    = ['name', 'email', 'password_hash', 'role'];

    public function getRules(string $context = 'create'): array
    {
        $base = [
            'name'       => 'required|min_length[2]|max_length[150]',
            'email'      => 'required|valid_email|max_length[150]|is_unique[users.email,id,{id}]',
            'role'       => 'required|in_list[0,1,2]',
            'is_active'  => 'permit_empty|in_list[0,1]',
        ];

        if ($context === 'create') {
            $base['password']         = 'required|min_length[6]';
            $base['password_confirm'] = 'required|matches[password]';
        } else {
            $base['password']         = 'permit_empty|min_length[6]';
            $base['password_confirm'] = 'permit_empty|matches[password]';
        }

        return $base;
    }

    protected $validationRules = [
        'name'     => 'required|min_length[2]|max_length[150]',
        'email'    => 'required|valid_email|max_length[150]|is_unique[users.email,id,{id}]',
        'password_hash' => 'permit_empty|min_length[6]',
        'role'     => 'required|in_list[0,1,2]',
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'E-mail já está em uso.'
        ],
        'role' => [
            'in_list' => 'Papel inválido.'
        ],
        'password_hash' => [
            'permit_empty' => 'A senha não pode estar vazia.',
            'min_length' => 'A senha deve conter ao menos 6 caracteres.'
        ]
    ];
}
