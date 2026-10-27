<?php

namespace Database\Seeders\Merchant;

use App\Models\Admin\Currency;
use App\Models\Merchants\SandboxWallet;
use Illuminate\Database\Seeder;

class MerchantSandboxWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currencies = Currency::roleHasOne()->active()->get(['id', 'type']);

        $merchant_ids = [1];

        foreach($merchant_ids as $merchant_id) {
            foreach($currencies as $currency) {
                $data[] = [
                    'merchant_id'   =>  $merchant_id,
                    'currency_id'   => $currency->id,
                    'balance'       => $currency->type === 'CRYPTO' ? 0.014 : 1000,
                    'status'        => true,
                    'created_at'    => now(),
                ];
            }
        }

        SandboxWallet::upsert($data,['merchant_id','currency_id'],['balance']);
    }
}
