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
        Schema::create('customer', function (Blueprint $table) {
            
            $table->id('customer_id');
            
            $table->unsignedBigInteger('user_id');
            
            $table->string('fname', 127);
            
            $table->string('lname', 127);
            
            $table->string('phone_number', 31);
            
            $table->string('email_address', 255);
            
            $table->string('address', 255);
            
            $table->string('gender', 10);
            
            $table->timestamp('date_of_joined')->useCurrent();

            $table->foreign('user_id')
                  ->references('uid')
                  ->on('users')
                  ->onDelete('cascade');
                  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};