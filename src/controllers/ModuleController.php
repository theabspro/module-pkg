<?php

namespace Abs\ModulePkg;
use Abs\ModulePkg\Module;
use Abs\ModulePkg\ModuleGroup;
use Abs\ProjectPkg\Project;
use Abs\ProjectPkg\ProjectVersion;
use Abs\StatusPkg\Status;
use App\Address;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ModuleController extends Controller {

	public function __construct() {
	}

	public function getModuleList(Request $request) {
		$modules = Module::withTrashed()
			->join('project_versions as pv', 'modules.project_version_id', 'pv.id')
			->join('projects as p', 'pv.project_id', 'p.id')
			->leftJoin('module_groups as mg', 'modules.group_id', 'mg.id')
			->leftJoin('users as at', 'modules.assigned_to_id', 'at.id')
			->select([
				'modules.id',
				DB::raw('CONCAT(p.short_name," / ",p.code) as project_name'),
				'pv.number as project_version_number',
				DB::raw('CONCAT(at.first_name," ",at.last_name) as assigned_to'),
				'modules.name',
				'mg.name as group_name',
				DB::raw('date_format(modules.start_date,"%d-%m-%Y") as start_date'),
				DB::raw('date_format(modules.end_date,"%d-%m-%Y") as end_date'),
				'modules.duration as duration',
				'modules.completed_percentage',
				DB::raw('IF(modules.deleted_at IS NULL,"Active","Inactive") as status'),
			])
			->where('pv.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->module_code)) {
					$query->where('modules.code', 'LIKE', '%' . $request->module_code . '%');
				}
			})
			->orderBy('at.id', 'asc')
			->orderBy('modules.duration', 'asc')
		;

		return Datatables::of($modules)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($module) {
				$status = $module->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $module->name;
			})
			->addColumn('action', function ($module) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/module-pkg/module/edit/' . $module->id . '">
						<img src="' . $edit_img . '" alt="edit" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_module"
					onclick="angular.element(this).scope().deleteModule(' . $module->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
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
				'projectVersion.project',
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
		$this->data['extras']['module_list'] = Collect(
			Module::select('id', 'name', 'code')
				->where('id', '!=', $module->id)
				->orderBy('modules.code')
				->get())
		;
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
				->company()
				->get())->prepend(['name' => 'Select Status'])
		;
		$this->data['extras']['user_list'] = Collect(
			User::select([
				'id',
				DB::raw('CONCAT(first_name," ",last_name) as name'),
				'email',
			])
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
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Module Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:modules,name,' . $request->id . ',id,project_version_id,' . Auth::user()->company_id,
				],
				'duration' => 'required|numeric',
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
	public function deleteModule($id) {
		$delete_status = Module::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
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
}
