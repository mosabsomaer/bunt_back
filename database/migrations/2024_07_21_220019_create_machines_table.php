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
        Schema::create('machines', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->integer('paper')->default(0);
            $table->integer('coins')->default(0);
            $table->integer('ink')->default(0);
            $table->enum('status', ['Active', 'Lost Connection', 'Resource Alert'])->default('Lost Connection');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
