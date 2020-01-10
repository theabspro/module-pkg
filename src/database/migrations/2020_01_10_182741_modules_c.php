<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModulesC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('modules')) {
			Schema::create('modules', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('project_version_id');
				$table->string('name', 191);
				$table->unsignedInteger('group_id')->nullable();
				$table->datetime('start_date')->nullable();
				$table->datetime('end_date')->nullable();
				$table->unsignedDecimal('duration', 12, 2)->nullable();
				$table->unsignedDecimal('completion_percentage', 5, 2);
				$table->unsignedInteger('created_by_id')->nullable();
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softdeletes();

				$table->foreign('group_id')->references('id')->on('module_groups')->onDelete('SET NULL')->onUpdate('cascade');

				$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

				$table->unique(["project_version_id", "name"]);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('modules');
	}
}
