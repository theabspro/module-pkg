<?php

Route::group(['namespace' => 'Abs\ModulePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'module-pkg'], function () {
	Route::get('/module/get-status-wise', 'ModuleController@getStatusWiseModules')->name('getStatusWiseModules');
	Route::get('/module/get-user-wise', 'ModuleController@getUserWiseModules')->name('getUserWiseModules');
	Route::get('/module/get-list', 'ModuleController@getModuleList')->name('getModuleList');
	Route::get('/module/get-form-data', 'ModuleController@getModuleFormData')->name('getModuleFormData');
	Route::post('/module/save', 'ModuleController@saveModule')->name('saveModule');
	Route::get('/module/delete/{id}', 'ModuleController@deleteModule')->name('deleteModule');

	Route::get('/module-groups/get-list', 'ModuleGroupController@getModuleGroupList')->name('getModuleGroupList');
	Route::get('/module-group/get-form-data/{id?}', 'ModuleGroupController@getModuleGroupFormData')->name('getModuleGroupFormData');
	Route::post('/module-group/save', 'ModuleGroupController@saveModule')->name('saveModuleGroup');
	Route::get('/module-group/delete/{id}', 'ModuleGroupController@deleteModule')->name('deleteModuleGroup');

	Route::get('/getGanttChartFormData', 'ModuleController@getGanttChartFormData')->name('getGanttChartFormData');
	Route::get('/getGanttChartData', 'ModuleController@getGanttChartData')->name('getGanttChartData');

});