<?php

namespace Abs\ModulePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'modules';
	public $timestamps = true;
	protected $fillable = [
		'project_version_id',
		'name',
		'group_id',
		'priority',
		'platform_id',
		'start_date',
		'end_date',
		'duration',
		'completed_percentage',
		'status_id',
		'assigned_to_id',
		'screens',
		'remarks',
		'source_branch_id',
		'tester_id',
	];

	public function setStartDateAttribute($date) {
		return $this->attributes['start_date'] = empty($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));
	}

	public function parentModules() {
		return $this->belongsToMany('Abs\ModulePkg\Module', 'module_dependency_module', 'module_id', 'dependancy_module_id');
	}

	public function dependedModules() {
		return $this->belongsToMany('Abs\ModulePkg\Module', 'module_dependency_module', 'module_id', 'dependancy_module_id');
	}

	public function assignedTo() {
		return $this->belongsTo('App\User', 'assigned_to_id');
	}

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

	public function getGanttChartData(&$data) {
		$data[$this->id] = [
			$this->code,
			$this->name . ' / ' . $this->assignedTo->name,
			$this->assigned_to_id . '',
			// 'new Date(' . date('Y,m,d', strtotime($module->start_date)) . ')',
			null,
			null,
			$this->duration * 24 * 60 * 60 * 1000,
			(float) $this->completed_percentage,
			implode(',', $this->dependedModules()->pluck('code')->toArray()),
			// $this->dependencies,
		];
		foreach ($this->dependedModules as $dm) {
			$dm->getGanttChartData($data);
		}

	}
}
