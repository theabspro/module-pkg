@if(config('module-pkg.DEV'))
    <?php $module_pkg_prefix = '/packages/abs/module-pkg/src';?>
@else
    <?php $module_pkg_prefix = '';?>
@endif

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
    var module_list_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/list.html')}}";
    var module_form_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/form.html')}}";
    var project_version_gantt_chart_view_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/gantt-chart.html')}}";
</script>
<script type="text/javascript" src="{{URL::asset($module_pkg_prefix.'/public/themes/'.$theme.'/module-pkg/module/controller.js?v=2')}}"></script>


