<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category_name', 255);
            $table->enum('type', ['Part', 'Car'])->nullable()->default('Part');
            $table->tinyInteger('is_mandatory')->nullable()->default(0);
            $table->string('category_img', 255)->nullable()->default('default-cat.png');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
