<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('auction_id');
            $table->integer('customer_id');
            $table->decimal('bid_amount', 10, 2);
            $table->dateTime('bid_time')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_bids');
    }
};