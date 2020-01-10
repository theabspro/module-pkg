<?php
Route::group(['namespace' => 'Abs\ModulePkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'module-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});