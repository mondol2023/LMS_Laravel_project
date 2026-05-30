<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface TestAttempt {
    id: number;
    test_title: string;
    completed_at: string;
    listening_score: number | null;
    reading_score: number | null;
    writing_score: number | null;
    speaking_score: number | null;
    overall_band: number | null;
    time_spent: Record<string, number>;
    total_time: number;
}

interface UserAnswer {
    section: string;
    question_number: number;
    user_answer: string;
    is_correct: boolean | null;
    points_earned: number;
}

interface Props {
    attempt: TestAttempt;
    userAnswers: Record<string, UserAnswer[]>;
    rank: number | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tests', href: '/tests' },
    { title: 'Results', href: '#' },
];

const formatTime = (seconds: number) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    }
    return `${minutes}m ${secs}s`;
};

const getBandColor = (score: number | null) => {
    if (!score) return 'text-muted-foreground';
    if (score >= 8) return 'text-green-600';
    if (score >= 6.5) return 'text-blue-600';
    if (score >= 5.5) return 'text-yellow-600';
    return 'text-red-600';
};

const getBandDescription = (score: number | null) => {
    if (!score) return 'Not available';
    if (score >= 8.5) return 'Very Good User';
    if (score >= 7.5) return 'Good User';
    if (score >= 6.5) return 'Competent User';
    if (score >= 5.5) return 'Modest User';
    if (score >= 4.5) return 'Limited User';
    return 'Extremely Limited User';
};

const getScorePercentage = (score: number | null) => {
    if (!score) return 0;
    return (score / 9) * 100;
};

const getSectionStats = (section: string) => {
    const answers = props.userAnswers[section] || [];
    const gradedAnswers = answers.filter((a) => a.is_correct !== null);
    const correctAnswers = answers.filter((a) => a.is_correct === true).length;

    return {
        total: answers.length,
        correct: correctAnswers,
        accuracy: gradedAnswers.length > 0 ? (correctAnswers / gradedAnswers.length) * 100 : 0,
    };
};
</script>

