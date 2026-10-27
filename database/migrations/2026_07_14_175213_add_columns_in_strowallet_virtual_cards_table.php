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
        Schema::table('strowallet_virtual_cards', function (Blueprint $table) {
            $table->string('authorization_code', 100)->nullable()->after('expiry');
            $table->timestamp('auth_code_send_at')->nullable()->after('authorization_code');
            $table->string('mode',100)->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('strowallet_virtual_cards', function (Blueprint $table) {
            $table->dropColumn([
                'authorization_code',
                'auth_code_send_at',
                'mode',
            ]);
        });
    }
};
