<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use App\Models\Admin\SetupPageHasSection;

class SectionHasPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $setup_page_has_sections = array(
        array('id' => '1','setup_page_id' => '1','site_section_id' => '2','position' => '1','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '2','setup_page_id' => '1','site_section_id' => '3','position' => '2','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '3','setup_page_id' => '1','site_section_id' => '5','position' => '3','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '4','setup_page_id' => '1','site_section_id' => '4','position' => '4','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '5','setup_page_id' => '1','site_section_id' => '14','position' => '5','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '6','setup_page_id' => '1','site_section_id' => '6','position' => '6','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '7','setup_page_id' => '1','site_section_id' => '15','position' => '7','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '8','setup_page_id' => '1','site_section_id' => '16','position' => '8','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '9','setup_page_id' => '1','site_section_id' => '8','position' => '9','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '10','setup_page_id' => '1','site_section_id' => '17','position' => '10','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '11','setup_page_id' => '1','site_section_id' => '7','position' => '11','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '12','setup_page_id' => '1','site_section_id' => '9','position' => '12','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '13','setup_page_id' => '1','site_section_id' => '11','position' => '13','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '14','setup_page_id' => '6','site_section_id' => '4','position' => '1','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '15','setup_page_id' => '6','site_section_id' => '5','position' => '2','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '16','setup_page_id' => '6','site_section_id' => '7','position' => '3','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '17','setup_page_id' => '6','site_section_id' => '8','position' => '4','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '18','setup_page_id' => '6','site_section_id' => '2','position' => '5','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '19','setup_page_id' => '6','site_section_id' => '3','position' => '6','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '20','setup_page_id' => '6','site_section_id' => '6','position' => '7','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '21','setup_page_id' => '6','site_section_id' => '9','position' => '8','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '22','setup_page_id' => '6','site_section_id' => '14','position' => '9','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '23','setup_page_id' => '6','site_section_id' => '15','position' => '10','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '24','setup_page_id' => '6','site_section_id' => '16','position' => '11','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '25','setup_page_id' => '6','site_section_id' => '17','position' => '12','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '26','setup_page_id' => '7','site_section_id' => '6','position' => '1','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '27','setup_page_id' => '7','site_section_id' => '2','position' => '2','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '28','setup_page_id' => '7','site_section_id' => '3','position' => '3','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '29','setup_page_id' => '7','site_section_id' => '4','position' => '4','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '30','setup_page_id' => '7','site_section_id' => '5','position' => '5','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '31','setup_page_id' => '7','site_section_id' => '7','position' => '6','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '32','setup_page_id' => '7','site_section_id' => '8','position' => '7','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '33','setup_page_id' => '7','site_section_id' => '9','position' => '8','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '34','setup_page_id' => '7','site_section_id' => '14','position' => '9','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '35','setup_page_id' => '7','site_section_id' => '15','position' => '10','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '36','setup_page_id' => '7','site_section_id' => '16','position' => '11','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '37','setup_page_id' => '7','site_section_id' => '17','position' => '12','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '38','setup_page_id' => '9','site_section_id' => '2','position' => '1','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '39','setup_page_id' => '9','site_section_id' => '3','position' => '2','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '40','setup_page_id' => '9','site_section_id' => '4','position' => '3','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '41','setup_page_id' => '9','site_section_id' => '5','position' => '4','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '42','setup_page_id' => '9','site_section_id' => '6','position' => '5','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '43','setup_page_id' => '9','site_section_id' => '7','position' => '6','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '44','setup_page_id' => '9','site_section_id' => '8','position' => '7','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '45','setup_page_id' => '9','site_section_id' => '9','position' => '8','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '46','setup_page_id' => '9','site_section_id' => '14','position' => '9','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '47','setup_page_id' => '9','site_section_id' => '15','position' => '10','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '48','setup_page_id' => '9','site_section_id' => '16','position' => '11','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '49','setup_page_id' => '9','site_section_id' => '17','position' => '12','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '50','setup_page_id' => '13','site_section_id' => '9','position' => '1','status' => '1','created_at' => now(),'updated_at' => now()),
        array('id' => '51','setup_page_id' => '13','site_section_id' => '2','position' => '2','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '52','setup_page_id' => '13','site_section_id' => '3','position' => '3','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '53','setup_page_id' => '13','site_section_id' => '4','position' => '4','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '54','setup_page_id' => '13','site_section_id' => '5','position' => '5','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '55','setup_page_id' => '13','site_section_id' => '6','position' => '6','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '56','setup_page_id' => '13','site_section_id' => '7','position' => '7','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '57','setup_page_id' => '13','site_section_id' => '8','position' => '8','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '58','setup_page_id' => '13','site_section_id' => '14','position' => '9','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '59','setup_page_id' => '13','site_section_id' => '15','position' => '10','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '60','setup_page_id' => '13','site_section_id' => '16','position' => '11','status' => '0','created_at' => now(),'updated_at' => now()),
        array('id' => '61','setup_page_id' => '13','site_section_id' => '17','position' => '12','status' => '0','created_at' => now(),'updated_at' => now())
        );

        SetupPageHasSection::upsert($setup_page_has_sections,['id'],['setup_page_id','site_section_id','position','status']);
    }
}
