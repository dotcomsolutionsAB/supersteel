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
        Schema::create('t_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code');
            $table->string('product_name');
            $table->string('print_name');
            $table->string('brand')->nullable();
            // $table->string('category')->nullable();
            $table->string('c1')->nullable();
            $table->string('c2')->nullable();
            $table->string('c3')->nullable();
            $table->string('c4')->nullable();
            $table->string('c5')->nullable();
            // $table->string('c6')->nullable();
            $table->enum('type', ['machine', 'spare']);
            $table->string('machine_part_no')->nullable();
            $table->string('price_a')->nullable();
            $table->string('price_b')->nullable();
            $table->string('price_c')->nullable();
            $table->string('price_d')->nullable();
            $table->string('price_e')->nullable();
            $table->longText('product_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_products');
    }
};
