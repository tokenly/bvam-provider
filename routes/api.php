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
    Route::match(['GET',  'OPTIONS'], '/bvam/all', 'BvamController@listBvam')
        ->name('bvam.list');

    // all categories
    Route::match(['GET',  'OPTIONS'], '/category/all', 'BvamController@listBvamCategories')
        ->name('category.list');

    // Lookup by asset name
    Route::match(['GET',  'OPTIONS'], '/asset/{asset_name}', 'TokenResourceController@getTokenInfo')
        ->name('tokenresource.get');

    // Lookup multiple assets by name
    Route::match(['GET',  'OPTIONS'], '/assets', 'TokenResourceController@getMultipleTokenInfo')
        ->name('tokenresource.getMultiple');


    // create a new bvam
    Route::match(['POST', 'OPTIONS'], '/bvam', 'BvamController@create')
        ->name('bvam.create');

    // create a new category
    Route::match(['POST', 'OPTIONS'], '/category', 'BvamController@createCategory')
        ->name('bvam.createCategory');
});

// Lookup by hash or signature filename
//   at URL /
Route::match(['GET',  'OPTIONS'], '/{filename}', 'BvamResourceController@getResource')
    ->name('bvamresource.get');


