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

    // all BVAMs
    Route::get('/bvam/all', 'BvamController@listBvam')
        ->name('bvam.list');

    // all categories
    Route::get('/category/all', 'BvamController@listBvamCategories')
        ->name('category.list');

    // Lookup by asset name
    Route::get('/asset/{asset_name}', 'TokenResourceController@getTokenInfo')
        ->name('tokenresource.get');


    // create a new bvam
    Route::post('/bvam', 'BvamController@create')
        ->name('bvam.create');

    // create a new category
    Route::post('/category', 'BvamController@createCategory')
        ->name('bvam.createCategory');

});

// Lookup by hash or signature filename
//   at URL /
Route::get('/{filename}', 'BvamResourceController@getResource')
    ->name('bvamresource.get');


