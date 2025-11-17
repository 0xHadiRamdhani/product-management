<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_id')->constrained('spare_parts');
            $table->foreignId('location_id')->constrained('locations');
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->integer('max_stock_level')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->decimal('average_cost', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->date('last_stock_date')->nullable();
            $table->date('last_purchase_date')->nullable();
            $table->date('last_sale_date')->nullable();
            $table->integer('total_purchases')->default(0);
            $table->integer('total_sales')->default(0);
            $table->boolean('is_low_stock')->default(false);
            $table->boolean('is_out_of_stock')->default(false);
            $table->boolean('is_over_stock')->default(false);
            $table->timestamps();
            
            $table->unique(['spare_part_id', 'location_id']);
            $table->index(['is_low_stock', 'is_out_of_stock']);
            $table->index(['location_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};