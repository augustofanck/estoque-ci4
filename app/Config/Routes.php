<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Dashboard::index');
$routes->get('ordens', 'Ordem::index');
$routes->get('ordens/create', 'Ordem::create');
$routes->post('ordens', 'Ordem::store');
$routes->get('ordens/(:num)/edit', 'Ordem::edit/$1');
$routes->post('ordens/(:num)/update', 'Ordem::update/$1');
$routes->get('ordens/(:num)/delete', 'Ordem::delete/$1');
