<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_sales', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('auction_id')->nullable();
            $table->integer('car_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->timestamp('sale_date')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_sales');
    }
};
