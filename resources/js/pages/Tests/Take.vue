<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface TestAttempt {
    id: number;
    status: string;
    started_at: string;
    time_spent: Record<string, number>;
}

interface Test {
    id: number;
    title: string;
    type: string;
    listening_content: any;
    reading_content: any;
    writing_tasks: any;
    speaking_tasks: any;
}

interface Props {
    attempt: TestAttempt;
    test: Test;
    userAnswers: Record<string, any>;
}

const props = defineProps<Props>();

// State management
const currentSection = ref('listening');
const currentQuestionIndex = ref(0);
const sectionTimer = ref(0);
const isPaused = ref(false);
const showInstructions = ref(true);

// Section configurations
const sectionConfig = {
    listening: {
        duration: 2400, // 40 minutes (30 + 10 transfer)
        name: 'Listening',
        icon: '🎧',
    },
    reading: {
        duration: 3600, // 60 minutes
        name: 'Reading',
        icon: '📖',
    },
    writing: {
        duration: 3600, // 60 minutes
        name: 'Writing',
        icon: '✍️',
    },
    speaking: {
        duration: 900, // 15 minutes
        name: 'Speaking',
        icon: '🎤',
    },
};

// Timer functionality
let timerInterval: NodeJS.Timeout | null = null;

const startTimer = () => {
    if (timerInterval) clearInterval(timerInterval);

    timerInterval = setInterval(() => {
        if (!isPaused.value) {
            sectionTimer.value++;

            // Update time spent on server every 30 seconds
            if (sectionTimer.value % 30 === 0) {
                updateTimeSpent();
            }

            // Auto-submit when time runs out
            if (sectionTimer.value >= sectionConfig[currentSection.value as keyof typeof sectionConfig].duration) {
                handleSectionComplete();
            }
        }
    }, 1000);
};

const formatTime = (seconds: number) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
};

const timeRemaining = computed(() => {
    const config = sectionConfig[currentSection.value as keyof typeof sectionConfig];
    return Math.max(0, config.duration - sectionTimer.value);
});

const timeProgress = computed(() => {
    const config = sectionConfig[currentSection.value as keyof typeof sectionConfig];
    return (sectionTimer.value / config.duration) * 100;
});

// Section management
const sections = ['listening', 'reading', 'writing', 'speaking'];
const currentSectionIndex = computed(() => sections.indexOf(currentSection.value));

const canNavigateToSection = (section: string) => {
    const sectionIndex = sections.indexOf(section);
    const currentIndex = currentSectionIndex.value;
    return sectionIndex <= currentIndex + 1; // Can only go to current or next section
};

const switchToSection = (section: string) => {
    if (!canNavigateToSection(section)) return;

    // Save current section progress
    if (currentSection.value !== section) {
        updateTimeSpent();
        submitCurrentSection();
    }

    currentSection.value = section;
    sectionTimer.value = props.attempt.time_spent[section] || 0;
    currentQuestionIndex.value = 0;
    showInstructions.value = true;
};

const handleSectionComplete = () => {
    submitCurrentSection();

    const nextSectionIndex = currentSectionIndex.value + 1;
    if (nextSectionIndex < sections.length) {
        switchToSection(sections[nextSectionIndex]);
    } else {
        submitTest();
    }
};

