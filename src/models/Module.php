<?php

namespace Abs\ModulePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\ModulePkg\Platform;
use Abs\ProjectPkg\Project;
use Abs\ProjectPkg\ProjectVersion;
use Abs\StatusPkg\Status;
use App\Company;
use App\Config;
use App\ImportCronJob;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use PHPExcel_Shared_Date;
use Validator;

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
		//REPLACE TODAY DATE TO  NULL FOR MODULE IMPORT
		return $this->attributes['start_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function setEndDateAttribute($date) {
		//REPLACE TODAY DATE TO  NULL FOR MODULE IMPORT
		return $this->attributes['end_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
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

	public function phases() {
		return $this->belongsToMany('Abs\ProjectPkg\Phase', 'phase_module', 'module_id', 'phase_id');
	}

	public function assignedTo() {
		return $this->belongsTo('App\User', 'assigned_to_id');
	}

	public function status() {
		return $this->belongsTo('App\Status');
	}

	// public function platform() {
	// 	return $this->belongsTo('App\Config', 'platform_id');
	// }

	public function platform() {
		return $this->belongsTo('Abs\ModulePkg\Platform', 'platform_id');
	}

	public function projectVersion() {
		return $this->belongsTo('Abs\ProjectPkg\ProjectVersion', 'project_version_id');
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

	public static function importFromExcel($job) {
		try {
			$response = ImportCronJob::getRecordsFromExcel($job, 'N', $job->type->sheet_index);
			$rows = $response['rows'];
			$header = $response['header'];

			$all_error_records = [];
			foreach ($rows as $k => $row) {
				$record = [];
				foreach ($header as $key => $column) {
					if (!$column) {
						continue;
					} else {
						$record[$column] = trim($row[$key]);
						$header_col = str_replace(' ', '_', strtolower($column));
						$record[$header_col] = $row[$key];
					}
				}
				$original_record = $record;
				$status = [];
				$status['errors'] = [];

				$save_eligible = true;

				// dd($record);

				$validator = Validator::make($record, [
					'project_code' => [
						'required',
						'string',
						'max:191',
						Rule::exists('projects', 'code')
							->where(function ($query) {
								$query->whereNull('deleted_at');
							}),
					],
					'requirement_number' => [
						'required',
						'string',
						'max:191',
					],
					'module_code' => [
						'nullable',
						'string',
						'max:191',
						'distinct',
					],
					'module_name' => [
						'required',
						'string',
						'max:191',
						'distinct',
					],
					'priority' => [
						'nullable',
						'integer',
						'max:999',
					],
					'platform' => [
						'required',
						'string',
						'max:191',
						Rule::exists('platforms', 'name')
							->where(function ($query) {
								$query->whereNull('deleted_at');
							}),
					],
					'start_date' => [
						'nullable',
					],
					'end_date' => [
						'nullable',
					],
					'duration' => [
						'nullable',
						'between:0,99.99',
					],
					'remarks' => [
						'nullable',
						'string',
					],
					'completed_percentage' => [
						'nullable',
						'integet',
					],
					'status' => [
						'required',
						'string',
						'max:191',
						Rule::exists('statuses', 'name')
							->where(function ($query) {
								$query->whereNull('deleted_at');
							}),
					],
				]);

				if ($validator->fails()) {
					$status['errors'] = $validator->errors()->all();
					$save_eligible = false;
				}

				$project = Project::where([
					'company_id' => $job->company_id,
					'code' => $record['project_code'],
				])->first();
				if (!$project) {
					$status['errors'][] = 'Invalid Project Code';
				} else {
					$project_version = ProjectVersion::where([
						'project_id' => $project->id,
						'number' => $record['requirement_number'],
					])->first();
					if (!$project_version) {
						$status['errors'][] = 'Invalid Project Version';
					}
					//else {
					// 	$module = Module::where([
					// 		'project_version_id' => $project_version->id,
					// 		'name' => $record['module_name'],
					// 	])->first();
					// 	if (!$module) {
					// 		$status['errors'][] = 'Invalid Module';
					// 	}
					// }
				}

				$platform = Platform::where([
					'company_id' => $job->company_id,
					'name' => $record['platform'],
				])->first();
				if (!$platform) {
					$status['errors'][] = 'Invalid Platform';
				}

				$status_detail = Status::where([
					'company_id' => $job->company_id,
					'name' => $record['status'],
				])->first();
				if (!$status_detail) {
					$status['errors'][] = 'Invalid Status';
				}

				//GET START DATE AND END DATE BY DOCUMENT DATE
				try {
					if (!empty($record['start_date'])) {
						$start_date = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($record['start_date']));
					}
					if (!empty($record['end_date'])) {
						$end_date = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($record['end_date']));
					}
				} catch (\Exception $e) {
					$status['errors'][] = 'Invalid Date Format';
				}

				// dd($record['start_date'], $record['end_date']);

				if (count($status['errors']) > 0) {
					// dump($status['errors']);
					$original_record['Record No'] = $k + 1;
					$original_record['Error Details'] = implode(',', $status['errors']);
					$all_error_records[] = $original_record;
					$job->incrementError();
					continue;
				}
				// dd($record);

				DB::beginTransaction();
				// dd(Auth::user()->company_id);

				$module = Module::firstOrNew([
					'project_version_id' => $project_version->id,
					'name' => $record['module_name'],
				]);

				$module->project_version_id = $project_version->id;
				$module->code = rand(1, 10000);
				$module->name = $record['module_name'];
				$module->priority = !empty($record['priority']) ? $record['priority'] : 999;
				$module->platform_id = !empty($platform) ? $platform->id : NULL;
				$module->start_date = !empty($record['start_date']) ? $start_date : NULL;
				$module->end_date = !empty($record['end_date']) ? $end_date : NULL;
				$module->status_id = !empty($status_detail) ? $status_detail->id : NULL;

				$module->save();
				$module->code = 'MOD' . $module->id;
				$module->save();

				$job->incrementNew();

				DB::commit();
				//UPDATING PROGRESS FOR EVERY FIVE RECORDS
				if (($k + 1) % 5 == 0) {
					$job->save();
				}
			}

			//COMPLETED or completed with errors
			$job->status_id = $job->error_count == 0 ? 7202 : 7205;
			$job->save();

			ImportCronJob::generateImportReport([
				'job' => $job,
				'all_error_records' => $all_error_records,
			]);

		} catch (\Throwable $e) {
			$job->status_id = 7203; //Error
			$job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
			$job->save();
			dump($job->error_details);
		}

	}

}
