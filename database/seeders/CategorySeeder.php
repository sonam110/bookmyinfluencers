<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        /* TYPE CATEGORY */
        Category::create([
            'type' => '1',
            'name' => 'Vlogger',
            'is_parent' => Null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        Category::create([
            'type' => '1',
            'name' => 'Finance',
            'is_parent' => Null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        Category::create([
            'type' => '1',
            'name' => 'Education',
            'is_parent' => Null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        Category::create([
            'type' => '1',
            'name' => 'Entertainment',
            'is_parent' => Null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        Category::create([
            'type' => '1',
            'name' => 'Fashion',
            'is_parent' => Null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        Category::create([
            'type' => '1',
            'name' => 'Food',
            'is_parent' => Null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        ;
    }

}
