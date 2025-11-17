<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markup_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('rule_type', ['category', 'supplier', 'cost_range', 'universal']);
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->decimal('min_cost', 15, 2)->nullable();
            $table->decimal('max_cost', 15, 2)->nullable();
            $table->decimal('markup_percentage', 5, 2);
            $table->decimal('min_markup', 15, 2)->nullable();
            $table->decimal('max_markup', 15, 2)->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'highest'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markup_rules');
    }
};