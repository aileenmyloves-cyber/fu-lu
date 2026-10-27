<?php

namespace Database\Seeders\Update;


use Database\Seeders\Admin\TransactionSettingSeeder;
use Illuminate\Database\Seeder;

class VersionUpdateSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //version Update Seeders
        $this->call([
            AppSettingsSeeder::class,
            BasicSettingsSeeder::class,
            VirtualApiSeeder::class,
        ]);
    }

}
