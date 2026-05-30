import { ref } from 'vue';

export interface IeltsResult {
    totalQuestions: number;
    correctAnswers: number;
    incorrectAnswers: number;
    unanswered: number;
    rawScore: number;
    bandScore: number;
    percentage: number;
    userAnswers: Record<string, string>;
    correctAnswersMap?: Record<string, string>;
}

export function useIeltsScoring() {
    const results = ref<IeltsResult | null>(null);

    /**
     * Normalize answer for comparison
     * Handles punctuation, ordinals, units, word variations
     */
    const normalize = (str: string): string => {
        if (!str) return '';

        return (
            str
                .toLowerCase()
                .trim()
                // Remove all punctuation
                .replace(/[^\w\s]/g, ' ')
                // Handle ordinals (1st → 1, 2nd → 2, etc.)
                .replace(/(\d+)(st|nd|rd|th)/g, '$1')
                // Handle common unit variations
                .replace(/\bcm\b/g, 'centimeter')
                .replace(/\bmm\b/g, 'millimeter')
                .replace(/\bkg\b/g, 'kilogram')
                .replace(/\bgm?\b/g, 'gram')
                // Handle common word variations
                .replace(/\bfemale\b/g, 'women')
                .replace(/\bmale\b/g, 'men')
                // Handle boolean variations
                .replace(/\b(yes|y|correct|true)\b/g, 'yes')
                .replace(/\b(no|n|incorrect|false)\b/g, 'no')
                // Normalize whitespace
                .replace(/\s+/g, ' ')
                .trim()
        );
    };

    /**
     * Calculate IELTS band score from raw score
     * Based on the standard IELTS conversion table for Listening/Reading (40 questions)
     */
    const calculateBandScore = (score: number, totalQuestions: number = 40): number => {
        // For partial tests (e.g., only Part 1 with 10 questions), scale proportionally
        if (totalQuestions < 40) {
            const scaledScore = (score / totalQuestions) * 40;
            return calculateBandScore(Math.round(scaledScore), 40);
        }

        // Standard IELTS band score conversion table
        if (score >= 39) return 9.0;
        if (score >= 37) return 8.5;
        if (score >= 35) return 8.0;
        if (score >= 33) return 7.5;
        if (score >= 30) return 7.0;
        if (score >= 27) return 6.5;
        if (score >= 23) return 6.0;
        if (score >= 19) return 5.5;
        if (score >= 15) return 5.0;
        if (score >= 13) return 4.5;
        if (score >= 10) return 4.0;
        if (score >= 8) return 3.5;
        if (score >= 6) return 3.0;
        if (score >= 4) return 2.5;
        if (score >= 3) return 2.0;
        if (score >= 2) return 1.5;
        if (score >= 1) return 1.0;
        return 0;
    };

    /**
     * Compare user answers with correct answers
     * Handles multiple correct answers separated by "/"
     */
    const compareAnswers = (
        userAnswers: Record<string, string>,
        correctAnswers: Record<string, string>,
    ): { correct: number; incorrect: number; unanswered: number } => {
        let correct = 0;
        let incorrect = 0;
        let unanswered = 0;

        for (const [questionKey, correctAnswer] of Object.entries(correctAnswers)) {
            const userAnswer = userAnswers[questionKey];

            if (!userAnswer || userAnswer.trim() === '') {
                unanswered++;
                continue;
            }

            const normalizedUserAnswer = normalize(userAnswer);

            // Handle multiple correct answers (e.g., "A/E" means A or E is correct)
            if (correctAnswer.includes('/')) {
                const possibleAnswers = correctAnswer.split('/').map((ans) => normalize(ans));
                if (possibleAnswers.some((ans) => normalizedUserAnswer.includes(ans))) {
                    correct++;
                } else {
                    incorrect++;
                }
            } else {
                const normalizedCorrectAnswer = normalize(correctAnswer);
                if (normalizedUserAnswer.includes(normalizedCorrectAnswer)) {
                    correct++;
                } else {
                    incorrect++;
                }
            }
        }

        return { correct, incorrect, unanswered };
    };

    /**
     * Calculate results for a practice test
     * Does NOT save to database - on-demand calculation only
     */
    const calculateResults = (userAnswers: Record<string, string>, correctAnswers: Record<string, string>): IeltsResult => {
        const totalQuestions = Object.keys(correctAnswers).length;
        const { correct, incorrect, unanswered } = compareAnswers(userAnswers, correctAnswers);

        const bandScore = calculateBandScore(correct, totalQuestions);
        const percentage = totalQuestions > 0 ? (correct / totalQuestions) * 100 : 0;

        const result: IeltsResult = {
            totalQuestions,
            correctAnswers: correct,
            incorrectAnswers: incorrect,
            unanswered,
            rawScore: correct,
            bandScore,
            percentage: Math.round(percentage * 10) / 10,
            userAnswers,
            correctAnswersMap: correctAnswers,
        };

        results.value = result;
        console.log(result);
        return result;
    };

    /**
     * Get performance level based on band score
     */
    const getPerformanceLevel = (bandScore: number): string => {
        if (bandScore >= 8.0) return 'Excellent';
        if (bandScore >= 7.0) return 'Very Good';
        if (bandScore >= 6.0) return 'Good';
        if (bandScore >= 5.0) return 'Moderate';
        if (bandScore >= 4.0) return 'Limited';
        return 'Needs Improvement';
    };

    /**
     * Get color based on performance
     */
    const getPerformanceColor = (bandScore: number): string => {
        if (bandScore >= 8.0) return 'text-green-600';
        if (bandScore >= 7.0) return 'text-blue-600';
        if (bandScore >= 6.0) return 'text-indigo-600';
        if (bandScore >= 5.0) return 'text-yellow-600';
        if (bandScore >= 4.0) return 'text-orange-600';
        return 'text-red-600';
    };

    return {
        results,
        normalize,
        calculateBandScore,
        compareAnswers,
        calculateResults,
        getPerformanceLevel,
        getPerformanceColor,
    };
}
