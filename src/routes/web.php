<?php

Route::group(['namespace' => 'Abs\ModulePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'module-pkg'], function () {
	Route::get('/modules/get-list', 'ModuleController@getModuleList')->name('getModuleList');
	Route::get('/module/get-form-data/{id?}', 'ModuleController@getModuleFormData')->name('getModuleFormData');
	Route::post('/module/save', 'ModuleController@saveModule')->name('saveModule');
	Route::get('/module/delete/{id}', 'ModuleController@deleteModule')->name('deleteModule');

	Route::get('/module-groups/get-list', 'ModuleGroupController@getModuleGroupList')->name('getModuleGroupList');
	Route::get('/module-group/get-form-data/{id?}', 'ModuleGroupController@getModuleGroupFormData')->name('getModuleGroupFormData');
	Route::post('/module-group/save', 'ModuleGroupController@saveModule')->name('saveModuleGroup');
	Route::get('/module-group/delete/{id}', 'ModuleGroupController@deleteModule')->name('deleteModuleGroup');

});