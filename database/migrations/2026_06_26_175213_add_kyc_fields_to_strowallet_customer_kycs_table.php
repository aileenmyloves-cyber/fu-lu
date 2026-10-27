<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('strowallet_customer_kycs', function (Blueprint $table) {
            $table->string('name', 100)->nullable()->after('user_id');
            $table->string('first_name', 50)->nullable()->after('name');
            $table->string('last_name', 50)->nullable()->after('first_name');
            $table->date('dob')->nullable()->after('last_name');
            $table->enum('id_type', ['national_id', 'passport', 'drivers_license'])->nullable()->after('dob');
            $table->string('id_number', 50)->nullable()->after('id_type');
            $table->string('email', 100)->nullable()->after('id_number');
            $table->string('line1', 250)->nullable()->after('email');
            $table->string('city', 100)->nullable()->after('line1');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');
            $table->string('phone_code')->nullable()->after('country');
            $table->string('phone', 20)->nullable()->after('phone_code');
        });

        DB::statement('
            ALTER TABLE strowallet_virtual_cards
            MODIFY COLUMN expiry VARCHAR(200) NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('strowallet_customer_kycs', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'first_name',
                'last_name',
                'dob',
                'id_type',
                'id_number',
                'email',
                'line1',
                'city',
                'state',
                'postal_code',
                'country',
                'phone_code',
                'phone',
            ]);
        });

        DB::statement('
            ALTER TABLE strowallet_virtual_cards
            MODIFY COLUMN expiry VARCHAR(5) NULL
        ');
    }
};