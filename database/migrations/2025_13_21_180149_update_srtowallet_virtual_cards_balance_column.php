<?php

use App\Constants\GlobalConst;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('strowallet_virtual_cards', function (Blueprint $table) {

            DB::statement("UPDATE strowallet_virtual_cards SET balance = REPLACE(balance, ',', '')");
            DB::statement("
                ALTER TABLE strowallet_virtual_cards
                MODIFY balance DECIMAL(28,8) UNSIGNED NOT NULL DEFAULT 0.00000000
            ");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("
            ALTER TABLE strowallet_virtual_cards
            MODIFY balance VARCHAR(255) NOT NULL
        ");
    }
};
