<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

interface Test {
    id: number;
    title: string;
    type: string;
    difficulty: string;
    listening_sections: number;
    reading_passages: number;
    writing_tasks: number;
    speaking_parts: number;
}

interface UserAttempt {
    id: number;
    attempt_number: number;
    status: string;
    started_at: string;
    completed_at: string | null;
    overall_band: number | null;
    listening_score: number | null;
    reading_score: number | null;
    writing_score: number | null;
    speaking_score: number | null;
    rank: number | null;
}

interface Props {
    test: Test;
    userAttempts: UserAttempt[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tests', href: '/tests' },
    { title: props.test.title, href: `/tests/${props.test.id}` },
];

const getDifficultyColor = (difficulty: string) => {
    switch (difficulty) {
        case 'easy':
            return 'text-green-600';
        case 'medium':
            return 'text-yellow-600';
        case 'hard':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'completed':
            return 'default';
        case 'in_progress':
            return 'secondary';
        case 'abandoned':
            return 'destructive';
        default:
            return 'outline';
    }
};

const startTest = () => {
    router.post(`/tests/${props.test.id}/start`);
};

const hasInProgressAttempt = props.userAttempts.some((attempt) => attempt.status === 'in_progress');
const bestAttempt = props.userAttempts
    .filter((attempt) => attempt.overall_band !== null)
    .sort((a, b) => (b.overall_band || 0) - (a.overall_band || 0))[0];
</script>

<template>
    <Head :title="test.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Test Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ test.title }}</h1>
                    <div class="mt-2 flex items-center gap-3">
                        <Badge variant="outline">{{ test.type.replace('_', ' ') }}</Badge>
                        <span :class="['font-medium', getDifficultyColor(test.difficulty)]"> {{ test.difficulty }} difficulty </span>
                    </div>
                </div>
                <Button @click="startTest" size="lg" class="px-8">
                    {{ hasInProgressAttempt ? 'Resume Test' : 'Start New Attempt' }}
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Test Structure -->
                <Card>
                    <CardHeader>
                        <CardTitle>Test Structure</CardTitle>
                        <CardDescription>This test includes all four IELTS sections</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between rounded-lg border p-3">
                                <div>
                                    <p class="font-medium">Listening</p>
                                    <p class="text-sm text-muted-foreground">30 minutes + 10 minutes transfer</p>
                                </div>
                                <Badge variant="outline">{{ test.listening_sections }} sections</Badge>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border p-3">
                                <div>
                                    <p class="font-medium">Reading</p>
                                    <p class="text-sm text-muted-foreground">60 minutes</p>
                                </div>
                                <Badge variant="outline">{{ test.reading_passages }} passages</Badge>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border p-3">
                                <div>
                                    <p class="font-medium">Writing</p>
                                    <p class="text-sm text-muted-foreground">60 minutes</p>
                                </div>
                                <Badge variant="outline">{{ test.writing_tasks }} tasks</Badge>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border p-3">
                                <div>
                                    <p class="font-medium">Speaking</p>
                                    <p class="text-sm text-muted-foreground">11-14 minutes</p>
                                </div>
                                <Badge variant="outline">{{ test.speaking_parts }} parts</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Performance Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle>Your Performance</CardTitle>
                        <CardDescription> {{ userAttempts.length }} total attempt{{ userAttempts.length !== 1 ? 's' : '' }} </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="bestAttempt" class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Best Overall Band</p>
                                <div class="flex items-center gap-3">
                                    <p class="text-3xl font-bold">{{ bestAttempt.overall_band }}</p>
                                    <Badge v-if="bestAttempt.rank" variant="secondary"> Rank #{{ bestAttempt.rank }} </Badge>
                                </div>
                            </div>

                            <Separator />

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium">Listening</p>
                                    <p class="text-lg font-semibold">{{ bestAttempt.listening_score || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Reading</p>
                                    <p class="text-lg font-semibold">{{ bestAttempt.reading_score || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Writing</p>
                                    <p class="text-lg font-semibold">{{ bestAttempt.writing_score || 'Pending' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Speaking</p>
                                    <p class="text-lg font-semibold">{{ bestAttempt.speaking_score || 'Pending' }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-6 text-center">
                            <p class="text-muted-foreground">No completed attempts yet</p>
                            <p class="text-sm text-muted-foreground">Take your first test to see your performance</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Attempt History -->
            <Card>
                <CardHeader>
                    <CardTitle>Attempt History</CardTitle>
                    <CardDescription>View your previous test attempts</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="attempt in userAttempts" :key="attempt.id" class="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <p class="font-medium">Attempt #{{ attempt.attempt_number }}</p>
                                    <Badge :variant="getStatusBadgeVariant(attempt.status)">
                                        {{ attempt.status }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Started: {{ new Date(attempt.started_at).toLocaleDateString() }}
                                    <span v-if="attempt.completed_at"> • Completed: {{ new Date(attempt.completed_at).toLocaleDateString() }} </span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p v-if="attempt.overall_band" class="text-lg font-bold">
                                    {{ attempt.overall_band }}
                                </p>
                                <div class="mt-2 flex gap-2">
                                    <Link v-if="attempt.status === 'completed'" :href="`/tests/${attempt.id}/results`">
                                        <Button variant="outline" size="sm">View Results</Button>
                                    </Link>
                                    <Link v-if="attempt.status === 'in_progress'" :href="`/attempt/${attempt.id}`">
                                        <Button size="sm">Continue</Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div v-if="userAttempts.length === 0" class="py-6 text-center">
                            <p class="text-muted-foreground">No attempts yet</p>
                            <p class="text-sm text-muted-foreground">Start your first test to begin tracking your progress</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
