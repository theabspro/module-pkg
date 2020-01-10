app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
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

// app.config(function($mdDateLocaleProvider) {
//     $mdDateLocaleProvider.parseDate = function(dateString) {
//         var m = moment(dateString, 'DD-MM-YYYY', true);
//         return m.isValid() ? m.toDate() : new Date(NaN);
//     };
// });


app.component('moduleList', {
    templateUrl: module_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var table_scroll;
        table_scroll = $('.page-main-content').height() - 37;
        var dataTable = $('#modules_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                // "search": "",
                // "searchPlaceholder": "Search",
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function(settings) {
                var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                if (state_save_val) {
                    $('#search_module').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            ordering: false,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getModuleList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.module_code = $('#module_code').val();
                    d.module_name = $('#module_name').val();
                    d.mobile_no = $('#mobile_no').val();
                    d.email = $('#email').val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'name', name: 'modules.name' },
                { data: 'assigned_to', name: 'at.name' },
                { data: 'group_name', name: 'mg.name' },
                { data: 'start_date', searchable: false },
                { data: 'end_date', searchable: false },
                { data: 'duration', searchable: false },
                { data: 'completed_percentage', searchable: false },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
                $('#search_module').focus();
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            $('#search_module').val('');
            $('#modules_list').DataTable().search('').draw();
        }

        var dataTables = $('#modules_list').dataTable();
        $("#search_module").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteModule = function($id) {
            $('#module_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#module_id').val();
            $http.get(
                module_delete_data_url + '/' + $id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Module Deleted Successfully',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#modules_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/module-pkg/module/list');
                }
            });
        }

        //FOR FILTER
        $('#module_code').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#module_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#mobile_no').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#email').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.reset_filter = function() {
            $("#module_name").val('');
            $("#module_code").val('');
            $("#mobile_no").val('');
            $("#email").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('moduleForm', {
    templateUrl: module_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        $('.focus').focus();
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? module_get_form_data_url : module_get_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            get_form_data_url
        ).then(function(response) {
            // console.log(response);
            self.module = response.data.module;
            self.extras = response.data.extras;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.module.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
                self.state_list = [{ 'id': '', 'name': 'Select State' }];
            }
        });

        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        $('.datePicker').bootstrapDP({
            format: "dd-mm-yyyy",
            autoclose: "true",
            todayHighlight: true,
        });

        //SELECT STATE BASED COUNTRY
        $scope.onSelectedCountry = function(id) {
            module_get_state_by_country = vendor_get_state_by_country;
            $http.post(
                module_get_state_by_country, { 'country_id': id }
            ).then(function(response) {
                // console.log(response);
                self.state_list = response.data.state_list;
            });
        }

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'duration': {
                    maxlength: 100,
                },
            },
            messages: {
                'name': {
                    maxlength: 'Maximum of 255 charaters',
                },
                'duration': {
                    maxlength: 'Maximum of 100 charaters',
                },
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs'
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 3000)
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveModule'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: res.message,
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 3000);
                            $location.path('/module-pkg/module/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('#submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                $noty = new Noty({
                                    type: 'error',
                                    layout: 'topRight',
                                    text: errors
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 3000);
                            } else {
                                $('#submit').button('reset');
                                $location.path('/module-pkg/module/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        $noty = new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: 'Something went wrong at server',
                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 3000);
                    });
            }
        });
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('projectVersionGanttChartView', {
    templateUrl: project_version_gantt_chart_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        // get_form_data_url = typeof($routeParams.id) == 'undefined' ? module_get_form_data_url : module_get_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getGanttChartData']
        ).then(function(response) {
            self.gantt_chart_data = response.data.gantt_chart_data;
            google.charts.load('current', { 'packages': ['gantt'] });
            google.charts.setOnLoadCallback(self.drawChart);
            $rootScope.loading = false;
        });



        function toMilliseconds(minutes) {
            return minutes * 60 * 1000;
        }

        function hoursToMilliseconds(hours) {
            return hours * 60 * 60 * 1000;
        }

        function daysToMilliseconds(days) {
            return days * 24 * 60 * 60 * 1000;
        }

        self.drawChart = function() {
            var otherData = new google.visualization.DataTable();
            otherData.addColumn('string', 'Task ID');
            otherData.addColumn('string', 'Task Name');
            otherData.addColumn('string', 'Resource');
            otherData.addColumn('date', 'Start');
            otherData.addColumn('date', 'End');
            otherData.addColumn('number', 'Duration');
            otherData.addColumn('number', 'Percent Complete');
            otherData.addColumn('string', 'Dependencies');

            // [
            //                 ['toTrain', 'Walk to train stop', 'walk', null, null, hoursToMilliseconds(8), 100, null],
            //                 ['music', 'Listen to music', 'music', null, null, hoursToMilliseconds(24), 100, null],
            //                 ['wait', 'Wait for train', 'wait', null, null, hoursToMilliseconds(10), 100, 'toTrain'],
            //                 ['train', 'Train ride', 'train', null, null, hoursToMilliseconds(180), 75, 'wait'],
            //                 ['toWork', 'Walk to work', 'walk', null, null, hoursToMilliseconds(10), 0, 'train'],
            //                 ['work', 'Sit down at desk', null, null, null, hoursToMilliseconds(2), 0, 'toWork'],

            //             ]

            otherData.addRows(self.gantt_chart_data);

            var options = {
                height: 1000,
                gantt: {
                    // defaultStartDateMillis: new Date(2015, 3, 28),
                    defaultStartDate: new Date(2015, 3, 28),
                    // defaultStartDate: new Date(),

                    trackHeight: 30,
                    criticalPathEnabled: true,
                    criticalPathStyle: {
                        stroke: '#e64a19',
                        strokeWidth: 5
                    },
                    //     innerGridHorizLine: {
                    //         stroke: '#ffe0b2',
                    //         strokeWidth: 2
                    //     },
                    //     innerGridTrack: { fill: '#fff3e0' },
                    //     innerGridDarkTrack: { fill: '#ffcc80' }
                    // 
                },
            };

            var chart = new google.visualization.Gantt(document.getElementById('chart_div'));

            chart.draw(otherData, options);
        }
    }
});