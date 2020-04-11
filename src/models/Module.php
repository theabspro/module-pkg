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

	public function setEndDateAttribute($date) {
		return $this->attributes['end_date'] = empty($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));
	}

	public function getStartDateAttribute() {
		return !empty($this->attributes['start_date']) ? date('d-m-Y', strtotime($this->attributes['start_date'])) : '';
	}

	public function getEndDateAttribute() {
		return !empty($this->attributes['end_date']) ? date('d-m-Y', strtotime($this->attributes['end_date'])) : '';
	}

	public function parentModules() {
		return $this->belongsToMany('Abs\ModulePkg\Module', 'module_parent_module', 'module_id', 'parent_module_id');
	}

	public function subModules() {
		return $this->belongsToMany('Abs\ModulePkg\Module', 'module_parent_module', 'parent_module_id', 'module_id');
	}

	public function assignedTo() {
		return $this->belongsTo('App\User', 'assigned_to_id');
	}

	public function status() {
		return $this->belongsTo('App\Status');
	}

	public function projectVersion() {
		return $this->belongsTo('Abs\ProjectPkg\ProjectVersion','project_version_id');
	}

	public static function createFromObject($record_data, $company = null) {

		if (!$company) {
			$company = Company::where('code', $record_data->company_code)->first();
		}
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company_code);
			return;
		}

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

		$errors = [];
		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'code' => $record_data->module_code,
		]);
		$record->project_version_id = 1;
		$record->name = $record_data->name;
		$record->group_id = null;
		$record->priority = $record_data->priority;
		$record->platform_id = null;
		$record->start_date = null;
		$record->end_date = null;
		$record->duration = $record_data->duration;
		$record->completed_percentage = $record_data->completed_percentage;
		$record->status_id = 1;
		$record->assigned_to_id = null;
		$record->remarks = 1;
		$record->source_branch_id = 1;
		$record->tester_id = 1;
		// $record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

	public static function mapPerents($records, $company = null, $specific_company = null, $tc, $command) {

		$bar = $command->getOutput()->createProgressBar(count($records));
		$bar->start();
		$command->getOutput()->writeln(1);

		$success = 0;
		foreach ($records as $key => $record_data) {
			try {
				$bar->advance(1);
				if (!$record_data->company_code) {
					continue;
				}

				if ($specific_company) {
					if ($record_data->company_code != $specific_company->code) {
						continue;
					}
				}

				if ($tc) {
					if ($record_data->tc != $tc) {
						continue;
					}
				}

				if (!$record_data->company_code) {
					continue;
				}
				$record = self::mapLOB($record_data, $company);
				$success++;
			} catch (Exception $e) {
				dd($e);
			}
		}
	}

	public static function mapPerent($record_data, $company) {
		if (!$company) {
			$company = Company::where('code', $record_data->company_code)->first();
		}
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company_code);
			return;
		}

		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();

		$errors = [];
		$record = Outlet::where('code', $record_data->outlet)->where('company_id', $company->id)->first();
		if (!$record) {
			$errors[] = 'Invalid Outlet : ' . $record_data->outlet;
		}

		$lob = Lob::where('name', $record_data->lob)->where('company_id', $company->id)->first();
		if (!$lob) {
			$errors[] = 'Invalid LOB : ' . $record_data->lob;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}
		$record->lobs()->syncWithoutDetaching([$lob->id]);
		return $record;
	}

	public function getGanttChartData1(&$data) {
		$data[$this->id] = [
			$this->code,
			$this->name . ' / ' . ($this->assignedTo ? $this->assignedTo->name : '') . '/' . $this->code,
			$this->assigned_to_id . '',
			// 'new Date(' . date('Y,m,d', strtotime($module->start_date)) . ')',
			null,
			null,
			$this->duration * 24 * 60 * 60 * 1000,
			(float) $this->completed_percentage,
			// implode(',', $this->parentModules()->pluck('code')->toArray()),
			implode(',', $this->subModules()->pluck('code')->toArray()),
			// $this->dependencies,
		];
		foreach ($this->subModules as $dm) {
			$dm->getGanttChartData($data);
		}

	}

	public function getGanttChartData(&$data) {
		$data[$this->id] = [
			$this->code,
			$this->name . ' / ' . ($this->assignedTo ? $this->assignedTo->name : ''),
			$this->assigned_to_id . '',
			// 'new Date(' . date('Y,m,d', strtotime($module->start_date)) . ')',
			null,
			null,
			$this->duration * 24 * 60 * 60 * 1000,
			(float) $this->completed_percentage,
			implode(',', $this->parentModules()->pluck('code')->toArray()),
			// implode(',', $this->subModules()->pluck('code')->toArray()),
			// $this->dependencies,
		];
		foreach ($this->parentModules as $dm) {
			$dm->getGanttChartData($data);
		}

	}

}
