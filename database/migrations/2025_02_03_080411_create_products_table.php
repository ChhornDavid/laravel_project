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
            $table->unsignedBigInteger('id_specialmenu')->nullable();
            $table->foreignId('id_category')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('name');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->float('rating')->default(0);
            $table->decimal('price', 8, 2);
            $table->string('size')->nullable();
            $table->timestamps();

            $table->foreign('id_specialmenu')->references('id')->on('special_menu')->onDelete('set null');
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
