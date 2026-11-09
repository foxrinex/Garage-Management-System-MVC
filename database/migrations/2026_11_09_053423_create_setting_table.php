<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting', function (Blueprint $table) {
            $table->increments('id');
            $table->string('garage_name', 127);
            $table->string('slogan', 255)->nullable();
            $table->text('about')->nullable();
            $table->integer('current_year')->nullable();
        });

        // Insert default garage settings
        DB::table('setting')->insert([
            'id' => 1,
            'garage_name' => 'Rev Motors',
            'slogan' => 'Built on Trust. Tuned for Performance.',
            'about' => 'Our garage was established with the goal of providing reliable vehicle maintenance, skilled mechanical service, and honest workmanship for every customer. Over the years, we have grown by expanding our range of services, upgrading our tools and equipment, and adopting modern diagnostic techniques to ensure efficient and high-quality repairs. We are committed to keeping vehicles safe, road-ready, and performing at their best while building long-term trust with our customers.',
            'current_year' => 2026,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('setting');
    }
};
