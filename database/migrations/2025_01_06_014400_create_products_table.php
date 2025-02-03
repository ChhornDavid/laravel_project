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
            $table->foreignId('id_specialmenu')->nullable()->constrained('special_menu')->onDelete('set null');
            $table->foreignId('id_category')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('name');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->float('rating')->default(0);
            $table->decimal('price', 8, 2);
            $table->string('size')->nullable();
            $table->timestamps();
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
