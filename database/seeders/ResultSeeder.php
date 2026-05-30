<?php

namespace Database\Seeders;

use App\Models\Result;
use Illuminate\Database\Seeder;

class ResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $results = [
            [
                'username' => 'John Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1-555-0123',
                'passport' => 'A12345678',
                'data' => [
                    'overall_score' => 85,
                    'band_score' => 7.5,
                    'listening_score' => 8.0,
                    'reading_score' => 7.5,
                    'writing_score' => 7.0,
                    'speaking_score' => 8.5,
                    'exam_type' => 'IELTS Academic',
                    'test_date' => '2024-09-15',
                    'test_center' => 'London Test Center',
                    'duration_minutes' => 175,
                ],
            ],
            [
                'username' => 'Maria Garcia',
                'email' => 'maria.garcia@example.com',
                'phone' => '+34-666-123456',
                'passport' => 'ESP987654',
                'data' => [
                    'overall_score' => 92,
                    'band_score' => 8.5,
                    'listening_score' => 9.0,
                    'reading_score' => 8.5,
                    'writing_score' => 8.0,
                    'speaking_score' => 9.0,
                    'exam_type' => 'IELTS Academic',
                    'test_date' => '2024-09-10',
                    'test_center' => 'Madrid Test Center',
                    'duration_minutes' => 168,
                ],
            ],
            [
                'username' => 'Chen Wei',
                'email' => 'chen.wei@example.com',
                'phone' => '+86-138-0013-8000',
                'passport' => 'CHN246810',
                'data' => [
                    'overall_score' => 78,
                    'band_score' => 6.5,
                    'listening_score' => 7.0,
                    'reading_score' => 8.0,
                    'writing_score' => 6.0,
                    'speaking_score' => 6.5,
                    'exam_type' => 'IELTS General',
                    'test_date' => '2024-09-08',
                    'test_center' => 'Beijing Test Center',
                    'duration_minutes' => 182,
                ],
            ],
            [
                'username' => 'Ahmed Hassan',
                'email' => 'ahmed.hassan@example.com',
                'phone' => '+20-10-1234-5678',
                'passport' => null,
                'data' => [
                    'overall_score' => 88,
                    'band_score' => 8.0,
                    'listening_score' => 8.5,
                    'reading_score' => 8.0,
                    'writing_score' => 7.5,
                    'speaking_score' => 8.5,
                    'exam_type' => 'IELTS Academic',
                    'test_date' => '2024-09-12',
                    'test_center' => 'Cairo Test Center',
                    'duration_minutes' => 171,
                ],
            ],
            [
                'username' => 'Sarah Johnson',
                'email' => 'sarah.johnson@example.com',
                'phone' => null,
                'passport' => 'CAN135792',
                'data' => [
                    'overall_score' => 95,
                    'band_score' => 9.0,
                    'listening_score' => 9.0,
                    'reading_score' => 9.0,
                    'writing_score' => 8.5,
                    'speaking_score' => 9.5,
                    'exam_type' => 'IELTS Academic',
                    'test_date' => '2024-09-18',
                    'test_center' => 'Toronto Test Center',
                    'duration_minutes' => 165,
                ],
            ],
            [
                'username' => 'Raj Patel',
                'email' => 'raj.patel@example.com',
                'phone' => '+91-98765-43210',
                'passport' => 'IND567890',
                'data' => [
                    'overall_score' => 82,
                    'band_score' => 7.0,
                    'listening_score' => 7.5,
                    'reading_score' => 8.0,
                    'writing_score' => 6.5,
                    'speaking_score' => 7.5,
                    'exam_type' => 'IELTS General',
                    'test_date' => '2024-09-05',
                    'test_center' => 'Mumbai Test Center',
                    'duration_minutes' => 178,
                ],
            ],
            [
                'username' => 'Emma Wilson',
                'email' => 'emma.wilson@example.com',
                'phone' => '+61-4-1234-5678',
                'passport' => 'AUS456123',
                'data' => [
                    'overall_score' => 90,
                    'band_score' => 8.0,
                    'listening_score' => 8.5,
                    'reading_score' => 8.5,
                    'writing_score' => 7.5,
                    'speaking_score' => 8.5,
                    'exam_type' => 'IELTS Academic',
                    'test_date' => '2024-09-20',
                    'test_center' => 'Sydney Test Center',
                    'duration_minutes' => 173,
                ],
            ],
            [
                'username' => 'Pierre Dubois',
                'email' => 'pierre.dubois@example.com',
                'phone' => '+33-6-12-34-56-78',
                'passport' => 'FRA789012',
                'data' => [
                    'overall_score' => 76,
                    'band_score' => 6.5,
                    'listening_score' => 6.5,
                    'reading_score' => 7.5,
                    'writing_score' => 6.0,
                    'speaking_score' => 7.0,
                    'exam_type' => 'IELTS General',
                    'test_date' => '2024-09-14',
                    'test_center' => 'Paris Test Center',
                    'duration_minutes' => 185,
                ],
            ],
            [
                'username' => 'Yuki Tanaka',
                'email' => 'yuki.tanaka@example.com',
                'phone' => '+81-90-1234-5678',
                'passport' => 'JPN654321',
                'data' => [
                    'overall_score' => 87,
                    'band_score' => 7.5,
                    'listening_score' => 8.0,
                    'reading_score' => 8.5,
                    'writing_score' => 7.0,
                    'speaking_score' => 7.5,
                    'exam_type' => 'IELTS Academic',
                    'test_date' => '2024-09-16',
                    'test_center' => 'Tokyo Test Center',
                    'duration_minutes' => 169,
                ],
            ],
            [
                'username' => 'Lars Anderson',
                'email' => 'lars.anderson@example.com',
                'phone' => '+46-70-123-4567',
                'passport' => 'SWE321654',
                'data' => [
                    'overall_score' => 93,
                    'band_score' => 8.5,
                    'listening_score' => 9.0,
                    'reading_score' => 9.0,
                    'writing_score' => 8.0,
                    'speaking_score' => 9.0,
                    'exam_type' => 'IELTS Academic',
                    'test_date' => '2024-09-11',
                    'test_center' => 'Stockholm Test Center',
                    'duration_minutes' => 166,
                ],
            ],
        ];

        foreach ($results as $resultData) {
            Result::create($resultData);
        }
    }
}
