<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempImagesTable extends Migration
{
    public function up()
    {
        Schema::create('temp_images', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('temp_images');
    }
}

