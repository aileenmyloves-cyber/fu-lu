<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cardyfie_virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->string('user_type',50)->default("USER")->comment("should be USER, AGENT, MERCHANT");
            $table->unsignedBigInteger('user_id');
            $table->string('reference_id',255)->nullable();
            $table->string('ulid',255)->nullable();
            $table->string('customer_ulid',255)->nullable();
            $table->string('card_name',100)->nullable();
            $table->decimal('amount', 28,8)->default(0);
            $table->string('currency',10)->nullable();
            $table->string('card_tier',50)->nullable();
            $table->string('card_type',50)->nullable();
            $table->string('card_exp_time',50)->nullable();
            $table->string('masked_pan',100)->nullable();
            $table->text('address')->nullable();
            $table->string('status',100)->nullable();
            $table->string('env',100)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cardyfie_virtual_cards');
    }
};
