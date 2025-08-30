<?php

namespace ArtflowStudio\Table\Database\Seeders;

use Illuminate\Database\Seeder;

class AFTableTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AFTestDepartmentSeeder::class,
            AFTestUserSeeder::class,
            AFTestProjectSeeder::class,
            AFTestTaskSeeder::class,
        ]);
    }
}
