<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_responses', function (Blueprint $table): void {
            $table->boolean('skipped')->default(false)->after('completed');
        });
    }

    public function down(): void
    {
        Schema::table('task_responses', function (Blueprint $table): void {
            $table->dropColumn('skipped');
        });
    }
};
