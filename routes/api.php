<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API methods
//   at URL /api/v1
Route::group(['prefix' => 'api/v1'], function() {

    Route::get('/bvam/all', 'BvamController@listBvam')
        ->name('bvam.list');

    Route::get('/category/all', 'BvamController@listBvamCategories')
        ->name('category.list');


    Route::post('/bvam', 'BvamController@create')
        ->name('bvam.create');

    Route::post('/category', 'BvamController@createCategory')
        ->name('bvam.createCategory');

});

// Lookup by hash or signature filename
//   at URL /
Route::get('/{filename}', 'BvamResourceController@getResource')
    ->name('bvamresource.get');

