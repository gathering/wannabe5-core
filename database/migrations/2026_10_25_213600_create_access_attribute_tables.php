<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_attribute_types', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('source');
            $table->string('key')->index();
            $table->string('summary');
            $table->text('description')->nullable();
            $table->unique('key');
        });

        Schema::create('access_attributes', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('source')->index();
            $table->uuid('user_id')->index();
            $table->string('key');
            $table->json('value');
            $table->index(['user_id', 'key']);
            $table->unique(['user_id', 'key', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_attributes');
        Schema::dropIfExists('access_attribute_types');
    }
};
