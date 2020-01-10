<?php

namespace Abs\ModulePkg;
use Abs\ModulePkg\Module;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ModuleGroupController extends Controller {

	public function __construct() {
	}

	public function getModuleGroupList(Request $request) {
		$module_list = Module::withTrashed()
			->select(
				'modules.id',
				'modules.code',
				'modules.name',
				DB::raw('IF(modules.mobile_no IS NULL,"--",modules.mobile_no) as mobile_no'),
				DB::raw('IF(modules.email IS NULL,"--",modules.email) as email'),
				DB::raw('IF(modules.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('modules.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->module_code)) {
					$query->where('modules.code', 'LIKE', '%' . $request->module_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->module_name)) {
					$query->where('modules.name', 'LIKE', '%' . $request->module_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('modules.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('modules.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('modules.id', 'desc');

		return Datatables::of($module_list)
			->addColumn('code', function ($module_list) {
				$status = $module_list->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $module_list->code;
			})
			->addColumn('action', function ($module_list) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/module-pkg/module/edit/' . $module_list->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_module"
					onclick="angular.element(this).scope().deleteModule(' . $module_list->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getModuleGroupFormData($id = NULL) {
		if (!$id) {
			$module = new Module;
			$address = new Address;
			$action = 'Add';
		} else {
			$module = Module::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['module'] = $module;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveModuleGroup(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Module Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'Module Code is already taken',
				'name.required' => 'Module Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'gst_number.required' => 'GST Number is Required',
				'gst_number.max' => 'Maximum 191 Numbers',
				'mobile_no.max' => 'Maximum 25 Numbers',
				// 'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				// 'pincode.required' => 'Pincode is Required',
				// 'pincode.max' => 'Maximum 6 Characters',
				// 'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:modules,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address' => 'required',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				// 'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$module = new Module;
				$module->created_by_id = Auth::user()->id;
				$module->created_at = Carbon::now();
				$module->updated_at = NULL;
				$address = new Address;
			} else {
				$module = Module::withTrashed()->find($request->id);
				$module->updated_by_id = Auth::user()->id;
				$module->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$module->fill($request->all());
			$module->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$module->deleted_at = Carbon::now();
				$module->deleted_by_id = Auth::user()->id;
			} else {
				$module->deleted_by_id = NULL;
				$module->deleted_at = NULL;
			}
			$module->gst_number = $request->gst_number;
			$module->axapta_location_id = $request->axapta_location_id;
			$module->save();

			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $module->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Module Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Module Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteModuleGroup($id) {
		$delete_status = Module::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
