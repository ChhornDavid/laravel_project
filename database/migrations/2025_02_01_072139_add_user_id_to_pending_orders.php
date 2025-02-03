<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('pending_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pending_orders', 'user_id')) {
                $table->unsignedBigInteger('user_id')->after('id');

                // Foreign key constraint (optional)
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('pending_orders', function (Blueprint $table) {
            if (Schema::hasColumn('pending_orders', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
