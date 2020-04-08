@if(config('module-pkg.DEV'))
    <?php $module_pkg_prefix = '/packages/abs/module-pkg/src';?>
@else
    <?php $module_pkg_prefix = '';?>
@endif

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
    // var module_list_template_url = "{{URL::asset($module_pkg_prefix.'/public/angular/module-pkg/pages/module/list.html')}}";
    // var module_get_form_data_url = "{{url('module-pkg/module/get-form-data/')}}";
    // var module_form_template_url = "{{URL::asset($module_pkg_prefix.'/public/angular/module-pkg/pages/module/form.html')}}";
    // var module_delete_data_url = "{{url('module-pkg/module/delete/')}}";

    // var project_version_gantt_chart_view_template_url = "{{URL::asset($module_pkg_prefix.'/public/angular/module-pkg/pages/module/gantt-chart.html')}}";
// var theme_md = "{{$module_pkg_prefix}}"; alert(theme_md);
    var module_list_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/module-pkg/uitoux-theme2/module/list.html')}}";
    var module_get_form_data_url = "{{url('module-pkg/module/get-form-data/')}}";
    var module_form_template_url = "{{URL::asset($module_pkg_prefix.'/public/themes/module-pkg/uitoux-theme2/module/form.html')}}";
    var module_delete_data_url = "{{url('module-pkg/module/delete/')}}";

    var project_version_gantt_chart_view_template_url = "{{URL::asset($module_pkg_prefix.'/public/angular/module-pkg/pages/module/gantt-chart.html')}}";
</script>
<script type="text/javascript" src="{{URL::asset($module_pkg_prefix.'/public/themes/module-pkg/uitoux-theme2/module/controller.js?v=2')}}"></script>


