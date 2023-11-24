<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class UserSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        \DB::table('users')->truncate()();
        $adminUser = new User();
        $adminUser->userType = 'admin';
        $adminUser->fullname = 'BMI ADMIN MANAGER';
        $adminUser->email = 'abhishek@prchitects.com';
        $adminUser->password = \Hash::make(12345678);
        $adminUser->status = '1';
        $adminUser->save();
        $adminUser->assignRole('admin');

        $influUser = new User();
        $influUser->userType = 'influencer';
        $influUser->fullname = 'influencer';
        $influUser->email = 'influencer@gmail.com';
        $influUser->password = \Hash::make(12345678);
        $influUser->status = '1';
        $influUser->save();
        $influUser->assignRole('influencer');

        $influUser1 = new User();
        $influUser1->userType = 'influencer1';
        $influUser1->fullname = 'influencer1';
        $influUser1->email = 'influencer1@gmail.com';
        $influUser1->password = \Hash::make(12345678);
        $influUser1->status = '1';
        $influUser1->save();
        $influUser1->assignRole('influencer');

        $brandUser = new User();
        $brandUser->userType = 'brand';
        $brandUser->fullname = 'brand';
        $brandUser->email = 'brand@gmail.com';
        $brandUser->password = \Hash::make(12345678);
        $brandUser->status = '1';
        $brandUser->save();
        $brandUser->assignRole('brand');
    }

}
