<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = (int) (session('role') ?? -1);

        $args = $arguments ?? [];
        foreach ($args as $arg) {
            if (strpos($arg, 'min:') === 0) {
                $min = (int) substr($arg, 4);
                if ($role < $min) {
                    return redirect()->to(site_url('login'))
                        ->with('errors', ['Acesso negado.']);
                }
            } elseif (strpos($arg, 'in:') === 0) {
                $list = array_map('intval', explode(',', substr($arg, 3)));
                if (!in_array($role, $list, true)) {
                    return redirect()->to(site_url('login'))
                        ->with('errors', ['Acesso negado.']);
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nada
    }
}
