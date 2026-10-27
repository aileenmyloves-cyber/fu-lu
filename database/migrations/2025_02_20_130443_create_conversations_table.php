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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("chatbox_id");
            $table->unsignedBigInteger("sender");
            $table->string("sender_type");
            $table->unsignedBigInteger("receiver")->nullable();
            $table->string("receiver_type")->nullable();
            $table->text("message");
            $table->boolean("seen")->default(false);
            $table->timestamps();

            $table->foreign("chatbox_id")->references("id")->on("chatboxes")->onDelete("cascade")->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversations');
    }
};
