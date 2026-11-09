<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message', function (Blueprint $table) {
            $table->increments('message_id');
            $table->string('sender_full_name', 100);
            $table->string('sender_email', 255);
            $table->text('message');
            $table->dateTime('date_time')->useCurrent();
            $table->string('status', 25)->nullable()->default('Unread');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message');
    }
};