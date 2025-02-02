<?php

use App\Controllers\Api\Coasters;
use App\Controllers\Api\Wagons;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

/**
 * @uses Coasters::create()
 * @uses Coasters::update()
 */
$routes->resource(
    '/api/coasters',
    [
        'namespace' => '',
        'controller' => Coasters::class,
        'only' => ['create', 'update']
    ]
);

/** @uses Wagons::create() */
$routes->post(
    '/api/coasters/(:segment)/wagons',
    'Api\Wagons::create/$1',
);

/** @uses Wagons::delete() */
$routes->delete(
    '/api/coasters/(:segment)/wagons/(:segment)',
    'Api\Wagons::delete/$1/$2',
);
