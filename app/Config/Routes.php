<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --- API (JWT) ---
$routes->post('api/login',   'AuthApi::login');
$routes->post('api/refresh', 'AuthApi::refresh');
$routes->post('api/logout',  'AuthApi::logout');

$routes->group('api', ['filter' => 'apiauth'], static function ($routes) {
    $routes->get('ordens', 'OrdemApi::index');
    // ...demais endpoints protegidos...
});

// --- Auth web (sessão) ---
$routes->get('login',  'Auth::login');
$routes->post('login', 'Auth::doLogin');
$routes->get('logout', 'Auth::logout');

// --- Páginas protegidas (sessão) ---
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    // Dashboard
    $routes->get('/', 'Dashboard::index');

    // Ordens
    $routes->get('ordens',                 'Ordem::index');
    $routes->get('ordens/create',          'Ordem::create');
    $routes->post('ordens',                'Ordem::store');
    $routes->get('ordens/(:num)/edit',     'Ordem::edit/$1');
    $routes->post('ordens/(:num)/update',  'Ordem::update/$1');
    $routes->get('ordens/(:num)/delete',   'Ordem::delete/$1');

    // Clientes
    $routes->get('clientes',               'Clientes::index');
    $routes->get('clientes/create',        'Clientes::create');
    $routes->post('clientes/store',        'Clientes::store');
    $routes->get('clientes/(:num)/edit',   'Clientes::edit/$1');
    $routes->post('clientes/(:num)/update', 'Clientes::update/$1');
    $routes->get('clientes/(:num)/delete', 'Clientes::delete/$1');
});
