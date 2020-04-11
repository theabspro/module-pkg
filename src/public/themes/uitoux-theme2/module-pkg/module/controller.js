app.component('statusWiseModules', {
    templateUrl: status_wise_modules_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            laravel_routes['getStatusWiseModules'],
        ).then(function(response) {
            if (!response.data.success) {
                showErrorNoty(response)
                return;
            }

            console.log(response.data);
            self.statuses = response.data.statuses;
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
        $('#module_code').on('keyup', function() {});
        $('#module_name').on('keyup', function() {});
        $('#mobile_no').on('keyup', function() {});
        $('#email').on('keyup', function() {});
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
app.component('userWiseModules', {
    templateUrl: user_wise_modules_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        $http.get(
            laravel_routes['getUserWiseModules'],
        ).then(function(response) {
            if (!response.data.success) {
                showErrorNoty(response)
                return;
            }

            console.log(response.data);
            self.users = response.data.users;
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
        $('#module_code').on('keyup', function() {});
        $('#module_name').on('keyup', function() {});
        $('#mobile_no').on('keyup', function() {});
        $('#email').on('keyup', function() {});
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

app.component('moduleList', {
    templateUrl: module_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http.get(
            laravel_routes['getModuleFilterData']
        ).then(function(response) {
            self.filter = response.data.filter;
            console.log(self.filter);
            $rootScope.loading = false;
        });

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
                    d.module_name = $('#module_name').val();
                    d.project_id = $('#project_id').val();
                    d.project_version_id = $('#project_version_id').val();
                    d.assigned_to_id = $('#assigned_to_id').val();
                    d.tester_id = $('#tester_id').val();
                    d.status_id = $('#status_id').val();
                    d.platform_id = $('#platform_id').val();
                    d.start_date = $("#start_date").val();
                    d.end_date = $("#end_date").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'project_name', name: 'p.name' },
                { data: 'project_version_number', name: 'pv.number' },
                { data: 'name', name: 'modules.name' },
                { data: 'status_name', name: 'statuses.name' },
                { data: 'priority', name: 'modules.priority' },
                { data: 'assigned_to', name: 'at.first_name' },
                { data: 'tester_name', name: 'tester.first_name' },
                { data: 'duration', searchable: false },
                { data: 'completed_percentage', searchable: false },
                { data: 'group_name', name: 'mg.name' },
                { data: 'dependancy_count', searchable: false },
                { data: 'start_date', searchable: false },
                { data: 'end_date', searchable: false },

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
        $('#module_name').on('keyup', function() {
            dataTables.fnFilter();
        });

        $scope.onselectProject = function(id) {
            $('#project_id').val(id);
            $scope.projectFilterChanged(id);
            dataTables.fnFilter();
        }
        $scope.onselectProjectVersion = function(id) {
            $('#project_version_id').val(id);
            dataTables.fnFilter();
        }
        $scope.onselectAssignedTo = function(id) {
            $('#assigned_to_id').val(id);
            dataTables.fnFilter();
        }
        $scope.onselectTester = function(id) {
            $('#tester_id').val(id);
            dataTables.fnFilter();
        }
        $scope.onselectStatus = function(id) {
            $('#status_id').val(id);
            dataTables.fnFilter();
        }
        $scope.onselectPlatform = function(id) {
            $('#platform_id').val(id);
            dataTables.fnFilter();
        }
        $scope.reset_filter = function() {
            // alert();
            $("#module_name").val('');
            $("#project_id").val('');
            $("#project_version_id").val('');
            $("#assigned_to_id").val('');
            $("#tester_id").val('');
            $("#status_id").val('');
            $("#platform_id").val('');
            $("#start_date").val('')
            $("#end_date").val('')
            dataTables.fnFilter();
        }
        $scope.projectFilterChanged = function(id) {
            //alert(id);
            $http.post(
                laravel_routes['getProjectVersions'], {
                    'project_id': id
                }
            ).then(function(response) {
                //alert();
                self.filter.project_version_list = [];
                console.log('empty ======' + self.filter.project_version_list);
                self.module.project_version_id = '';
                self.filter.project_version_list = response.data.project_versions;
                console.log('after ======' + self.filter.project_version_list);
            });

        }
        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            // dataTables.fnFilter();
        });
        $('body').on('click', '.applyBtn', function() { //alert('sd');
            setTimeout(function() {
                dataTables.fnFilter();
            }, 900);
        });
        $('body').on('click', '.cancelBtn', function() {
            //alert('sd');
            setTimeout(function() {
                dataTables.fnFilter();
            }, 900);
        });

        $('.align-left.daterange').daterangepicker({
            autoUpdateInput: false,
            "opens": "left",
            locale: {
                cancelLabel: 'Clear',
                format: "DD-MM-YYYY"
            }
        });

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('moduleForm', {
    templateUrl: module_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        $('.focus').focus();
        // get_form_data_url = typeof($routeParams.id) == 'undefined' ? module_get_form_data_url : module_get_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getModuleFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            console.log(response.data);
            //return false;
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
                self.extras.module_list = [];
                self.extras.project_version_list = [];

                self.switch_value = 'Active';
                self.state_list = [{ 'id': '', 'name': 'Select State' }];
            }
        });

        self.depended_module_ids = [];
        $scope.selectAllFieldGroups = function() {
            self.all_depended_module_ids = [];
            angular.forEach(self.extras.module_list, function(value, key) {
                console.log("value====" + value.id);
                self.all_depended_module_ids.push(value.id);
            });
            console.log(self.extras.module_list);
            self.module.depended_module_ids = self.all_depended_module_ids;
        };
        $scope.deselectAllFieldGroups = function() {
            self.module.depended_module_ids = [];
        };
        $scope.projectChanged = function() {
            $http.post(
                laravel_routes['getProjectVersions'], {
                    'project_id': self.module.project.id
                }
            ).then(function(response) {
                // alert();
                self.extras.project_version_list = [];
                //self.module.project_version = '';
                //self.module.depended_module_ids = '';
                //self.extras.module_list = [];
                self.extras.project_version_list = response.data.project_versions;
            });

        }

        $scope.versionChanged = function() {
            console.log(self.module.project_version.id);
            $http.post(
                laravel_routes['getProjectVersionModules'], {
                    'project_version_id': self.module.project_version.id,
                    'module_id': self.module.id
                }
            ).then(function(response) {
                //self.extras.module_list = [];
                //self.module.depended_module_ids = '';
                self.extras.module_list = response.data.module_list;
            });

        }
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
                'project_id': {
                    required: true,
                },
                'project_version_id': {
                    required: true,
                },
                'name': {
                    required: true,
                    maxlength: 191,
                },
                'duration': {
                    required: true,
                    number: true,
                },

                'priority': {
                    required: true,
                    number: true,
                },
                'start_date': {
                    required: true,
                    //number: true,
                },
                'completed_percentage': {
                    required: true,
                    number: true,
                },
            },
            messages: {
                'name': {
                    maxlength: 'Maximum of 191 charaters',
                },
                'duration': {
                    maxlength: 'Maximum of 100 charaters',
                },
            },
            /*invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs'
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 3000)
            },*/
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
                            custom_noty('success', res.message);
                            $location.path('/module-pkg/module/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('#submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);

                            } else {
                                $('#submit').button('reset');
                                $location.path('/module-pkg/module/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
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

        $rootScope.loading = true;
        $http.get(
            laravel_routes['getGanttChartFormData']
        ).then(function(response) {
            if (!response.data.success) {
                showErrorNoty(response.data);
                return;
            }
            self.extras = response.data.extras;
            $rootScope.loading = false;
        });


        self.drawChart = function() {
            $rootScope.loading = true;
            $http.get(
                laravel_routes['getGanttChartData'], {
                    params: {
                        'filtered_module_ids[]': self.filtered_module_ids
                    },
                }
            ).then(function(response) {
                if (!response.data.success) {
                    showErrorNoty(response.data);
                    return;
                }
                self.gantt_chart_data = response.data.gantt_chart_data;

                var otherData = new google.visualization.DataTable();
                otherData.addColumn('string', 'Task ID');
                otherData.addColumn('string', 'Task Name');
                otherData.addColumn('string', 'Resource');
                otherData.addColumn('date', 'Start');
                otherData.addColumn('date', 'End');
                otherData.addColumn('number', 'Duration');
                otherData.addColumn('number', 'Percent Complete');
                otherData.addColumn('string', 'Dependencies');

                var data = [];

                console.log(self.gantt_chart_data)
                for (var i in self.gantt_chart_data) {
                    data.push(self.gantt_chart_data[i]);
                }
                otherData.addRows(data);
                console.log(data.length)
                var options = {
                    height: (data.length * 50) + 200,
                    gantt: {
                        // defaultStartDateMillis: new Date(2015, 3, 28),
                        defaultStartDate: new Date(2015, 3, 28),
                        // defaultStartDate: new Date(),

                        trackHeight: 50,
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
                $rootScope.loading = false;
            });
        }

        self.filtered_module_ids = [];
        $scope.selectAllModules = function() {
            self.filtered_module_ids = self.extras.all_module_ids;
        };

        $scope.deselectAllModules = function() {
            self.filtered_module_ids = [];
        };

        google.charts.load('current', { 'packages': ['gantt'] });
        google.charts.setOnLoadCallback(self.drawChart);

    }
});
