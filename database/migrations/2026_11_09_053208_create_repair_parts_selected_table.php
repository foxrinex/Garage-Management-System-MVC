<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_parts_selected', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('repair_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('product_image', 255)->nullable();
            $table->string('category_name', 255)->nullable();
            $table->decimal('price_at_time', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_parts_selected');
    }
};
