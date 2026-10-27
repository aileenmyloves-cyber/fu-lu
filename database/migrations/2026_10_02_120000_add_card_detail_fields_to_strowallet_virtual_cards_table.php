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
        Schema::table('strowallet_virtual_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('strowallet_virtual_cards', 'card_holder_name')) { $table->string('card_holder_name')->nullable()->after('card_name'); }
            if (!Schema::hasColumn('strowallet_virtual_cards', 'address')) { $table->string('address')->nullable()->after('customer_email'); }
            if (!Schema::hasColumn('strowallet_virtual_cards', 'city')) { $table->string('city')->nullable()->after('address'); }
            if (!Schema::hasColumn('strowallet_virtual_cards', 'state')) { $table->string('state')->nullable()->after('city'); }
            if (!Schema::hasColumn('strowallet_virtual_cards', 'postal')) { $table->string('postal')->nullable()->after('state'); }
            if (!Schema::hasColumn('strowallet_virtual_cards', 'country')) { $table->string('country')->nullable()->after('postal'); }
            if (!Schema::hasColumn('strowallet_virtual_cards', 'card_number_url')) { $table->text('card_number_url')->nullable()->after('country'); }
            if (!Schema::hasColumn('strowallet_virtual_cards', 'cvv_url')) { $table->text('cvv_url')->nullable()->after('card_number_url'); }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('strowallet_virtual_cards', function (Blueprint $table) {
            $table->dropColumn([
                'card_holder_name',
                'address',
                'city',
                'state',
                'postal',
                'country',
                'card_number_url',
                'cvv_url',
            ]);
        });
    }
};
