<?php

namespace App\Services\Contracts;

interface AIServiceInterface
{
    /**
     * Evaluate IELTS writing tasks
     *
     * @param  array<int, array{q: string, ans: string}>  $writingTasks
     * @return array{
     *     task1_ta: float,
     *     task1_cc: float,
     *     task1_lr: float,
     *     task1_gra: float,
     *     task1_feedback: string,
     *     task2_ta: float,
     *     task2_cc: float,
     *     task2_lr: float,
     *     task2_gra: float,
     *     task2_feedback: string,
     *     teacher_feedback: string
     * }
     */
    public function evaluateIELTSWriting(array $writingTasks): array;
}
