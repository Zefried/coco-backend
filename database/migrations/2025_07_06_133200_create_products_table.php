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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            
            $table->string('name')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('clay_type')->nullable();
            $table->string('firing_method')->nullable();
            $table->string('glaze_type')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('weight')->nullable();
            $table->string('price')->nullable()->index();
            $table->string('discount_percent')->nullable();
            $table->string('stock_quantity')->nullable();
            $table->boolean('is_fragile')->default(false)->nullable();
            $table->boolean('is_handmade')->default(false)->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('subcategory_id')->references('id')->on('sub_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
