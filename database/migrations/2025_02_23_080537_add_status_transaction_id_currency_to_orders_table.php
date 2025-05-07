<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid'])->default('pending')->change(); // Add status column
            $table->string('transaction_id')->nullable(); // Add transaction_id (nullable initially)
            $table->string('currency', 3)->default('USD');  // Add currency
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->dropColumn('transaction_id');
            $table->dropColumn('currency');
        });
    }
};