<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/bvam/all', 'BvamController@listBvam')->name('bvam.list');;
Route::get('/category/all', 'BvamController@listBvamCategories')->name('category.list');;

Route::post('/bvam', 'BvamController@create')->name('bvam.create');;
Route::post('/category', 'BvamController@createCategory')->name('bvam.createCategory');;
