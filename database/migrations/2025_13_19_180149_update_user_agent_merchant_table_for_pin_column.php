<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('pin_status')->default(false)->after('password');
            $table->integer('pin_code')->nullable()->after('pin_status');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->boolean('pin_status')->default(false)->after('password');
            $table->integer('pin_code')->nullable()->after('pin_status');
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('pin_status')->default(false)->after('password');
            $table->integer('pin_code')->nullable()->after('pin_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pin_status', 'pin_code']);
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['pin_status', 'pin_code']);
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['pin_status', 'pin_code']);
        });
    }
};
