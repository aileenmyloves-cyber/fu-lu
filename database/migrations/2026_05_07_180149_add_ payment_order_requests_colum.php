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
        Schema::table('payment_order_requests', function (Blueprint $table) {
            $table->unsignedBigInteger("credential_id")->after('id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('payment_order_requests', function (Blueprint $table) {
            $table->dropColumn('credential_id');
        });

    }
};