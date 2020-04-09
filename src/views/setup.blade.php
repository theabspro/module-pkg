@if(config('module-pkg.DEV'))
    <?php $module_pkg_prefix = '/packages/abs/module-pkg/src';?>
@else
    <?php $module_pkg_prefix = '';?>
@endif

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">

	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
	    when('/module-pkg/module/status-wise', {
	        template: '<status-wise-modules></status-wise-modules>',
	        title: 'Modules - Status Wise',
	    }).
	    when('/module-pkg/module/user-wise', {
	        template: '<user-wise-modules></user-wise-modules>',
	        title: 'Modules - User Wise',
	    }).
	    when('/module-pkg/module/list', {
	        template: '<module-list></module-list>',
	        title: 'Modules',
	    }).
	    when('/module-pkg/module/add', {
	        template: '<module-form></module-form>',
	        title: 'Add Module',
	    }).
	    when('/module-pkg/module/edit/:id', {
	        template: '<module-form></module-form>',
	        title: 'Edit Module',
	    }).

	    when('/project-pkg/project-version/gantt-chart-view', {
	        template: '<project-version-gantt-chart-view></project-version-view-gantt-chart-view>',
	        title: 'View Gantt Chart',
	    })

	    ;
	}]);


    var status_wise_modules_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/status-wise-modules.html')}}";
    var user_wise_modules_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/user-wise-modules.html')}}";
    var module_list_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/list.html')}}";
    var module_form_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/form.html')}}";
    var project_version_gantt_chart_view_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/gantt-chart.html')}}";
</script>
<script type="text/javascript" src="{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/controller.js?v=2')}}"></script>


