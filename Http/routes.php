<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Modules\Dashboard\Http\Controllers', 'middleware' => ['web', 'auth'], 'prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
    Route::get('/', 'DashboardController@index')->name('index');
    Route::post('/', 'DashboardController@store')->name('store');
    Route::put('/{id}/size', 'DashboardController@updateSize')->name('size.update');
    Route::post('/reorder', 'DashboardController@reorder')->name('reorder');
    Route::delete('/{id}', 'DashboardController@destroy')->name('destroy');
});
