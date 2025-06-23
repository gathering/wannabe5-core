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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('firstname', 128)->nullable();
            $table->string('lastname', 128)->nullable();
            $table->string('nickname', 128)->nullable();
            $table->string('email', 256);
            $table->date('birthdate')->nullable();
            $table->enum('gender', ['undefined', 'male', 'female', 'other'])->default('undefined');
            $table->string('phone')->nullable();

            $table->string('streetaddress', 128)->nullable();
            $table->string('postcode', 32)->nullable();
            $table->string('town', 128)->nullable();
            $table->string('countrycode', 2)->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
