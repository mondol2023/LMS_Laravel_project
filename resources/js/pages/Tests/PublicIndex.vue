<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';

interface Test {
    id: number;
    title: string;
    type: string;
    difficulty: string;
    average_score: number;
    completed_attempts: number;
    listening_sections: number;
    reading_passages: number;
    writing_tasks: number;
    speaking_parts: number;
}

interface Props {
    tests: Test[];
}

defineProps<Props>();

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
</script>

<template>
    <Head title="IELTS Practice Tests - Browse Available Tests" />

    <div class="min-h-screen bg-background">
        <!-- Navigation -->
        <nav class="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="text-2xl">📝</div>
                        <h1 class="text-xl font-bold">IELTS Mock Test</h1>
                    </Link>

                    <div class="flex items-center gap-4">
                        <Link href="/">
                            <Button variant="ghost">Home</Button>
                        </Link>
                        <Link href="/leaderboard">
                            <Button variant="ghost">Leaderboard</Button>
                        </Link>
                        <Link href="/login">
                            <Button variant="ghost">Log In</Button>
                        </Link>
                        <Link href="/register">
                            <Button>Get Started</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Header -->
        <section class="bg-muted/30 py-12">
            <div class="container mx-auto px-4 text-center">
                <h1 class="mb-4 text-4xl font-bold">IELTS Practice Tests</h1>
                <p class="mb-6 text-xl text-muted-foreground">Choose from our collection of realistic IELTS mock tests</p>
                <div class="flex justify-center gap-4">
                    <Link href="/register">
                        <Button size="lg"> Register to Start Practicing </Button>
                    </Link>
                    <Link href="/login">
                        <Button variant="outline" size="lg"> Already have an account? </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Tests Grid -->
        <section class="container mx-auto px-4 py-12">
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="test in tests" :key="test.id" class="transition-shadow hover:shadow-lg">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <CardTitle class="text-lg">{{ test.title }}</CardTitle>
                            <Badge variant="outline">{{ test.type.replace('_', ' ') }}</Badge>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="['text-sm font-medium', getDifficultyColor(test.difficulty)]"> {{ test.difficulty }} difficulty </span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <!-- Test Structure -->
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <span>🎧</span>
                                    <span>{{ test.listening_sections }} sections</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span>📖</span>
                                    <span>{{ test.reading_passages }} passages</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span>✍️</span>
                                    <span>{{ test.writing_tasks }} tasks</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span>🎤</span>
                                    <span>{{ test.speaking_parts }} parts</span>
                                </div>
                            </div>

                            <!-- Statistics -->
                            <div class="rounded-lg bg-muted/50 p-3">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="font-medium">Average Score</p>
                                        <p class="text-lg font-bold">{{ test.average_score.toFixed(1) }}</p>
                                    </div>
                                    <div>
                                        <p class="font-medium">Attempts</p>
                                        <p class="text-lg font-bold">{{ test.completed_attempts }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action -->
                            <div class="space-y-2">
                                <Link href="/register">
                                    <Button class="w-full"> Register to Take Test </Button>
                                </Link>
                                <p class="text-center text-xs text-muted-foreground">Free account required to practice</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-if="tests.length === 0" class="py-12 text-center">
                <h3 class="mb-2 text-lg font-medium text-muted-foreground">No tests available</h3>
                <p class="text-muted-foreground">Check back soon for new practice tests!</p>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="bg-primary py-16 text-primary-foreground">
            <div class="container mx-auto px-4 text-center">
                <h2 class="mb-4 text-3xl font-bold">Ready to Start Practicing?</h2>
                <p class="mb-8 text-xl opacity-90">Create your free account and take your first IELTS practice test</p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <Link href="/register">
                        <Button size="lg" variant="secondary" class="px-8 py-6 text-lg"> Create Free Account </Button>
                    </Link>
                    <Link href="/login">
                        <Button
                            size="lg"
                            variant="outline"
                            class="border-primary-foreground px-8 py-6 text-lg text-primary-foreground hover:bg-primary-foreground hover:text-primary"
                        >
                            Already Have Account?
                        </Button>
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>
