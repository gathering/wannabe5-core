<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_images', function (Blueprint $table): void {
            $table->string('decline_reason')->nullable()->after('approval_state');
        });
    }

    public function down(): void
    {
        Schema::table('profile_images', function (Blueprint $table): void {
            $table->dropColumn('decline_reason');
        });
    }
};
