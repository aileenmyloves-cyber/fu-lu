<?php

namespace Database\Seeders\Update;

use Exception;
use Illuminate\Database\Seeder;
use App\Models\Admin\BasicSettings;

class BasicSettingsSeeder extends Seeder
{
      /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()

    {
        $basicSettings = BasicSettings::first();

        $data = [
            'web_version'    => "2.3.0",
            'user_pin_verification'     => $basicSettings->user_pin_verification     == true ? true : false,
            'agent_pin_verification'    => $basicSettings->agent_pin_verification    == true ? true : false,
            'merchant_pin_verification' => $basicSettings->merchant_pin_verification == true ? true : false,
        ];
        $basicSettings->update($data);

          //update language values
        try{
            update_project_localization_data();
        }catch(Exception $e) {
            // handle error
        }
    }
}