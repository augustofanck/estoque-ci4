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

    // ORDENS
    $routes->group('ordens', static function ($r) {

        // Se existe o fluxo "cria -> vai pro edit", então edit/update devem ser 0+
        $r->get('/',              'Ordem::index',               ['filter' => 'role:min:0']);
        $r->get('create',         'Ordem::create',              ['filter' => 'role:min:0']);
        $r->post('',              'Ordem::store',               ['filter' => 'role:min:0']);
        $r->get('(:num)/edit',    'Ordem::edit/$1',             ['filter' => 'role:min:0']);
        $r->post('(:num)/update', 'Ordem::update/$1',           ['filter' => 'role:min:0']);

        // Delete de ordem: normalmente mais restrito
        $r->get('(:num)/delete',  'Ordem::delete/$1',           ['filter' => 'role:min:2']);

        // Pagamentos
        $r->post('(:num)/pagamentos/add',           'Ordem::addPagamento/$1',          ['filter' => 'role:min:0']);
        $r->post('(:num)/pagamentos/(:num)/delete', 'Ordem::deletePagamento/$1/$2',    ['filter' => 'role:min:1']);

        // Itens na Ordem (ligados ao edit da ordem)
        // Itens na Ordem
        $r->post('(:num)/itens',            'Ordem::itensStore/$1',        ['filter' => 'role:min:0']);
        $r->put('(:num)/itens/(:num)',      'Ordem::itensUpdate/$1/$2',   ['filter' => 'role:min:0']);
        $r->delete('(:num)/itens/(:num)',   'Ordem::itensDelete/$1/$2',   ['filter' => 'role:min:0']);
    });

    /**
     * ESTOQUE: gerente/admin (1+)
     * Padrão: /estoque, /estoque/create, etc.
     */
    $routes->group('estoque', static function ($r) {

        // CRUD protegido
        $r->get('/',                    'Estoque::index',           ['filter' => 'role:min:1']);
        $r->get('create',               'Estoque::create',          ['filter' => 'role:min:1']);
        $r->post('',                    'Estoque::store',           ['filter' => 'role:min:1']);
        $r->get('(:num)/edit',          'Estoque::edit/$1',         ['filter' => 'role:min:1']);
        $r->post('(:num)/update',       'Estoque::update/$1',       ['filter' => 'role:min:1']);
        $r->get('(:num)/delete',        'Estoque::delete/$1',       ['filter' => 'role:min:2']);

        $r->post('(:num)/movimentar',   'Estoque::movimentar/$1',   ['filter' => 'role:min:1']);

        // Autocomplete precisa ficar acessível ao módulo de ordens (0+)
        $r->get('autocomplete',         'Estoque::autocomplete',    ['filter' => 'role:min:0']);

        // Relatórios do estoque
        $r->get('relatorios',           'Estoque::relatorios',      ['filter' => 'role:min:1']);
    });

    /**
     * TIPOS DE ESTOQUE
     * /estoque-tipos, /estoque-tipos/create, etc.
     */
    $routes->group('estoque-tipos', static function ($r) {
        $r->get('/',              'EstoqueTipos::index',      ['filter' => 'role:min:1']);
        $r->get('create',         'EstoqueTipos::create',     ['filter' => 'role:min:1']);
        $r->post('',              'EstoqueTipos::store',      ['filter' => 'role:min:1']);
        $r->get('(:num)/edit',    'EstoqueTipos::edit/$1',    ['filter' => 'role:min:1']);
        $r->post('(:num)/update', 'EstoqueTipos::update/$1',  ['filter' => 'role:min:1']);
        $r->get('(:num)/delete',  'EstoqueTipos::delete/$1',  ['filter' => 'role:min:2']);
    });

    // Clientes
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
        $r->get('/',              'Usuarios::index');
        $r->get('create',         'Usuarios::create');
        $r->post('store',         'Usuarios::store');
        $r->get('(:num)/edit',    'Usuarios::edit/$1');
        $r->post('(:num)/update', 'Usuarios::update/$1');
        $r->get('(:num)/delete',  'Usuarios::delete/$1');
    });

    // Relatórios
    $routes->group('relatorios', ['namespace' => 'App\Controllers'], static function ($r) {
        $r->get('/',       'Relatorios::index');
        $r->get('ordens',  'Relatorios::ordens');
    });
});
