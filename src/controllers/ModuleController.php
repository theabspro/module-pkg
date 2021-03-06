<?php

namespace Abs\ModulePkg;
use Abs\BasicPkg\Address;
use Abs\ModulePkg\ModuleGroup;
use App\Config;
use App\Http\Controllers\Controller;
use App\Module;
use App\Platform;
use App\Project;
use App\ProjectVersion;
use App\Status;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ModuleController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getStatusWiseModules(Request $request) {
		$statuses = Status::with([
			'modules' => function ($q) use ($request) {
				if ($request->project_version_id) {
					$query->where('modules.project_version_id', $request->project_version_id)->keyBy('id');
				}
			},
			'modules.assignedTo',
		])
			->where('type_id', 161)
			->where(function ($query) use ($request) {
				if ($request->status_ids) {
					$query->whereIn('statuses.id', $request->status_ids);
				}
			})
			->get()
			->keyBy('id')
		;
		return response()->json([
			'success' => true,
			'statuses' => $statuses,
		]);
	}

	public function getUserWiseModules(Request $request) {
		$users = User::with([
			'modules' => function ($q) use ($request) {
				if ($request->project_version_id) {
					$query->where('modules.project_version_id', $request->project_version_id)->keyBy('id');
				}
			},
			'modules.assignedTo',
		])
			->where(function ($query) use ($request) {
				if ($request->user_ids) {
					$query->whereIn('users.id', $request->user_ids);
				}
			})
			->get()
			->keyBy('id')
		;
		return response()->json([
			'success' => true,
			'users' => $users,
		]);
	}

	public function getModuleList(Request $request) {
		//dd($request->all());
		if (!empty($request->start_date)) {
			$start_date = explode('to', $request->start_date);
			$start_from_date = date('Y-m-d', strtotime($start_date[0]));
			$start_to_date = date('Y-m-d', strtotime($start_date[1]));
		} else {
			$start_from_date = '';
			$start_to_date = '';
		}
		if (!empty($request->end_date)) {
			$end_date = explode('to', $request->end_date);
			$end_from_date = date('Y-m-d', strtotime($end_date[0]));
			$end_to_date = date('Y-m-d', strtotime($end_date[1]));
		} else {
			$end_from_date = '';
			$end_to_date = '';
		}
		if (!empty($request->status_id)) {
			$status_ids = explode(',', $request->status_id);
		} else {
			$status_ids = '';
		}
		if (!empty($request->assigned_to_id)) {
			$assigned_to_ids = explode(',', $request->assigned_to_id);
		} else {
			$assigned_to_ids = '';
		}
		if (!empty($request->platform_id)) {
			$platform_ids = explode(',', $request->platform_id);
		} else {
			$platform_ids = '';
		}

		//dd($status_ids);
		$modules = Module::withTrashed()
			->join('project_versions as pv', 'modules.project_version_id', 'pv.id')
			->join('projects as p', 'pv.project_id', 'p.id')
			->leftJoin('statuses', 'statuses.id', 'modules.status_id')
			->leftJoin('module_groups as mg', 'modules.group_id', 'mg.id')
			->leftJoin('users as at', 'modules.assigned_to_id', 'at.id')
			->leftJoin('users as tester', 'modules.tester_id', 'tester.id')
			->leftJoin('module_parent_module as mpm', 'modules.id', 'mpm.module_id')
			->select([
				'modules.id',
				DB::raw('CONCAT(p.short_name," / ",p.code) as project_name'),
				'pv.number as project_version_number',
				DB::raw('CONCAT(at.first_name," ",at.last_name) as assigned_to'),
				DB::raw('CONCAT(tester.first_name," ",tester.last_name) as tester_name'),
				DB::raw('COUNT(mpm.parent_module_id) as dependancy_count'),
				'modules.name',
				'mg.name as group_name',
				'statuses.name as status_name',
				'modules.priority',
				DB::raw('date_format(modules.start_date,"%d-%m-%Y") as start_date'),
				DB::raw('date_format(modules.end_date,"%d-%m-%Y") as end_date'),
				'modules.duration as duration',
				'modules.completed_percentage',
				DB::raw('IF(modules.deleted_at IS NULL,"Active","Inactive") as status'),
			])
			->where('pv.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->module_name)) {
					$query->where('modules.name', 'LIKE', '%' . $request->module_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->project_id)) {
					$query->where('p.id', $request->project_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->project_version_id)) {
					$query->where('modules.project_version_id', $request->project_version_id);
				}
			})
			->where(function ($query) use ($assigned_to_ids) {
				if (!empty($assigned_to_ids)) {
					$query->whereIn('modules.assigned_to_id', $assigned_to_ids);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->tester_id)) {
					$query->where('modules.tester_id', $request->tester_id);
				}
			})
			->where(function ($query) use ($status_ids) {
				if (!empty($status_ids)) {
					$query->whereIn('modules.status_id', $status_ids);
				}
			})
			->where(function ($query) use ($platform_ids) {
				if (!empty($platform_ids)) {
					$query->whereIn('modules.platform_id', $platform_ids);
				}
			})
			->where(function ($query) use ($start_from_date, $start_to_date) {
				if (!empty($start_from_date) && !empty($start_to_date)) {
					$query->whereRaw("DATE(modules.start_date) BETWEEN '" . $start_from_date . "' AND '" . $start_to_date . "'");
				}
			})
			->where(function ($query) use ($end_from_date, $end_to_date) {
				if (!empty($end_from_date) && !empty($end_to_date)) {
					$query->whereRaw("DATE(modules.end_date) BETWEEN '" . $end_from_date . "' AND '" . $end_to_date . "'");
				}
			})
			->groupBy('modules.id')
			->orderBy('at.id', 'asc')
			->orderBy('modules.duration', 'asc')
		;

		return Datatables::of($modules)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($module) {
				$status = $module->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $module->name;
			})
			->addColumn('action', function ($modules) {
				$edit_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');;
				$delete_img = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				return '
					<a href="#!/module-pkg/module/edit/' . $modules->id . '">
						<img src="' . $edit_img . '" alt="edit" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_module"
					onclick="angular.element(this).scope().deleteModule(' . $modules->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getModuleFilterData() {
		$this->data['filter']['project_list'] = Collect(
			Project::select([
				'id',
				DB::raw('CONCAT(code," / ",short_name) as name'),
			])
				->get())->prepend(['name' => 'Select Project'])
		;
		$this->data['filter']['project_version_list'] = Collect(
			ProjectVersion::select([
				'id',
				'number as name',
			])
				->get())->prepend(['name' => 'Select Project Version']);
		$this->data['filter']['module_list'] = Collect(
			Module::select('id', 'name', 'code')
				->orderBy('modules.code')
				->get())
		;

		$this->data['filter']['status_list'] = Collect(
			Status::select([
				'id',
				'name',
			])
				->where('type_id', 161)
			//->company()
				->get())->prepend(['name' => 'Select Status'])
		;
		$this->data['filter']['platform_list'] = Collect(
			Config::select([
				'id',
				'name',
			])
				->where('config_type_id', 50)
				->get())->prepend(['name' => 'Select Platform'])
		;
		$this->data['filter']['user_list'] = Collect(
			User::select([
				'id',
				DB::raw('CONCAT(first_name," ",last_name) as name'),
				'email',
			])
				->where('user_type_id', 1)
				->get())->prepend(['name' => 'Select Assigned To'])
		;
		$this->data['filter']['tester_list'] = Collect(
			User::select([
				'id',
				DB::raw('CONCAT(first_name," ",last_name) as name'),
				'email',
			])
				->where('user_type_id', 1)
				->get())->prepend(['name' => 'Select Tester'])
		;
		$this->data['filter']['group_list'] = Collect(
			ModuleGroup::where('company_id', Auth::user()->company_id)->select('id', 'name')->orderBy('name')
				->get())->prepend(['name' => 'Select Group'])
		;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function getModuleFormData(Request $r) {
		$id = $r->id;
		if (!$id) {
			$module = new Module;
			$module->priority = 1;
			$module->completed_percentage = 0;
			$action = 'Add';
			$this->data['extras']['project_version_list'] = [];
		} else {
			$module = Module::with([
				'assignedTo',
				'projectVersion',
				'ProjectVersion.project',
			])->withTrashed()->find($id);
			$module->start_date = $module->start_date;
			$module->end_date = $module->end_date;
			$module->project = $module->projectVersion->project;
			$module->depended_module_ids = $module->parentModules()->pluck('id')->toArray();
			$action = 'Edit';
			$this->data['extras']['project_version_list'] = Collect(
				ProjectVersion::select([
					'id',
					'number',
				])
					->get());
		}
		$this->data['module'] = $module;
		/*$this->data['extras']['module_list'] = Collect(
			Module::select('id', 'name', 'code')
				->where('id', '!=', $module->id)
				->orderBy('modules.code')
				->get())
		;*/
		$this->data['extras']['project_list'] = Collect(
			Project::select([
				'id',
				'code',
				'short_name',
			])
				->get())->prepend(['code' => 'Select Project'])
		;
		$this->data['extras']['status_list'] = Collect(
			Status::select([
				'id',
				'name',
			])
				->where('type_id', 161)
			//->company()
				->get())->prepend(['name' => 'Select Status'])
		;
		// $this->data['extras']['platform_list'] = Collect(
		// 	Config::select([
		// 		'id',
		// 		'name',
		// 	])
		// 		->where('config_type_id', 50)
		// 		->get())->prepend(['name' => 'Select Platform'])
		// ;
		$this->data['extras']['platform_list'] = Collect(
			Platform::select([
				'id',
				'name',
			])
				->where('company_id', 1)
				->get())->prepend(['name' => 'Select Platform'])
		;
		$this->data['extras']['user_list'] = Collect(
			User::select([
				'id',
				DB::raw('CONCAT(first_name," ",last_name) as name'),
				'email',
			])
				->where('user_type_id', 1)
				->get())->prepend(['name' => 'Select Assigned To'])
		;
		$this->data['extras']['group_list'] = Collect(
			ModuleGroup::where('company_id', Auth::user()->company_id)->select('id', 'name')->orderBy('name')
				->get())->prepend(['name' => 'Select Group'])
		;
		// $this->data['extras']['git_branches'] = Collect(
		// 	GitBranch::where('project_id', )->select('id', 'name')->orderBy('name')
		// 		->get())->prepend(['name' => 'Select Group'])
		// ;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveModule(Request $request) {
		//dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Module Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				// 'project_version_id.required' => 'project version is Required',
				'completed_percentage.required' => 'Completed Percentage is Required',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:modules,name,' . $request->id . ',id,project_version_id,' . $request->project_version_id,
				],
				/*'code' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:modules,name,' . $request->id . ',id,project_version_id,' . $request->project_version_id,
				],*/
				'project_version_id' => [
					// 'required:true',
					'exists:project_versions,id',
				],
				'group_id' => [
					'nullable',
					'exists:module_groups,id',
				],
				// 'platform_id' => [
				// 	'nullable',
				// 	'exists:configs,id',
				// ],
				'platform_id' => [
					'nullable',
					'exists:platforms,id',
				],
				'status_id' => [
					'nullable',
					'exists:statuses,id',
				],
				'assigned_to_id' => [
					'nullable',
					'exists:users,id',
				],
				'priority' => [
					'nullable',
					'numeric',
				],
				'tester_id' => [
					'nullable',
					'exists:users,id',
				],
				'completed_percentage' => 'required',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$module = new Module;
				$module->created_by_id = Auth::user()->id;
				$module->code = 'temp';
			} else {
				$module = Module::withTrashed()->find($request->id);
				$module->updated_by_id = Auth::user()->id;
				$module->updated_at = Carbon::now();
			}
			$module->fill($request->all());
			$module->priority = $request->priority ? $request->priority : 999;
			if ($request->status == 'Inactive') {
				$module->deleted_at = Carbon::now();
				$module->deleted_by_id = Auth::user()->id;
			} else {
				$module->deleted_by_id = NULL;
				$module->deleted_at = NULL;
			}
			$module->save();

			if (!$request->id) {
				$module->code = 'MOD' . $module->id;
				$module->save();
			}
			$module->parentModules()->sync(json_decode($request->depended_module_ids));

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Module Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Module Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getProjectVersionModules(Request $r) {
		//dd($r->all());
		$this->data['success'] = true;
		if (isset($r->module_id)) {
			$this->data['module_list'] = collect(Module::
					where('project_version_id', $r->project_version_id)
					->where('id', '!=', $r->module_id)
					->select('id', DB::raw('CONCAT(code,"/",name) as name'))
					->get())
				->prepend(['id' => '', 'name' => 'Select Version Module']);
		} else {
			$this->data['module_list'] = collect(Module::
					where('project_version_id', $r->project_version_id)
					->select('id', DB::raw('CONCAT(code,"/",name) as name'))
					->get())
				->prepend(['id' => '', 'name' => 'Select Version Module']);
		}
		return response()->json($this->data);
	}

	public function deleteModule(Request $r) {
		// dd($r->id);
		DB::beginTransaction();
		try {
			$delete_module = Module::where('id', $r->id)->delete();
			DB::commit();
			if ($delete_module) {
				$address_delete = Address::where('address_of_id', 24)->where('entity_id', $r->id)->forceDelete();
				return response()->json(['success' => true]);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getGanttChartFormData() {
		$modules = Module::
			leftJoin('module_parent_module as mdm', 'modules.id', 'mdm.parent_module_id')
			->leftJoin('modules as dm', 'mdm.parent_module_id', 'dm.id')
			->groupBy('modules.id')
			->select([
				'modules.id',
				'modules.code',
				'modules.name',
				DB::raw('COUNT(dm.id) as depended_count'),
			])
		// ->orderBy('modules.assigned_to_id')
			->orderBy('depended_count')
		// ->orderBy('modules.duration')
			->get();

		$extras = [
			'module_list' => $modules,
			'all_module_ids' => Module::pluck('id')->toArray(),

		];

		return response()->json([
			'success' => true,
			'extras' => $extras,
		]);
	}

	public function getGanttChartData(Request $r) {
		$modules = Module::from('modules')
		// leftJoin('module_dependency_module as mdm', 'modules.id', 'mdm.module_id')
		// ->leftJoin('modules as dm', 'mdm.parent_module_id', 'dm.id')
		// ->select([
		// 	'modules.*',
		// 	DB::raw('COUNT(dm.id) as depended_count'),
		// 	DB::raw('GROUP_CONCAT(dm.code) as dependencies'),
		// ])
		// ->groupBy('modules.id')
		// ->orderBy('assigned_to_id')
		;
		if ($r->filtered_module_ids) {
			$modules->where(function ($q) use ($r) {
				$q->whereIn('modules.id', $r->filtered_module_ids);
				// 	$q->orWhereRaw('modules.id IN (
				// 		select
				// 			mdm.parent_module_id
				// 		from
				// 			modules as sm
				// 		left join
				// 			module_dependency_module as mdm on mdm.module_id = sm.id
				// 		where
				// 			mdm.module_id = modules.id
				// 			)
				// 		'
				// 	);
				// 	// $q->orWhereIn('dm.id', $r->filtered_module_ids);
			});
		}
		$modules = $modules->get();
		$data = [];

		foreach ($modules as $module) {
			foreach ($module->subModules as $sub_module) {
				$sub_module->getGanttChartData($data);
			}
			// foreach ($module->parentModules as $sub_module) {
			// 	$module->getGanttChartData($data);
			// }
		}

		// foreach ($modules as $module) {
		// 	$module->getGanttChartData($data);
		// }

		return response()->json([
			'success' => true,
			'gantt_chart_data' => $data,
			'filtered_module_ids' => $r->filtered_module_ids,
		]);
	}

	public function updateModulePriority(Request $r) {
		$module = Module::find($r->id);
		if (!$module) {
			return response()->json([
				'success' => false,
				'error' => 'Module not found',
			]);
		}
		$module->priority = $r->priority;
		$module->save();
		return response()->json([
			'success' => true,
			'error' => 'Module priority updated successfully',
		]);
	}
}
