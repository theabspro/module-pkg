<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModuleDependencyModuleC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('module_dependency_module')) {
			Schema::create('module_dependency_module', function (Blueprint $table) {
				$table->unsignedInteger('module_id');
				$table->unsignedInteger('dependancy_module_id');

				$table->foreign('module_id')->references('id')->on('modules')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('dependancy_module_id')->references('id')->on('modules')->onDelete('CASCADE')->onUpdate('cascade');

				$table->unique(["module_id", "dependancy_module_id"], "mdm_uniq");
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('module_dependency_module');
	}
}
