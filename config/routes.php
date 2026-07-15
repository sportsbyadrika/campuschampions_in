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

// Public results portal (no login): active meets → published prize winners
$router->get('/public-results', 'PublicController@meets');
$router->get('/public-results/{meetId}', 'PublicController@meetResults');

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
$router->get('/contestants/bulk-edit',           'ContestantBulkEditController@form',      ['auth']);
$router->post('/contestants/bulk-edit',          'ContestantBulkEditController@update',    ['auth']);
$router->post('/contestants/bulk-edit/row/{id}', 'ContestantBulkEditController@updateRow', ['auth']);

$crud('contestants',             'ContestantController');
$crud('meets',                   'MeetController');

// ---------------------------------------------------------------------
// Meet bulk import (events + event instances) — campus admin / super admin
// ---------------------------------------------------------------------
$router->get('/meets/{meetId}/bulk',                    'MeetBulkController@form',              ['auth']);
$router->get('/meets/{meetId}/bulk/events-template',    'MeetBulkController@eventsTemplate',    ['auth']);
$router->get('/meets/{meetId}/bulk/instances-template', 'MeetBulkController@instancesTemplate', ['auth']);
$router->post('/meets/{meetId}/bulk/events-preview',    'MeetBulkController@eventsPreview',      ['auth']);
$router->post('/meets/{meetId}/bulk/events-import',     'MeetBulkController@eventsImport',       ['auth']);
$router->post('/meets/{meetId}/bulk/instances-preview', 'MeetBulkController@instancesPreview',   ['auth']);
$router->post('/meets/{meetId}/bulk/instances-import',  'MeetBulkController@instancesImport',    ['auth']);

// ---------------------------------------------------------------------
// Meet setup hub (disciplines, categories, events, instances, points)
// ---------------------------------------------------------------------
$router->get('/meets/{meetId}/setup', 'MeetSetupController@show', ['auth']);
$router->post('/meets/{meetId}/points', 'MeetSetupController@savePoints', ['auth']);
$router->post('/meets/{meetId}/live-settings', 'MeetSetupController@saveLiveSettings', ['auth']);

foreach (['disciplines' => 'Discipline', 'categories' => 'Category', 'events' => 'Event', 'instances' => 'Instance'] as $seg => $suffix) {
    $router->post("/meets/{meetId}/$seg",       "MeetSetupController@store$suffix",  ['auth']);
    $router->put("/meets/{meetId}/$seg/{id}",   "MeetSetupController@update$suffix", ['auth']);
    $router->delete("/meets/{meetId}/$seg/{id}", "MeetSetupController@delete$suffix", ['auth']);
}

// ---------------------------------------------------------------------
// Event instance registrations
// ---------------------------------------------------------------------
$router->get('/instances/{instanceId}/registrations', 'RegistrationController@show', ['auth']);
$router->post('/instances/{instanceId}/registrations', 'RegistrationController@store', ['auth']);
$router->put('/instances/{instanceId}/registrations/{regId}', 'RegistrationController@updateStatus', ['auth']);
$router->delete('/instances/{instanceId}/registrations/{regId}', 'RegistrationController@destroy', ['auth']);

// ---------------------------------------------------------------------
// Results & result entry
// ---------------------------------------------------------------------
$router->get('/results',                        'ResultController@index',      ['auth']);
$router->get('/results/{instanceId}/entry',     'ResultController@entry',      ['auth']);
$router->post('/results/{instanceId}/save',     'ResultController@save',       ['auth']);
$router->post('/results/{instanceId}/publish',  'ResultController@togglePublish', ['auth']);
$router->get('/results/{instanceId}/export',    'ResultController@export',     ['auth']);
$router->get('/results/{instanceId}/assign',    'ResultController@assignForm', ['auth']);
$router->post('/results/{instanceId}/assign',   'ResultController@assign',     ['auth']);
$router->delete('/results/{instanceId}/assign/{assignmentId}', 'ResultController@unassign', ['auth']);

// ---------------------------------------------------------------------
// Championship standings
// ---------------------------------------------------------------------
$router->get('/standings',                'StandingsController@index',  ['auth']);
$router->get('/standings/export/{type}',  'StandingsController@export', ['auth']);
// Live big-screen dashboard (public, so a TV display is not affected by session timeout)
$router->get('/standings/live/{meetId}',      'StandingsController@live');
$router->get('/standings/live-data/{meetId}', 'StandingsController@liveData');

// ---------------------------------------------------------------------
// Certificates
// ---------------------------------------------------------------------
$crud('certificate-templates', 'CertificateTemplateController');

$router->get('/certificates',                        'CertificateController@index',        ['auth']);
$router->get('/certificates/download/{certId}',      'CertificateController@download',      ['auth']);
$router->get('/certificates/{instanceId}/generate',  'CertificateController@generateForm',  ['auth']);
$router->post('/certificates/{instanceId}/generate', 'CertificateController@generate',      ['auth']);

// ---------------------------------------------------------------------
// Audit logs & system reports (Super Admin)
// ---------------------------------------------------------------------
$router->get('/audit-logs',        'AuditLogController@index',  ['role:super_admin']);
$router->get('/audit-logs/export', 'AuditLogController@export', ['role:super_admin']);

// Reports hub + meet reports (all authenticated roles); system report is super-admin only
$router->get('/reports',                        'ReportsController@index',              ['auth']);
$router->get('/reports/system',                 'ReportsController@system',             ['role:super_admin']);
$router->get('/reports/instances-house',        'ReportsController@instancesHouse',     ['auth']);
$router->get('/reports/instances-house/print',  'ReportsController@instancesHousePrint', ['auth']);
$router->get('/reports/instances-house/pdf',    'ReportsController@instancesHousePdf',   ['auth']);
$router->get('/reports/course-house',           'ReportsController@courseHouse',        ['auth']);
$router->get('/reports/course-house/print',     'ReportsController@courseHousePrint',    ['auth']);
$router->get('/reports/course-house/pdf',        'ReportsController@courseHousePdf',      ['auth']);
$router->get('/reports/instance-contestants',   'ReportsController@instanceContestants',['auth']);
$router->get('/reports/instance-contestants/{instanceId}/print', 'ReportsController@instancePrint', ['auth']);
$router->get('/reports/instance-contestants/{instanceId}/pdf',   'ReportsController@instancePdf',   ['auth']);
$router->get('/reports/instance-contestants/{instanceId}/csv',   'ReportsController@instanceCsv',   ['auth']);
$router->get('/reports/class-contestants',        'ReportsController@classContestants', ['auth']);
$router->get('/reports/class-contestants/print',  'ReportsController@classPrint',       ['auth']);
$router->get('/reports/class-contestants/pdf',    'ReportsController@classPdf',         ['auth']);
$router->get('/reports/class-contestants/csv',    'ReportsController@classCsv',         ['auth']);
