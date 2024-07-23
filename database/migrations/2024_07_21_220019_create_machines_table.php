<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

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
            $table->float('coins')->default(0);
            $table->integer('ink')->default(0);
            $table->enum('status', ['Active', 'Lost Connection', 'Resource Alert'])->default('Lost Connection');
            $table->timestamp('last_ping')->default(Carbon::now()->format('Y-m-d H:i:s'));
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
