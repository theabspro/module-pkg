<?php

Route::group(['namespace' => 'Abs\ModulePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'module-pkg'], function () {
	Route::get('/module/get-status-wise', 'ModuleController@getStatusWiseModules')->name('getStatusWiseModules');
	Route::get('/module/get-user-wise', 'ModuleController@getUserWiseModules')->name('getUserWiseModules');
	Route::get('/module/get-list', 'ModuleController@getModuleList')->name('getModuleList');
	Route::get('/module/get-form-data', 'ModuleController@getModuleFormData')->name('getModuleFormData');
	Route::post('/module/save', 'ModuleController@saveModule')->name('saveModule');
	Route::post('/module/update-priority', 'ModuleController@updateModulePriority')->name('updateModulePriority');
	// Route::get('/module/delete/{id}', 'ModuleController@deleteModule')->name('deleteModule');
	Route::get('/module/delete/', 'ModuleController@deleteModule')->name('deleteModule');

	Route::post('project-version-module/get', 'ModuleController@getProjectVersionModules')->name('getProjectVersionModules');
	Route::get('/module/get-filter-data', 'ModuleController@getModuleFilterData')->name('getModuleFilterData');

	Route::get('/module-groups/get-list', 'ModuleGroupController@getModuleGroupList')->name('getModuleGroupList');
	Route::get('/module-group/get-form-data/', 'ModuleGroupController@getModuleGroupFormData')->name('getModuleGroupFormData');
	Route::post('/module-group/save', 'ModuleGroupController@saveModuleGroup')->name('saveModuleGroup');
	Route::get('/module-group/delete/', 'ModuleGroupController@deleteModuleGroup')->name('deleteModuleGroup');

	Route::get('/getGanttChartFormData', 'ModuleController@getGanttChartFormData')->name('getGanttChartFormData');
	Route::get('/getGanttChartData', 'ModuleController@getGanttChartData')->name('getGanttChartData');

});