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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->json('products')->nullable(); // stores product IDs + quantities + unit prices as JSON
            $table->text('address')->nullable();
            $table->string('pin')->nullable();
            $table->string('item_name')->nullable();
            $table->string('payment_id')->nullable(); // alphanumeric payment ID
            $table->string('delivery_status')->nullable()->index();
            $table->string('payment_status')->nullable()->index();
            $table->string('coupon_code')->nullable();  
            $table->string('coupon_discount')->nullable();
            $table->timestamp('order_date')->nullable()->index();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
