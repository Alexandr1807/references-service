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
        Schema::create('treasury_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('account', 20)->unique();
            $table->string('mfo', 5);
            $table->string('name');
            $table->string('department');
            $table->string('currency');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_accounts');
    }
};
