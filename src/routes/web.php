<?php

Route::group(['namespace' => 'Abs\ModulePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'module-pkg'], function () {
	Route::get('/modules/get-list', 'ModuleController@getModuleList')->name('getModuleList');
	Route::get('/module/get-form-data/{id?}', 'ModuleController@getModuleFormData')->name('getModuleFormData');
	Route::post('/module/save', 'ModuleController@saveModule')->name('saveModule');
	Route::get('/module/delete/{id}', 'ModuleController@deleteModule')->name('deleteModule');

});