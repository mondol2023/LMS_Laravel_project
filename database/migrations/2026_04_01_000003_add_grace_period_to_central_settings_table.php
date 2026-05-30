<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_settings', function (Blueprint $table) {
            $table->unsignedInteger('grace_period_days')->default(7)->after('status_reason');
            $table->unsignedInteger('auto_suspend_after_days')->default(15)->after('grace_period_days');
        });
    }

    public function down(): void
    {
        Schema::table('central_settings', function (Blueprint $table) {
            $table->dropColumn(['grace_period_days', 'auto_suspend_after_days']);
        });
    }
};
