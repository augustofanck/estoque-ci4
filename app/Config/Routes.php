<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Auth web
$routes->get('login',  'Auth::login');
$routes->post('login', 'Auth::doLogin');
$routes->get('logout', 'Auth::logout');

// API JWT
$routes->group('api', ['filter' => 'apiauth'], static function ($routes) {
    $routes->get('ordens', 'OrdemApi::index');
});

// ÁREA LOGADA WEB
$routes->group('', ['filter' => 'webauth'], static function ($routes) {

    // Dashboard: todos logados (0+)
    $routes->get('/', 'Dashboard::index', ['filter' => 'role:min:0']);

    // Ordens: vendedor 0+, editar 1+, deletar 2
    $routes->group('ordens', static function ($r) {
        $r->get('/',              'Ordem::index',               ['filter' => 'role:min:0']);
        $r->get('create',         'Ordem::create',              ['filter' => 'role:min:0']);
        $r->post('',              'Ordem::store',               ['filter' => 'role:min:0']);
        $r->get('(:num)/edit',    'Ordem::edit/$1',             ['filter' => 'role:min:1']);
        $r->post('(:num)/update', 'Ordem::update/$1',           ['filter' => 'role:min:1']);
        $r->get('(:num)/delete',  'Ordem::delete/$1',           ['filter' => 'role:min:2']);
    });

    // Clientes: vendedor 0+, editar 1+, deletar 2
    $routes->group('clientes', static function ($r) {
        $r->get('/',              'Clientes::index',            ['filter' => 'role:min:0']);
        $r->get('create',         'Clientes::create',           ['filter' => 'role:min:0']);
        $r->post('store',         'Clientes::store',            ['filter' => 'role:min:0']);
        $r->get('(:num)/edit',    'Clientes::edit/$1',          ['filter' => 'role:min:1']);
        $r->post('(:num)/update', 'Clientes::update/$1',        ['filter' => 'role:min:1']);
        $r->get('(:num)/delete',  'Clientes::delete/$1',        ['filter' => 'role:min:2']);
    });

    // Usuários: apenas admin (2)
    $routes->group('usuarios', ['filter' => 'role:min:2'], static function ($r) {
        $r->get('/',             'Usuarios::index');
        $r->post('(:num)/role',  'Usuarios::changeRole/$1');
    });
});
