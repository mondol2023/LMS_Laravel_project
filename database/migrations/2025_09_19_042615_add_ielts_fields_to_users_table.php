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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('passport', 50)->nullable()->after('phone');
            $table->string('country', 50)->nullable()->after('passport');
            $table->decimal('target_band', 2, 1)->nullable()->after('country');
            $table->enum('role', ['student', 'admin','super_admin'])->default('student')->after('target_band');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'passport', 'country', 'target_band', 'role']);
        });
    }
};
