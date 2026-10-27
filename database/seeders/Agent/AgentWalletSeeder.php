<?php

namespace Database\Seeders\Agent;

use App\Models\Admin\Currency;
use App\Models\AgentWallet;
use Illuminate\Database\Seeder;

class AgentWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currencies = Currency::roleHasOne()->active()->get(['id', 'type']);
        $user_ids = [1,2];
        foreach($user_ids as $user_id) {
            foreach($currencies as $currency) {
                $data[] = [
                    'agent_id'       => $user_id,
                    'currency_id'   => $currency->id,
                    'balance'       => $currency->type === 'CRYPTO' ? 0.014 : 1000,
                    'status'        => true,
                ];
            }
        }

        AgentWallet::upsert($data,['agent_id','currency_id'],['balance']);
    }
}
