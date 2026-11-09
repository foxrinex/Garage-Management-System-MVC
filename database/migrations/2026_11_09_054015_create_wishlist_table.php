<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('product_id')->nullable();
            $table->integer('car_id')->nullable();
            $table->dateTime('date_added')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist');
    }
};
