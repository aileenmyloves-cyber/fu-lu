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
        Schema::create('chatboxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("sender_id");
            $table->unsignedBigInteger("receiver_id");
            $table->string("token",120)->unique()->nullable();
            $table->boolean("status")->default(true);
            $table->timestamps();

            $table->foreign("sender_id")->references("id")->on("users")->onDelete("cascade")->onUpdate("cascade");
            $table->foreign("receiver_id")->references("id")->on("users")->onDelete("cascade")->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chatboxes');
    }
};
