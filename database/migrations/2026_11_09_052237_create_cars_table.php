<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop if exists to avoid conflicts with the earlier simplified stub table
        Schema::dropIfExists('cars');

        Schema::create('cars', function (Blueprint $table) {
            $table->increments('id');
            $table->string('brand', 255);
            $table->string('model', 255);
            $table->integer('year');
            $table->integer('category_id');
            $table->decimal('price', 15, 2);
            $table->integer('quantity')->nullable()->default(0);
            $table->enum('status', ['In Stock', 'Upcoming', 'Out of Stock'])->nullable()->default('Upcoming');
            $table->enum('transmission', ['Manual', 'Automatic', 'CVT', 'DCT', 'AMT'])->nullable()->default('Automatic');
            $table->integer('mileage')->nullable()->default(0);
            $table->text('description')->nullable();
            $table->tinyInteger('regular_warranty')->nullable()->default(1);
            $table->tinyInteger('replacement_warranty')->nullable()->default(0);
            $table->string('image', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->tinyInteger('in_auction')->nullable()->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
