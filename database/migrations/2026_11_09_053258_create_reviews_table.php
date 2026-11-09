<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->nullable();
            $table->integer('car_id')->nullable();
            $table->integer('user_id');
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->text('company_reply')->nullable();
            $table->dateTime('date_posted')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
