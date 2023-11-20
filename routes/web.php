<?php

/** @var \Laravel\Lumen\Routing\Router $router */
use App\Http\Controllers\AdvertController;
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

$router->get('/', function () use ($router) {
    return $router->app->version();
});


$router->group(['prefix' => 'api/v1'], function () use ($router) {
    $router->post('/createAds', 'AdvertController@store');
    $router->get('/filterPlatform', 'AdvertController@index');
    $router->get('/filterSection', 'AdvertController@filter');
    $router->get('/filterPage', 'AdvertController@filterPage');
    $router->get('/filterPlatform', 'AdvertController@filterPlatform');
    $router->get('/all', 'AdvertController@index');
    $router->delete('/deleteAds', 'AdvertController@destroy');

    //Auth routes
    $router->post('login', 'AuthController@login');
    $router->post('logout', 'AuthController@logout');
    $router->post('refresh', 'AuthController@refresh');
    $router->post('user-profile', 'AuthController@me');
    $router->post('/register', 'AuthController@register');


    //News routes
    $router->get('getAllNews', 'NewsController@getAllNews');
    $router->get('getTransferNews', 'NewsController@getTransferNews');
    $router->get('getChampionsLeague', 'NewsController@getChampionsLeague');
    $router->get('getNews', 'NewsController@getNews');
    $router->get('getNewsByID', 'NewsController@getNewsByID');
    $router->get('getFootballNews', 'NewsController@getFootballNews');
    $router->get('getTopStories', 'NewsController@getTopStories');
    $router->get('thisDayLastYear', 'NewsController@thisDayLastYear');
    $router->post('createNews', 'NewsController@createNews');
    $router->delete('deleteNews', 'NewsController@deleteNews');
    $router->post('/updateNews/{uri}', 'NewsController@updateNews');
    $router->get('/getNewsByTag', 'NewsController@filterNews');

    // middleware for routes
    $router->group(['middleware' => 'auth:api'], function () use ($router) {
        $router->get('user', 'AuthController@me');
        $router->post('/updateAds/{id}', 'AdvertController@update');
    });
});

