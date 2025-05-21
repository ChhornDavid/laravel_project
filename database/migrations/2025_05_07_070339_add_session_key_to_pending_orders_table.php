<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('pending_orders', function (Blueprint $table) {
            $table->string('session_key')->after('status');
        });
    }

    public function down()
    {
        Schema::table('pending_orders', function (Blueprint $table) {
            $table->dropColumn('session_key');
        });
    }
};
