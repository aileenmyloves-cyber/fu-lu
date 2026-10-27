<?php

namespace Database\Seeders\Update;

use Exception;
use App\Constants\GlobalConst;
use App\Models\VirtualCardApi;
use Illuminate\Database\Seeder;

class VirtualApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

         try{
            $virtualApi = VirtualCardApi::first();
            $config_data = (array) $virtualApi->config;

            $defaults = [
                'strowallet_url'   => "https://ziiropay.com/api/bitvcard/",

            ];

            // Merge defaults only for missing keys
            foreach ($defaults as $key => $value) {
                // if (!array_key_exists($key, $config_data)) {
                    $config_data[$key] = $value;
                // }
            }


            $virtualApi->config = (object) $config_data;
            $virtualApi->save();
        }catch(Exception $e){}

    }
}
