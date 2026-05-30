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
        Schema::table('subscription_packages', function (Blueprint $table) {
            // Add temporary column
            $table->date('duration_date')->nullable()->after('price');
        });

        // Convert existing integer duration to dates (duration was in days)
        DB::statement('UPDATE subscription_packages SET duration_date = DATE_ADD(CURDATE(), INTERVAL duration DAY) WHERE duration IS NOT NULL');

        Schema::table('subscription_packages', function (Blueprint $table) {
            // Drop old duration column
            $table->dropColumn('duration');
        });

        Schema::table('subscription_packages', function (Blueprint $table) {
            // Rename new column to duration
            $table->renameColumn('duration_date', 'duration');
        });

        Schema::table('subscription_packages', function (Blueprint $table) {
            // Make duration required
            $table->date('duration')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            // Add temporary integer column
            $table->integer('duration_int')->nullable();
        });

        // Convert dates back to integer (approximate days from now)
        DB::statement('UPDATE subscription_packages SET duration_int = DATEDIFF(duration, CURDATE()) WHERE duration IS NOT NULL');

        Schema::table('subscription_packages', function (Blueprint $table) {
            // Drop date column
            $table->dropColumn('duration');
        });

        Schema::table('subscription_packages', function (Blueprint $table) {
            // Rename back to duration
            $table->renameColumn('duration_int', 'duration');
        });
    }
};
