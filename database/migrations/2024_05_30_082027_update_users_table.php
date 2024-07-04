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
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('gender');
            $table->string('phoneNumber', 30);
            $table->string('dob', 12);
            $table->tinyInteger('height');
            $table->tinyInteger('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
            $table->dropColumn('phoneNumber');
            $table->dropColumn('dob');
            $table->dropColumn('height');
            $table->dropColumn('weight');
        });
    }
};
