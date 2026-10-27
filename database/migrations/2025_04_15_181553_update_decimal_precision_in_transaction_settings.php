<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {

        DB::statement("
            ALTER TABLE transaction_settings
            MODIFY fixed_charge DECIMAL(28,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
            MODIFY percent_charge DECIMAL(28,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
            MODIFY min_limit DECIMAL(28,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
            MODIFY max_limit DECIMAL(28,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
            MODIFY monthly_limit DECIMAL(28,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
            MODIFY daily_limit DECIMAL(28,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
            MODIFY agent_fixed_commissions DECIMAL(28,8) UNSIGNED NOT NULL DEFAULT 0.00000000,
            MODIFY agent_percent_commissions DECIMAL(28,8) UNSIGNED NOT NULL DEFAULT 0.00000000
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("
            ALTER TABLE transaction_settings
            MODIFY fixed_charge DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
            MODIFY percent_charge DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
            MODIFY min_limit DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
            MODIFY max_limit DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
            MODIFY monthly_limit DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
            MODIFY daily_limit DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
            MODIFY agent_fixed_commissions DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
            MODIFY agent_percent_commissions DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00
        ");
    }
};
