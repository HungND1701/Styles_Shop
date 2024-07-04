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
        Schema::table('orders', function (Blueprint $table) {
            $table->string("fullname");
            $table->string("phone_number");
            $table->string("email");
            $table->string("address");
            $table->string("city");
            $table->string("district");
            $table->string("commune");
            $table->string("note")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn("fullname");
            $table->dropColumn("phone_number");
            $table->dropColumn("email");
            $table->dropColumn("address");
            $table->dropColumn("city");
            $table->dropColumn("district");
            $table->dropColumn("commune");
            $table->dropColumn("note");

        });
    }
};
