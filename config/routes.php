<?php
/**
 * Application routes.
 *
 * @var \App\Core\Router $router  (provided by App::loadRoutes)
 *
 * Middleware directives: 'auth', 'guest', 'role:super_admin,campus_admin', ...
 */

declare(strict_types=1);

// ---------------------------------------------------------------------
// Public / guest
// ---------------------------------------------------------------------
$router->get('/', 'HomeController@index');

$router->get('/login',  'AuthController@showLogin',  ['guest']);
$router->post('/login', 'AuthController@login',      ['guest']);
$router->post('/logout','AuthController@logout',     ['auth']);

$router->get('/forgot-password',  'AuthController@showForgot', ['guest']);
$router->post('/forgot-password', 'AuthController@sendReset',  ['guest']);
$router->get('/reset-password/{token}',  'AuthController@showReset',  ['guest']);
$router->post('/reset-password',         'AuthController@resetPassword', ['guest']);

// Public results (no login)
$router->get('/public-results', 'PublicController@results');

// ---------------------------------------------------------------------
// Authenticated
// ---------------------------------------------------------------------
$router->get('/dashboard', 'DashboardController@index', ['auth']);

$router->get('/profile',          'ProfileController@show',           ['auth']);
$router->post('/profile',         'ProfileController@update',         ['auth']);
$router->get('/change-password',  'ProfileController@showPassword',   ['auth']);
$router->post('/change-password', 'ProfileController@updatePassword', ['auth']);
