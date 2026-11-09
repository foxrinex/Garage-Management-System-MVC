<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('uid');
            $table->string('username', 127);
            $table->string('password', 255);
            $table->enum('user_type', ['manager', 'employee', 'customer']);
        });

        // Seed the hard-coded admin user (ID 3)
        DB::table('users')->insert([
            'uid' => 3,
            'username' => 'admin',
            'password' => '$2y$10$NoUMt1JP7nfZfVyqYCmx5eR0cIwvFmXD22Ey4vOa1B2dxs7uK9D1.',
            'user_type' => 'manager',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
