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
        Schema::table('results', function (Blueprint $table) {
            if (!Schema::hasColumn('results', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('exam_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('results', 'exam_type')) {
                $table->string('exam_type')->default('full')->after('passport');
            }
            if (!Schema::hasColumn('results', 'modules')) {
                $table->json('modules')->nullable()->after('exam_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            if (Schema::hasColumn('results', 'student_id')) {
                $table->dropForeign(['student_id']);
                $table->dropColumn('student_id');
            }
            if (Schema::hasColumn('results', 'exam_type')) {
                $table->dropColumn('exam_type');
            }
            if (Schema::hasColumn('results', 'modules')) {
                $table->dropColumn('modules');
            }
        });
    }
};
