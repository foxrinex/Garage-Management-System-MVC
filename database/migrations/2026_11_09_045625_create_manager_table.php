<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager', function (Blueprint $table) {
            $table->increments('manager_id');
            $table->integer('user_id');
            $table->string('fname', 255);
            $table->string('lname', 255);
            $table->string('email_address', 255)->nullable();
            $table->string('phone_number', 255)->nullable();
            $table->string('gender', 10)->nullable();
            $table->timestamp('date_of_joined')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager');
    }
};
