<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class WebAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('uid')) return;

        // guarda destino apenas em GET
        if ($request->getMethod() === 'get') {
            $uri = current_url(true);
            // mantém querystring
            $qs  = $request->getUri()->getQuery();
            if ($qs) $uri = $uri->setQuery($qs);
            session()->set('intended_url', (string) $uri);
        }

        // AJAX/JSON → 401 (sem redirect)
        if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return service('response')->setStatusCode(401)->setJSON(['ok' => false, 'error' => 'unauthenticated']);
        }

        return redirect()->to(site_url('login'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
