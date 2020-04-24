<?php

namespace Abs\ModulePkg;

use Abs\BasicPkg\Address;
use Abs\BasicPkg\Config;
use Abs\ModulePkg\Module;
use Abs\ModulePkg\ModuleGroup;
use Abs\ModulePkg\Platform;
use Abs\ProjectPkg\Project;
use Abs\ProjectPkg\ProjectVersion;
use Abs\StatusPkg\Status;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class PlatformController extends Controller
{
    public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getPlatformList(Request $request) {
		// dd($request->all());
		$platforms = Platform::withTrashed()
			->select([
				'platforms.id',
				'platforms.name',
				'platforms.display_order',
				DB::raw('IF(platforms.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('platforms.company_id', Auth::user()->company_id)
		
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('platforms.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->display_order)) {
					$query->where('platforms.display_order', $request->display_order);
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('platforms.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('platforms.deleted_at');
				}
			})
			->orderBy('display_order','asc')
		;

		return Datatables::of($platforms)
			->addColumn('name', function ($platforms) {
				$status = $platforms->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $platforms->name;
			})
			->addColumn('action', function ($platforms) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-platform')) {
					$output .= '<a href="#!/module-pkg/platform/edit/' . $platforms->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-platform')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#platforms-delete-modal" onclick="angular.element(this).scope().deletePlatform(' . $platforms->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getPlatformFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$platform = new Platform;
			$action = 'Add';
		} else {
			$platform = Platform::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['platform'] = $platform;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function savePlatform(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:platforms,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'display_order' => 'nullable|numeric',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$platform = new Platform;
				$platform->created_by_id = Auth::user()->id;
				$platform->created_at = Carbon::now();
				$platform->updated_at = NULL;
			} else {
				$platform = Platform::withTrashed()->find($request->id);
				$platform->updated_by_id = Auth::user()->id;
				$platform->updated_at = Carbon::now();
			}
			$platform->company_id = Auth::user()->company_id;
			$platform->fill($request->all());
			if ($request->status == 'Inactive') {
				$platform->deleted_by_id = Auth::user()->id;
				$platform->deleted_at = Carbon::now();
			} else {
				$platform->deleted_by_id = NULL;
				$platform->deleted_at = NULL;
			}
			if ($request->display_order == NULL) {
				$platform->display_order = 999;
			}
			$platform->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Platform Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Platform Updated Successfully',
				]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function deletePlatform(Request $request) {
		DB::beginTransaction();
		try {
			$platform = Platform::withTrashed()->where('id', $request->id)->first();
			if ($platform) {
				$platform = Platform::withTrashed()->where('id', $request->id)->forceDelete();
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Platform Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}


}
