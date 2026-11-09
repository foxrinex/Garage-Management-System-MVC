<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('car_id');
            $table->decimal('start_price', 10, 2);
            $table->decimal('current_bid', 10, 2);
            $table->decimal('reserve_price', 15, 2)->nullable();
            $table->decimal('min_increment', 10, 2)->default(100.00);
            $table->integer('highest_bidder_id')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->enum('status', ['Active', 'Closed', 'Failed'])->default('Active');
            $table->tinyInteger('winner_paid')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
