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
        Schema::create('user_profile', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('name', 128)->nullable();
            $table->string('nickname', 128)->nullable();
            $table->string('email', 128);
            $table->date('birth')->nullable();
            $table->enum('sexe', ['undefined', 'male', 'female', 'other', 'na'])->default('undefined');
            $table->string('phone')->nullable();
            $table->string('language', 8)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profile');
    }
};
