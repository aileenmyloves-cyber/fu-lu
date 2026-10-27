<?php

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
        Schema::table('sudo_virtual_cards', function (Blueprint $table) {
            $table->text('spendingControls')->after('fundingSource')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('sudo_virtual_cards', function (Blueprint $table) {
            $table->dropColumn('spendingControls');
        });

    }
};