<?php

namespace Abs\ModulePkg;
use Abs\ModulePkg\ModuleGroup;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ModuleGroupController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getModuleGroupList(Request $request) {
		$module_group_list = ModuleGroup::withTrashed()
			->select(
				'module_groups.id',
				'module_groups.name',
				DB::raw('IF(module_groups.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('module_groups.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('module_groups.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('module_groups.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('module_groups.deleted_at');
				}
			})
		// ->get()
		;

		return Datatables::of($module_group_list)
			->addColumn('name', function ($module_group_list) {
				$status = $module_group_list->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $module_group_list->name;
			})
			->addColumn('action', function ($module_group_list) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');

				$output = '';
				if (Entrust::can('edit-module-group')) {
					$output .= '<a href="#!/module-pkg/module-group/edit/' . $module_group_list->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}

				if (Entrust::can('delete-module-group')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#module-group-delete-modal" onclick="angular.element(this).scope().deleteModuleGroup(' . $module_group_list->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getModuleGroupFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$module_group = new ModuleGroup;
			$action = 'Add';
		} else {
			$module_group = ModuleGroup::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['module_group'] = $module_group;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveModuleGroup(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Module Group Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'name.unique' => 'Module Group Name is already taken',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:module_groups,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$module_group = new ModuleGroup;
				$module_group->created_by_id = Auth::user()->id;
				$module_group->created_at = Carbon::now();
				$module_group->updated_at = NULL;
			} else {
				$module_group = ModuleGroup::withTrashed()->find($request->id);
				$module_group->updated_by_id = Auth::user()->id;
				$module_group->updated_at = Carbon::now();
			}
			$module_group->fill($request->all());
			$module_group->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$module_group->deleted_at = Carbon::now();
				$module_group->deleted_by_id = Auth::user()->id;
			} else {
				$module_group->deleted_by_id = NULL;
				$module_group->deleted_at = NULL;
			}
			$module_group->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Module Group Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Module Group Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteModuleGroup(Request $request) {
		DB::beginTransaction();
		try {
			$delete_status = ModuleGroup::withTrashed()->where('id', $request->id)->forceDelete();
			if ($delete_status) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Module Group Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
