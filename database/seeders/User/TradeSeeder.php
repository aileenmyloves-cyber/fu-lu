<?php

namespace Database\Seeders\User;

use App\Models\Trade;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $trades = array(
            array('id' => '6','user_id' => '1','currency_id' => '2','amount' => '50.0000000000000000','rate' => '40.0000000000000000','rate_currency_id' => '3','comment' => NULL,'status' => '1','created_at' => '2025-03-25 22:51:47','updated_at' => '2025-03-25 22:57:17'),
            array('id' => '7','user_id' => '1','currency_id' => '5','amount' => '60.0000000000000000','rate' => '70.0000000000000000','rate_currency_id' => '4','comment' => NULL,'status' => '1','created_at' => '2025-03-25 22:59:57','updated_at' => '2025-03-25 23:01:02')
        );

        Trade::insert($trades);

        $transactions = array(
            array('id' => '29','admin_id' => NULL,'user_id' => '1','user_wallet_id' => '2','merchant_id' => NULL,'merchant_wallet_id' => NULL,'agent_id' => NULL,'agent_wallet_id' => NULL,'sandbox_wallet_id' => NULL,'payment_gateway_currency_id' => NULL,'payment_link_id' => NULL,'trade_id' => '6','type' => 'TRADE','trx_id' => 'MT62898379','request_amount' => '50.00000000','payable' => '50.00000000','available_balance' => '813.73733800','remark' => 'TRADE','details' => '"Balance Payment"','reject_reason' => NULL,'status' => '1','attribute' => 'SEND','created_at' => '2025-03-25 22:51:47','updated_at' => NULL,'callback_ref' => NULL),
            array('id' => '32','admin_id' => NULL,'user_id' => '1','user_wallet_id' => '5','merchant_id' => NULL,'merchant_wallet_id' => NULL,'agent_id' => NULL,'agent_wallet_id' => NULL,'sandbox_wallet_id' => NULL,'payment_gateway_currency_id' => NULL,'payment_link_id' => NULL,'trade_id' => '7','type' => 'TRADE','trx_id' => 'MT65294444','request_amount' => '60.00000000','payable' => '60.00000000','available_balance' => '879.02620750','remark' => 'TRADE','details' => '"Balance Payment"','reject_reason' => NULL,'status' => '1','attribute' => 'SEND','created_at' => '2025-03-25 22:59:57','updated_at' => NULL,'callback_ref' => NULL)
        );

        Transaction::insert($transactions);
    }
}
