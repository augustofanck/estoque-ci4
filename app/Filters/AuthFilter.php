<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before($request, $arguments = null)
    {
        if (! session()->get('uid')) return redirect()->to(site_url('login'));
    }
    public function after($request, $response, $arguments = null) {}
}
