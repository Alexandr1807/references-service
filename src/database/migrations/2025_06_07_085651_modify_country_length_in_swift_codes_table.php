<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('swift_codes', function (Blueprint $table) {
            // расширяем до 100 символов
            $table->string('country', 100)->change();
        });
    }

    public function down()
    {
        Schema::table('swift_codes', function (Blueprint $table) {
            $table->string('country', 2)->change();
        });
    }
};
