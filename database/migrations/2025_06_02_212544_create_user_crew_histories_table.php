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
        Schema::create('user_crew_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('event_name', 128);
            $table->string('crew_name', 128);
            $table->bigInteger('event_id')->nullable();
            $table->bigInteger('crew_id')->nullable();
            $table->string('title', 128)->default('Member');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_crew_histories');
    }
};