<template>
    <Head title="Test Results" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Results Header -->
            <div class="space-y-4 text-center">
                <h1 class="text-3xl font-bold">Test Results</h1>
                <p class="text-muted-foreground">{{ attempt.test_title }}</p>
                <p class="text-sm text-muted-foreground">Completed on {{ new Date(attempt.completed_at).toLocaleDateString() }}</p>
            </div>

            <!-- Overall Score -->
            <Card class="mx-auto max-w-md">
                <CardHeader class="text-center">
                    <CardTitle>Overall Band Score</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4 text-center">
                    <div :class="['text-6xl font-bold', getBandColor(attempt.overall_band)]">
                        {{ attempt.overall_band || 'N/A' }}
                    </div>
                    <p class="text-lg font-medium">{{ getBandDescription(attempt.overall_band) }}</p>
                    <div v-if="rank" class="flex items-center justify-center gap-2">
                        <Badge variant="secondary">Rank #{{ rank }}</Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- Section Scores -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="text-center">
                        <CardTitle class="text-lg">🎧 Listening</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-center">
                        <div :class="['text-3xl font-bold', getBandColor(attempt.listening_score)]">
                            {{ attempt.listening_score || 'N/A' }}
                        </div>
                        <Progress :value="getScorePercentage(attempt.listening_score)" class="mx-auto w-20" />
                        <div class="text-sm text-muted-foreground">
                            <p>{{ getSectionStats('listening').correct }}/{{ getSectionStats('listening').total }} correct</p>
                            <p>{{ getSectionStats('listening').accuracy.toFixed(1) }}% accuracy</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="text-center">
                        <CardTitle class="text-lg">📖 Reading</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-center">
                        <div :class="['text-3xl font-bold', getBandColor(attempt.reading_score)]">
                            {{ attempt.reading_score || 'N/A' }}
                        </div>
                        <Progress :value="getScorePercentage(attempt.reading_score)" class="mx-auto w-20" />
                        <div class="text-sm text-muted-foreground">
                            <p>{{ getSectionStats('reading').correct }}/{{ getSectionStats('reading').total }} correct</p>
                            <p>{{ getSectionStats('reading').accuracy.toFixed(1) }}% accuracy</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="text-center">
                        <CardTitle class="text-lg">✍️ Writing</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-center">
                        <div :class="['text-3xl font-bold', getBandColor(attempt.writing_score)]">
                            {{ attempt.writing_score || 'Pending' }}
                        </div>
                        <Progress :value="getScorePercentage(attempt.writing_score)" class="mx-auto w-20" />
                        <div class="text-sm text-muted-foreground">
                            <p v-if="!attempt.writing_score">Manual review required</p>
                            <p v-else>Manually assessed</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="text-center">
                        <CardTitle class="text-lg">🎤 Speaking</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-center">
                        <div :class="['text-3xl font-bold', getBandColor(attempt.speaking_score)]">
                            {{ attempt.speaking_score || 'Pending' }}
                        </div>
                        <Progress :value="getScorePercentage(attempt.speaking_score)" class="mx-auto w-20" />
                        <div class="text-sm text-muted-foreground">
                            <p v-if="!attempt.speaking_score">Manual review required</p>
                            <p v-else>Manually assessed</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Time Analysis -->
            <Card>
                <CardHeader>
                    <CardTitle>Time Analysis</CardTitle>
                    <CardDescription>How you spent your time during the test</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="text-center">
                            <p class="font-medium">Listening</p>
                            <p class="text-2xl font-bold">{{ formatTime(attempt.time_spent.listening || 0) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="font-medium">Reading</p>
                            <p class="text-2xl font-bold">{{ formatTime(attempt.time_spent.reading || 0) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="font-medium">Writing</p>
                            <p class="text-2xl font-bold">{{ formatTime(attempt.time_spent.writing || 0) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="font-medium">Speaking</p>
                            <p class="text-2xl font-bold">{{ formatTime(attempt.time_spent.speaking || 0) }}</p>
                        </div>
                    </div>

                    <Separator class="my-4" />

                    <div class="text-center">
                        <p class="font-medium text-muted-foreground">Total Time</p>
                        <p class="text-3xl font-bold">{{ formatTime(attempt.total_time) }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Answer Review (for auto-graded sections) -->
            <div class="grid gap-6 md:grid-cols-2">
                <Card v-if="userAnswers.listening">
                    <CardHeader>
                        <CardTitle>Listening Answers</CardTitle>
                        <CardDescription>Review your listening responses</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="max-h-60 space-y-2 overflow-y-auto">
                            <div
                                v-for="answer in userAnswers.listening"
                                :key="answer.question_number"
                                class="flex items-center justify-between rounded border p-2"
                            >
                                <span class="font-medium">Q{{ answer.question_number }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">{{ answer.user_answer }}</span>
                                    <Badge v-if="answer.is_correct !== null" :variant="answer.is_correct ? 'default' : 'destructive'">
                                        {{ answer.is_correct ? '✓' : '✗' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="userAnswers.reading">
                    <CardHeader>
                        <CardTitle>Reading Answers</CardTitle>
                        <CardDescription>Review your reading responses</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="max-h-60 space-y-2 overflow-y-auto">
                            <div
                                v-for="answer in userAnswers.reading"
                                :key="answer.question_number"
                                class="flex items-center justify-between rounded border p-2"
                            >
                                <span class="font-medium">Q{{ answer.question_number }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">{{ answer.user_answer }}</span>
                                    <Badge v-if="answer.is_correct !== null" :variant="answer.is_correct ? 'default' : 'destructive'">
                                        {{ answer.is_correct ? '✓' : '✗' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Actions -->
            <div class="flex justify-center gap-4">
                <Link href="/tests">
                    <Button variant="outline">Back to Tests</Button>
                </Link>
                <Link href="/leaderboard">
                    <Button>View Leaderboard</Button>
                </Link>
                <Link href="/dashboard">
                    <Button variant="secondary">Dashboard</Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
