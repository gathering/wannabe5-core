<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->uuid('user_id')->index();
            $table->unsignedBigInteger('event_id')->index();
            $table->unsignedBigInteger('crew_id')->index();
            $table->string('status')->default('received');
            $table->text('message')->nullable();
            $table->dateTime('reviewable_at')->nullable();
        });

        Schema::create('application_questions', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('crew_id')->nullable()->index();
            $table->unsignedBigInteger('event_id')->nullable()->index();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
        });

        Schema::create('application_answers', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('application_questions')->cascadeOnDelete();
            $table->uuid('user_id')->index();
            $table->unsignedBigInteger('event_id')->nullable()->index();
            $table->text('content')->nullable();
            $table->unique(['application_id', 'question_id']);
        });

        Schema::create('application_comments', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->uuid('user_id')->index();
            $table->text('comment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_comments');
        Schema::dropIfExists('application_answers');
        Schema::dropIfExists('application_questions');
        Schema::dropIfExists('applications');
    }
};
