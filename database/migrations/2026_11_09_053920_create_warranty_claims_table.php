<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_id');
            $table->integer('customer_id');
            $table->string('claim_type', 20)->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->nullable()->default('Pending');
            $table->dateTime('date_submitted')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
    }
};