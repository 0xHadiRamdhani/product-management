<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_job_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->constrained('service_jobs');
            $table->foreignId('spare_part_id')->constrained('spare_parts');
            $table->foreignId('location_id')->constrained('locations');
            $table->integer('quantity_used');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->enum('status', ['allocated', 'used', 'returned'])->default('allocated');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['service_job_id', 'status']);
            $table->index(['spare_part_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_job_parts');
    }
};