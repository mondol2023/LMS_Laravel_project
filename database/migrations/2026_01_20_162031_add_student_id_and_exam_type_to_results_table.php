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
            $table->foreignId('student_id')->nullable()->after('exam_id')->constrained()->nullOnDelete();
            $table->string('exam_type')->default('full')->after('passport'); // 'full' or 'partial'
            $table->json('modules')->nullable()->after('exam_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['student_id', 'exam_type', 'modules']);
        });
    }
};
