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

// ---------------------------------------------------------------------
// Master data (CRUD) — controller enforces view/manage role checks
// ---------------------------------------------------------------------
/**
 * Register the standard CRUD route set for a resource.
 * GET  /res            list
 * GET  /res/export     CSV
 * GET  /res/{id}/edit  fetch one (JSON)
 * POST /res            create
 * PUT  /res/{id}       update
 * DELETE /res/{id}     delete
 */
$crud = function (string $path, string $controller) use ($router): void {
    $router->get("/$path",             "$controller@index",   ['auth']);
    $router->get("/$path/export",      "$controller@export",  ['auth']);
    $router->get("/$path/{id}/edit",   "$controller@find",    ['auth']);
    $router->post("/$path",            "$controller@store",   ['auth']);
    $router->put("/$path/{id}",        "$controller@update",  ['auth']);
    $router->delete("/$path/{id}",     "$controller@destroy", ['auth']);
};

$crud('courses',                 'CourseController');
$crud('divisions',               'DivisionController');
$crud('houses',                  'HouseController');
$crud('course-category-groups',  'CourseCategoryGroupController');
$crud('users',                   'UserController');
$crud('institutions',            'InstitutionController');

// Contestant bulk upload (register BEFORE the generic contestant CRUD so the
// literal /contestants/bulk* paths are not shadowed).
$router->get('/contestants/bulk',            'ContestantBulkController@form',     ['auth']);
$router->get('/contestants/bulk/template',   'ContestantBulkController@template', ['auth']);
$router->post('/contestants/bulk/preview',   'ContestantBulkController@preview',  ['auth']);
$router->post('/contestants/bulk/import',    'ContestantBulkController@import',   ['auth']);

$crud('contestants',             'ContestantController');
