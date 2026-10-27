<?php

namespace Database\Seeders\Admin;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\Admin\SiteSections;
use Illuminate\Support\Facades\DB;
use App\Constants\SiteSectionConst;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SiteSectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $site_sections = file_get_contents(base_path("database/seeders/Admin/site-section.json"));
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SiteSections::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        SiteSections::insert(json_decode($site_sections,true));
    }
}
