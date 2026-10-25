<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crew_events', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('location');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('visible')->default(false);
            $table->boolean('active')->default(false);
            $table->boolean('applications_open')->default(false);
        });

        Schema::create('crew_roles', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('event_id')->constrained('crew_events')->cascadeOnDelete();
            $table->string('name');
            $table->string('title');
            $table->text('description')->nullable();
        });

        Schema::create('crews', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('parent_crew')->nullable()->constrained('crews')->nullOnDelete();
            $table->foreignId('event_id')->constrained('crew_events')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('hidden')->default(false);
            $table->boolean('can_apply')->default(false);
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->integer('sorted_weight')->default(0);
        });

        Schema::create('crew_teams', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('crew_id')->constrained('crews')->cascadeOnDelete();
            $table->string('name');
        });

        Schema::create('crew_users', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->uuid('user_id')->index();
            $table->foreignId('crew_id')->constrained('crews')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('crew_teams')->nullOnDelete();
            $table->foreignId('role_id')->constrained('crew_roles')->cascadeOnDelete();
            $table->string('custom_title')->nullable();
            $table->unique(['user_id', 'crew_id']);
        });

        Schema::create('crew_pages', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('type');
            $table->unsignedBigInteger('event_id')->index();
            $table->json('content')->nullable();
            $table->unique(['event_id', 'type']);
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('event_id')->index();
            $table->string('type')->default('forms');
            $table->boolean('enabled')->default(true);
            $table->boolean('private')->default(false);
            $table->string('name');
            $table->longText('short_description')->nullable();
            $table->json('long_description')->nullable();
            $table->string('icon')->default('task');
            $table->boolean('skippable')->default(false);
        });

        Schema::create('task_recipients', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->boolean('mandatory')->default(false);
            $table->boolean('approval_needed')->default(false);
            $table->string('recipient_type');
            $table->string('recipient_id');
            $table->json('filter')->nullable();
        });

        Schema::create('task_questions', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->boolean('optional')->default(false);
            $table->boolean('hidden')->default(false);
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('type');
            $table->json('options')->nullable();
        });

        Schema::create('task_responses', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->uuid('user_id')->index();
            $table->json('value')->nullable();
            $table->boolean('approved')->default(true);
            $table->boolean('completed')->default(false);
        });

        Schema::create('profile_images', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('profile_id')->index();
            $table->string('path')->nullable();
            $table->string('approval_state')->default('new');
            $table->json('crop')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_images');
        Schema::dropIfExists('task_responses');
        Schema::dropIfExists('task_questions');
        Schema::dropIfExists('task_recipients');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('crew_pages');
        Schema::dropIfExists('crew_users');
        Schema::dropIfExists('crew_teams');
        Schema::dropIfExists('crews');
        Schema::dropIfExists('crew_roles');
        Schema::dropIfExists('crew_events');
    }
};
