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
        Schema::create('trade_offers', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('trade_id')->constrained('trades')->cascadeOnDelete();
            $table->foreignId('trade_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->nullable()->cconstrained('users')->cascadeOnDelete();
            $table->decimal('amount', 28, 16);
            $table->decimal('rate', 28, 16);
            $table->foreignId('sale_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('rate_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->boolean("status")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_offers');
    }
};
