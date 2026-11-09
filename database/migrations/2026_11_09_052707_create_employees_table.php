<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('employee_id');
            $table->integer('user_id');
            $table->string('fname', 255);
            $table->string('lname', 255);
            $table->string('email_address', 255);
            $table->string('phone_number', 20)->nullable();
            $table->decimal('salary', 10, 2)->nullable()->default(0.00);
            $table->dateTime('date_of_joined')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
