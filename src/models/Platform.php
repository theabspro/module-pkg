<?php

namespace Abs\ModulePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Platform extends Model
{
	use SeederTrait;
    use SoftDeletes;
	protected $table = 'platforms';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'name',
		'display_order',
	];

	public function modules() {
		return $this->hasMany('Abs\ModulePkg\Module','platform_id');
	}
}