// API calls
const updateTimeSpent = () => {
    router.post(
        `/attempt/${props.attempt.id}/time`,
        {
            section: currentSection.value,
            time: sectionTimer.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const submitCurrentSection = () => {
    router.post(
        `/attempt/${props.attempt.id}/submit-section`,
        {
            section: currentSection.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const submitTest = () => {
    if (timerInterval) clearInterval(timerInterval);
    router.post(`/attempt/${props.attempt.id}/submit`);
};

// Question navigation
const getCurrentSectionQuestions = () => {
    switch (currentSection.value) {
        case 'listening':
            return props.test.listening_content?.sections?.flatMap((s: any) => s.questions) || [];
        case 'reading':
            return props.test.reading_content?.passages?.flatMap((p: any) => p.questions) || [];
        case 'writing':
            return [props.test.writing_tasks?.task1, props.test.writing_tasks?.task2].filter(Boolean);
        case 'speaking':
            return [props.test.speaking_tasks?.part1, props.test.speaking_tasks?.part2, props.test.speaking_tasks?.part3].filter(Boolean);
        default:
            return [];
    }
};

const currentQuestions = computed(() => getCurrentSectionQuestions());
const currentQuestion = computed(() => currentQuestions.value[currentQuestionIndex.value]);

const navigateToQuestion = (index: number) => {
    if (index >= 0 && index < currentQuestions.value.length) {
        currentQuestionIndex.value = index;
    }
};

// Lifecycle
onMounted(() => {
    sectionTimer.value = props.attempt.time_spent[currentSection.value] || 0;
    startTimer();
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    updateTimeSpent();
});

// Emergency handlers
const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    e.preventDefault();
    updateTimeSpent();
    return 'Are you sure you want to leave? Your progress will be saved.';
};

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

<template>
    <Head :title="`${test.title} - ${sectionConfig[currentSection].name}`" />

    <div class="min-h-screen bg-background">
        <!-- Test Header -->
        <div class="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container flex h-16 items-center justify-between">
                <div class="flex items-center gap-4">
                    <h1 class="text-lg font-semibold">{{ test.title }}</h1>
                    <Badge variant="outline">{{ test.type.replace('_', ' ') }}</Badge>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Timer -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">Time Remaining:</span>
                        <div class="flex items-center gap-2">
                            <span :class="['font-mono text-lg font-bold', timeRemaining < 300 ? 'text-red-600' : 'text-foreground']">
                                {{ formatTime(timeRemaining) }}
                            </span>
                            <Progress :value="timeProgress" class="w-24" />
                        </div>
                    </div>

                    <!-- Section Navigation -->
                    <div class="flex items-center gap-2">
                        <Button
                            v-for="section in sections"
                            :key="section"
                            :variant="currentSection === section ? 'default' : 'outline'"
                            :disabled="!canNavigateToSection(section)"
                            @click="switchToSection(section)"
                            size="sm"
                        >
                            {{ sectionConfig[section].icon }} {{ sectionConfig[section].name }}
                        </Button>
                    </div>

                    <!-- Emergency Exit -->
                    <Button @click="submitTest" variant="destructive" size="sm"> Submit Test </Button>
                </div>
            </div>
        </div>

        <!-- Instructions Modal -->
        <div v-if="showInstructions" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="w-full max-w-2xl">
                <CardHeader>
                    <CardTitle> {{ sectionConfig[currentSection].icon }} {{ sectionConfig[currentSection].name }} Section </CardTitle>
                    <CardDescription> Please read the instructions carefully before beginning </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-if="currentSection === 'listening'">
                            <p>You will hear a number of different recordings and you will have to answer questions on what you hear.</p>
                            <p>There are four sections to this test. You will have time to read the instructions and questions.</p>
                            <p><strong>Time allowed: 30 minutes + 10 minutes transfer time</strong></p>
                        </div>
                        <div v-if="currentSection === 'reading'">
                            <p>You should spend about 60 minutes on this task.</p>
                            <p>There are three reading passages with questions. Answer all questions.</p>
                            <p><strong>Time allowed: 60 minutes</strong></p>
                        </div>
                        <div v-if="currentSection === 'writing'">
                            <p>This section has two tasks. You must complete both tasks.</p>
                            <p>Task 1: Spend about 20 minutes (150+ words)</p>
                            <p>Task 2: Spend about 40 minutes (250+ words)</p>
                            <p><strong>Time allowed: 60 minutes</strong></p>
                        </div>
                        <div v-if="currentSection === 'speaking'">
                            <p>The speaking test consists of three parts.</p>
                            <p>You will be recorded throughout the test.</p>
                            <p><strong>Time allowed: 11-14 minutes</strong></p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button @click="showInstructions = false"> Begin {{ sectionConfig[currentSection].name }} </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Main Test Content -->
        <div v-if="!showInstructions" class="container py-6">
            <div class="grid gap-6 lg:grid-cols-4">
                <!-- Question Navigator -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="text-base">Questions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-5 gap-2">
                            <Button
                                v-for="(question, index) in currentQuestions"
                                :key="index"
                                :variant="currentQuestionIndex === index ? 'default' : 'outline'"
                                @click="navigateToQuestion(index)"
                                size="sm"
                                class="aspect-square p-0"
                            >
                                {{ index + 1 }}
                            </Button>
                        </div>

                        <Separator class="my-4" />

                        <div class="space-y-2">
                            <Button
                                @click="navigateToQuestion(Math.max(0, currentQuestionIndex - 1))"
                                :disabled="currentQuestionIndex === 0"
                                variant="outline"
                                size="sm"
                                class="w-full"
                            >
                                Previous
                            </Button>
                            <Button
                                @click="navigateToQuestion(Math.min(currentQuestions.length - 1, currentQuestionIndex + 1))"
                                :disabled="currentQuestionIndex === currentQuestions.length - 1"
                                variant="outline"
                                size="sm"
                                class="w-full"
                            >
                                Next
                            </Button>
                        </div>

                        <Separator class="my-4" />

                        <Button @click="handleSectionComplete" class="w-full" variant="secondary">
                            Complete {{ sectionConfig[currentSection].name }}
                        </Button>
                    </CardContent>
                </Card>

                <!-- Question Content -->
                <Card class="lg:col-span-3">
                    <CardHeader>
                        <CardTitle> {{ sectionConfig[currentSection].name }} - Question {{ currentQuestionIndex + 1 }} </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="min-h-[600px]">
                            <!-- This will be replaced with section-specific components -->
                            <div class="space-y-6">
                                <div class="rounded-lg border bg-muted/50 p-6">
                                    <p class="text-center text-muted-foreground">
                                        {{ sectionConfig[currentSection].name }} question content will be displayed here
                                    </p>
                                    <p class="mt-2 text-center text-sm text-muted-foreground">
                                        Question {{ currentQuestionIndex + 1 }} of {{ currentQuestions.length }}
                                    </p>
                                </div>

                                <!-- Answer area placeholder -->
                                <div class="space-y-4">
                                    <label class="text-sm font-medium">Your Answer:</label>
                                    <textarea
                                        class="min-h-[200px] w-full resize-none rounded-lg border p-3"
                                        placeholder="Enter your answer here..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
