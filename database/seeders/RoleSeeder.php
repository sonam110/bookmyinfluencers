<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create roles and assign existing permissions
        $admin = Role::create(['guard_name' => 'web', 'name' => 'admin']);

        $influencer = Role::create(['guard_name' => 'api', 'name' => 'influencer']);

        $brand = Role::create(['guard_name' => 'api', 'name' => 'brand']);
    }

}
