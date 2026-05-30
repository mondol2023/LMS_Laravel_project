<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add temporary integer column
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->integer('duration_months')->nullable()->after('price');
        });

        // Convert existing date values to months
        // Calculate months from today to the duration date
        DB::statement('
            UPDATE subscription_packages
            SET duration_months = TIMESTAMPDIFF(MONTH, CURDATE(), duration)
            WHERE duration IS NOT NULL
        ');

        // Drop the old duration column
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn('duration');
        });

        // Rename duration_months to duration
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->renameColumn('duration_months', 'duration');
        });

        // Make duration not nullable
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->integer('duration')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add temporary date column
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->date('duration_date')->nullable()->after('price');
        });

        // Convert months back to dates
        DB::statement('
            UPDATE subscription_packages
            SET duration_date = DATE_ADD(CURDATE(), INTERVAL duration MONTH)
            WHERE duration IS NOT NULL
        ');

        // Drop the integer column
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn('duration');
        });

        // Rename duration_date to duration
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->renameColumn('duration_date', 'duration');
        });

        // Make duration not nullable
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->date('duration')->nullable(false)->change();
        });
    }
};
