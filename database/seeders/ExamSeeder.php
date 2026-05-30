<?php

namespace Database\Seeders;

use App\Models\Exam;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed C20 Practice Tests (Test 1-4)
        $c20Tests = [
            ['exam_id' => 'Practice001', 'uuid' => 'practice001', 'name' => 'C20 Test 1 - IELTS Academic Practice'],
            ['exam_id' => 'Practice002', 'uuid' => 'practice002', 'name' => 'C20 Test 2 - IELTS Academic Practice'],
            ['exam_id' => 'Practice003', 'uuid' => 'practice003', 'name' => 'C20 Test 3 - IELTS Academic Practice'],
            ['exam_id' => 'Practice004', 'uuid' => 'practice004', 'name' => 'C20 Test 4 - IELTS Academic Practice'],
        ];

        foreach ($c20Tests as $test) {
            Exam::query()->firstOrCreate(
                ['uuid' => $test['uuid']],
                [
                    'exam_id' => $test['exam_id'],
                    'name' => $test['name'],
                    'description' => 'Cambridge IELTS 20 Academic Practice Test',
                    'type' => 'practice',
                ]
            );
        }

        // Seed C19 Practice Tests (Test 5-8)
        $c19Tests = [
            ['exam_id' => 'Practice005', 'uuid' => 'practice005', 'name' => 'C19 Test 1 - IELTS Academic Practice'],
            ['exam_id' => 'Practice006', 'uuid' => 'practice006', 'name' => 'C19 Test 2 - IELTS Academic Practice'],
            ['exam_id' => 'Practice007', 'uuid' => 'practice007', 'name' => 'C19 Test 3 - IELTS Academic Practice'],
            ['exam_id' => 'Practice008', 'uuid' => 'practice008', 'name' => 'C19 Test 4 - IELTS Academic Practice'],
        ];

        foreach ($c19Tests as $test) {
            Exam::query()->firstOrCreate(
                ['uuid' => $test['uuid']],
                [
                    'exam_id' => $test['exam_id'],
                    'name' => $test['name'],
                    'description' => 'Cambridge IELTS 19 Academic Practice Test',
                    'type' => 'practice',
                ]
            );
        }
    }
}
