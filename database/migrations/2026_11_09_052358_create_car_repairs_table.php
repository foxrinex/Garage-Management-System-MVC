<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('car_repairs');

        Schema::create('car_repairs', function (Blueprint $table) {
            $table->increments('repair_id');
            $table->integer('customer_id');
            $table->enum('repair_type', ['Service', 'Repair'])->nullable()->default('Repair');
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->integer('year')->nullable();
            $table->integer('mileage')->nullable();
            $table->text('issue_description')->nullable();
            $table->string('car_image', 255)->nullable();
            $table->enum('status', ['Pending', 'Inspecting', 'Awaiting Payment', 'Repairing', 'Ready', 'Completed', 'Rejected'])->nullable()->default('Pending');
            $table->text('repair_notes')->nullable();
            $table->decimal('labor_fee', 10, 2)->nullable()->default(500.00);
            $table->decimal('parts_total', 10, 2)->nullable()->default(0.00);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_repairs');
    }
};
