<?php
namespace Abs\ModulePkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class ModulePkgPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//MODULE GROUP
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'module-groups',
				'display_name' => 'Module Groups',
			],
			[
				'display_order' => 1,
				'parent' => 'module-groups',
				'name' => 'add-module-group',
				'display_name' => 'Add',
			],
			[
				'display_order' => 1,
				'parent' => 'module-groups',
				'name' => 'edit-module-group',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 2,
				'parent' => 'module-groups',
				'name' => 'delete-module-group',
				'display_name' => 'Delete',
			],

			//MODULE
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'modules',
				'display_name' => 'Modules',
			],
			[
				'display_order' => 1,
				'parent' => 'modules',
				'name' => 'add-module',
				'display_name' => 'Add',
			],
			[
				'display_order' => 1,
				'parent' => 'modules',
				'name' => 'edit-module',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 2,
				'parent' => 'modules',
				'name' => 'delete-module',
				'display_name' => 'Delete',
			],
			[
				'display_order' => 4,
				'parent' => 'modules',
				'name' => 'view-all-module',
				'display_name' => 'View All',
			],
			[
				'display_order' => 6,
				'parent' => 'modules',
				'name' => 'view-own-module',
				'display_name' => 'View Only Own',
			],

		];
		Permission::createFromArrays($permissions);
	}
}