<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$api = app('Dingo\Api\Routing\Router');
/*
$api->version('v1', function (\Dingo\Api\Routing\Router $api) {

    $api->post('/upload', App\Http\Controllers\V1\FileController::class . '@upload');

    $api->get('/file/{hash}', App\Http\Controllers\V1\StorageController::class . '@download');
});
*/

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['namespace' => 'App\Http\Controllers\V1'], function (\Dingo\Api\Routing\Router $api) {
	$api->post('upload', [
        'as' => 'file.store',
        'uses' => 'FileController@upload',
    ]);

    $api->get('file/{hash}', [
        'as' => 'file.download',
        'uses' => 'FileController@download',
    ]);
});
