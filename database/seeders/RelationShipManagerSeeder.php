<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RelationshipManager;
class RelationShipManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RelationshipManager::create([
            'type' => '1',
            'fullname' => 'Mohseen Saifi',
            'email' => 'mohseen@prchitects.com',
            'phone' => '8505831552',
            'plan_id' => '1',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        RelationshipManager::create([
            'type' => '1',
            'fullname' => 'Divya Mittal',
            'email' => 'divya@prchitects.com',
            'phone' => '81300 55759',
            'plan_id' => '1',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        RelationshipManager::create([
            'type' => '1',
            'fullname' => 'Megha Dubey',
            'email' => 'megha@prchitects.com',
            'phone' => '9953995872',
            'plan_id' => '2',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        RelationshipManager::create([
            'type' => '1',
            'fullname' => 'Aaisha Mushtaq',
            'email' => 'aaisha@prchitects.com',
            'phone' => '91494 55473',
            'plan_id' => '2',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        RelationshipManager::create([
            'type' => '1',
            'fullname' => 'Kushagra Nigam',
            'email' => 'kushagra@prchitects.com',
            'phone' => '7565015888',
            'plan_id' => '3',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        RelationshipManager::create([
            'type' => '2',
            'fullname' => 'Manish Bijalwan',
            'email' => 'manish@prchitects.com',
            'phone' => 'manish@prchitects.com',
            'plan_id' => NULL,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
