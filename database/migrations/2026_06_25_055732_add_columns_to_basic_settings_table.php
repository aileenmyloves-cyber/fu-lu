<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->boolean('frontend_mode')->after('admin_prefix')->default(false)->comment("0 = default/html frontend, 1 = next.js frontend");
            $table->text('frontend_url')->after('frontend_mode')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn(['frontend_mode']);
            $table->dropColumn(['frontend_url']);
        });
    }
};