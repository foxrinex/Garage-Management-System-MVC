<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            
            $table->id('uid');
            
            $table->string('username', 127)->unique();
            
            $table->string('password', 255);
            
            $table->enum('user_type', ['manager', 'employee', 'customer']);
            
            // Notice: `$table->timestamps();` has been removed to match your original database schema.
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
