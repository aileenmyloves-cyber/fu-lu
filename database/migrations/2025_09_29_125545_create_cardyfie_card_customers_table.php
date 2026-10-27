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
        Schema::create('cardyfie_card_customers', function (Blueprint $table) {
            $table->id();
            $table->string('user_type',50)->default("USER")->comment("should be USER, AGENT, Merchant");
            $table->unsignedBigInteger('user_id');
            $table->string('ulid',255)->nullable();
            $table->string('reference_id',255)->nullable();
            $table->string('first_name',100);
            $table->string('last_name',100);
            $table->string('email',100)->unique();
            $table->string('date_of_birth',100);
            $table->string('id_type',50);
            $table->string('id_number',50);
            $table->longText('id_front_image')->nullable();
            $table->longText('id_back_image')->nullable();
            $table->longText('user_image')->nullable();
            $table->string('house_number',50)->nullable();
            $table->string('address_line_1',255)->nullable();
            $table->string('city',50)->nullable();
            $table->string('state',50)->nullable();
            $table->string('zip_code',50)->nullable();
            $table->string('country',50)->nullable();
            $table->string('status',100);
            $table->string('env',100);
            $table->text('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cardyfie_card_customers');
    }
};
