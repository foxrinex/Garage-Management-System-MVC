<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('products');

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('product_name', 255);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->string('image', 255)->nullable()->default('product-default.png');
            $table->integer('category_id')->nullable();
            $table->string('status', 20)->nullable()->default('In Stock');
            $table->tinyInteger('regular_warranty')->nullable()->default(1);
            $table->tinyInteger('replacement_warranty')->nullable()->default(0);
            $table->string('tags', 255)->nullable();
            $table->integer('hp')->nullable()->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
