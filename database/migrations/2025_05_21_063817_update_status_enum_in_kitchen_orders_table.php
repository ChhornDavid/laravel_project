<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE kitchen_orders MODIFY status ENUM('pending', 'accepted', 'preparing', 'completed') DEFAULT 'pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE kitchen_orders MODIFY status ENUM('pending', 'preparing', 'completed') DEFAULT 'pending'");
    }
};
