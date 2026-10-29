<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_templates', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('title');
            $table->text('content')->nullable();
            $table->json('schema')->nullable();
            $table->string('source_type')->default('database');
            $table->string('view_id')->nullable();
        });

        Schema::create('communication_notifications', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('name')->unique();
            $table->foreignId('mail_template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
            $table->foreignId('sms_template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
            $table->foreignId('slack_template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
        });

        Schema::create('communication_messages', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->timestamp('sent')->nullable();
            $table->string('state')->default('raw');
            $table->string('last_error')->nullable();
            $table->unsignedInteger('tries')->default(0);
            $table->string('channel');
            $table->string('notification_name')->nullable()->index();
            $table->json('recipient')->nullable();
            $table->json('data')->nullable();
            $table->json('source')->nullable();
        });

        Schema::create('communication_lists', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('identifier');
            $table->string('type')->default('mail');
            $table->string('summary');
            $table->unsignedBigInteger('event_id')->default(1)->index();
        });

        Schema::create('communication_list_owners', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('list_id')->constrained('communication_lists')->cascadeOnDelete();
            $table->string('owner_id');
            $table->string('owner_type');
        });

        Schema::create('communication_list_recipients', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('list_id')->constrained('communication_lists')->cascadeOnDelete();
            $table->string('recipient_id');
            $table->string('recipient_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_list_recipients');
        Schema::dropIfExists('communication_list_owners');
        Schema::dropIfExists('communication_lists');
        Schema::dropIfExists('communication_messages');
        Schema::dropIfExists('communication_notifications');
        Schema::dropIfExists('communication_templates');
    }
};
