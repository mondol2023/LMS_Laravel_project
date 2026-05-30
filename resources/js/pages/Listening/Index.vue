<script setup lang="ts">
import AppFooter from '@/components/Common/AppFooter.vue';
import PracticeLayout from '@/layouts/PracticeLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Headphones, Layers, Lock, PlayCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

defineOptions({
    layout: PracticeLayout,
});

const props = defineProps({
    subscription: {
        type: Object,
        default: null,
    },
    accessError: {
        type: String,
        default: null,
    },
});

type ViewMode = 'cambridge' | 'topicwise';
const activeView = ref<ViewMode>('cambridge');

// --- Cambridge Section Data ---
const LISTENINGSections = [
    {
        level: 20,
        slug: 'C20',
        tests: [
            {
                id: 1,
                title: 'Test1',
                slug: 'practice001',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: false, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: false, part: 'part4' },
                ],
            },
            {
                id: 2,
                title: 'Test2',
                slug: 'practice002',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: false, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: false, part: 'part4' },
                ],
            },
            {
                id: 3,
                title: 'Test3',
                slug: 'practice003',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: false, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: false, part: 'part4' },
                ],
            },
            {
                id: 4,
                title: 'Test4',
                slug: 'practice004',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: false, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: false, part: 'part4' },
                ],
            },
        ],
    },
    {
        level: 19,
        slug: 'C19',
        tests: [
            {
                id: 5,
                title: 'Test1',
                slug: 'practice005',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: false, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: false, part: 'part4' },
                ],
            },
            {
                id: 6,
                title: 'Test2',
                slug: 'practice006',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: false, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: false, part: 'part4' },
                ],
            },
            {
                id: 7,
                title: 'Test3',
                slug: 'practice007',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: false, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: false, part: 'part4' },
                ],
            },
            {
                id: 8,
                title: 'Test4',
                slug: 'practice008',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: false, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: false, part: 'part4' },
                ],
            },
        ],
    },
    {
        level: 18,
        slug: 'C18',
        tests: [
            {
                id: 1,
                title: 'Test1',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: true, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: true, part: 'part4' },
                ],
            },
            {
                id: 2,
                title: 'Test2',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: true, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: true, part: 'part4' },
                ],
            },
            {
                id: 3,
                title: 'Test3',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: true, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: true, part: 'part4' },
                ],
            },
            {
                id: 4,
                title: 'Test4',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-10', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 11-20', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 21-30', locked: true, part: 'part3' },
                    { name: 'Part 4', description: 'Questions 31-40', locked: true, part: 'part4' },
                ],
            },
        ],
    },
];

// --- Topic Wise Section Data ---
const questionTypes = ['All', 'Multiple Choice', 'Matching', 'Map/Diagram Labelling', 'Completion', 'Sentence Completion', 'Short Answer'];
const activeQuestionType = ref('All');

interface TopicTask {
    id: number;
    name: string;
    type: string;
    source: string;
    testId: number;
    part: string;
    questions: string;
    description: string;
    tips: string[];
    locked: boolean;
}

const topicTasks: TopicTask[] = [
    // Multiple Choice Questions
    {
        id: 1,
        name: 'C20 Test1 Part2 - Multiple Choice',
        type: 'Multiple Choice',
        source: 'Cambridge 20',
        testId: 1,
        part: 'part2',
        questions: 'Q11-16',
        description: 'Choose the correct letter A, B or C for each question about a guided tour.',
        tips: [
            'Read all options before listening',
            'Listen for synonyms and paraphrasing',
            'Pay attention to qualifiers like "always", "never", "sometimes"',
        ],
        locked: false,
    },
    {
        id: 2,
        name: 'C20 Test2 Part2 - Multiple Choice',
        type: 'Multiple Choice',
        source: 'Cambridge 20',
        testId: 2,
        part: 'part2',
        questions: 'Q11-16',
        description: 'Choose the correct letter A, B or C for each question.',
        tips: ['Underline key words in the question', 'Eliminate obviously wrong answers', 'Be careful with distractors'],
        locked: false,
    },
    {
        id: 3,
        name: 'C19 Test1 Part3 - Multiple Choice',
        type: 'Multiple Choice',
        source: 'Cambridge 19',
        testId: 5,
        part: 'part3',
        questions: 'Q21-26',
        description: 'Choose the correct letter A, B or C for academic discussion questions.',
        tips: ["Focus on the speaker's opinion, not just facts", 'Listen for attitude and tone', 'Watch for changes of mind'],
        locked: false,
    },
    // Matching Questions
    {
        id: 4,
        name: 'C20 Test1 Part2 - Matching',
        type: 'Matching',
        source: 'Cambridge 20',
        testId: 1,
        part: 'part2',
        questions: 'Q17-20',
        description: 'Match each item with the correct option from the list.',
        tips: ['Read all items and options first', 'Cross out options as you use them', 'Some options may not be used'],
        locked: false,
    },
    {
        id: 5,
        name: 'C20 Test3 Part3 - Matching',
        type: 'Matching',
        source: 'Cambridge 20',
        testId: 3,
        part: 'part3',
        questions: 'Q27-30',
        description: 'Match the features with the correct category.',
        tips: ['Listen for specific keywords', 'Pay attention to the order of information', 'Options may be listed in a different order'],
        locked: false,
    },
    {
        id: 6,
        name: 'C19 Test2 Part3 - Matching',
        type: 'Matching',
        source: 'Cambridge 19',
        testId: 6,
        part: 'part3',
        questions: 'Q27-30',
        description: 'Match the research findings with the correct researcher.',
        tips: ['Focus on who says what', 'Listen for names before key information', 'Some names may be mentioned multiple times'],
        locked: false,
    },
    // Map/Diagram Labelling
    {
        id: 7,
        name: 'C20 Test2 Part2 - Map Labelling',
        type: 'Map/Diagram Labelling',
        source: 'Cambridge 20',
        testId: 2,
        part: 'part2',
        questions: 'Q17-20',
        description: 'Label the map with the correct places from the list.',
        tips: ['Study the map before listening', 'Note directions and landmarks', "Follow the speaker's route mentally"],
        locked: false,
    },
    {
        id: 8,
        name: 'C19 Test3 Part2 - Diagram Labelling',
        type: 'Map/Diagram Labelling',
        source: 'Cambridge 19',
        testId: 7,
        part: 'part2',
        questions: 'Q17-20',
        description: 'Label the diagram showing the process or structure.',
        tips: ['Understand the overall structure first', 'Listen for position words (above, below, next to)', 'Follow the logical sequence'],
        locked: false,
    },
    // Form/Note/Table/Summary Completion
    {
        id: 9,
        name: 'C20 Test1 Part1 - Form Completion',
        type: 'Completion',
        source: 'Cambridge 20',
        testId: 1,
        part: 'part1',
        questions: 'Q1-10',
        description: 'Complete the form with ONE WORD AND/OR A NUMBER for each answer.',
        tips: ['Check the word limit carefully', 'Predict the type of answer (name, number, date)', 'Watch spelling for proper nouns'],
        locked: false,
    },
    {
        id: 10,
        name: 'C20 Test3 Part1 - Note Completion',
        type: 'Completion',
        source: 'Cambridge 20',
        testId: 3,
        part: 'part1',
        questions: 'Q1-10',
        description: 'Complete the notes with NO MORE THAN TWO WORDS for each answer.',
        tips: ['Use the heading to understand context', 'Listen for stressed words', 'Grammar must fit the sentence'],
        locked: false,
    },
    {
        id: 11,
        name: 'C19 Test1 Part1 - Table Completion',
        type: 'Completion',
        source: 'Cambridge 19',
        testId: 5,
        part: 'part1',
        questions: 'Q1-10',
        description: 'Complete the table with ONE WORD AND/OR A NUMBER.',
        tips: ['Read column headers first', 'Information follows table order', 'Numbers may be spelled out'],
        locked: false,
    },
    {
        id: 12,
        name: 'C20 Test4 Part4 - Summary Completion',
        type: 'Completion',
        source: 'Cambridge 20',
        testId: 4,
        part: 'part4',
        questions: 'Q31-40',
        description: 'Complete the summary with ONE WORD ONLY for each answer.',
        tips: ['Read the whole summary first', 'Identify the topic and key ideas', 'Listen for paraphrased information'],
        locked: false,
    },
    // Sentence Completion
    {
        id: 13,
        name: 'C20 Test2 Part4 - Sentence Completion',
        type: 'Sentence Completion',
        source: 'Cambridge 20',
        testId: 2,
        part: 'part4',
        questions: 'Q31-40',
        description: 'Complete each sentence with NO MORE THAN TWO WORDS.',
        tips: ['Read the incomplete sentences first', 'Identify grammatical requirements', 'Listen for the exact wording or synonyms'],
        locked: false,
    },
    {
        id: 14,
        name: 'C19 Test2 Part4 - Sentence Completion',
        type: 'Sentence Completion',
        source: 'Cambridge 19',
        testId: 6,
        part: 'part4',
        questions: 'Q31-40',
        description: 'Complete each sentence with ONE WORD ONLY.',
        tips: ['Sentences follow lecture order', 'Focus on main ideas and examples', 'Check your answer fits grammatically'],
        locked: false,
    },
    // Short Answer Questions
    {
        id: 15,
        name: 'C20 Test1 Part3 - Short Answer',
        type: 'Short Answer',
        source: 'Cambridge 20',
        testId: 1,
        part: 'part3',
        questions: 'Q21-26',
        description: 'Answer the questions with NO MORE THAN THREE WORDS.',
        tips: ['Focus on question words (what, where, when)', 'Answer directly and concisely', "Don't add unnecessary words"],
        locked: false,
    },
    {
        id: 16,
        name: 'C19 Test4 Part3 - Short Answer',
        type: 'Short Answer',
        source: 'Cambridge 19',
        testId: 8,
        part: 'part3',
        questions: 'Q21-26',
        description: 'Answer the questions using words from the recording.',
        tips: ['Read all questions first', 'Identify key words to listen for', 'Write exact words from the recording'],
        locked: false,
    },
];

const filteredTasks = computed(() => {
    if (activeQuestionType.value === 'All') {
        return topicTasks;
    }
    return topicTasks.filter((task) => task.type === activeQuestionType.value);
});

const activeTask = ref<TopicTask | null>(filteredTasks.value[0] || null);

watch(filteredTasks, (newFilteredTasks) => {
    if (newFilteredTasks.length > 0) {
        activeTask.value = newFilteredTasks[0];
    } else {
        activeTask.value = null;
    }
});

const selectTask = (task: TopicTask) => {
    activeTask.value = task;
};

const startPractice = () => {
    if (!activeTask.value || activeTask.value.locked) return;
    router.visit(`/listening/test${activeTask.value.testId}/${activeTask.value.part}`);
};

const handlePartClick = (testId: number, part: string, locked: boolean) => {
    if (locked) return;
    router.visit(`/listening/test${testId}/${part}`);
};

const handleFullTestClick = (testSlug: string | undefined, locked: boolean) => {
    if (locked || !testSlug) return;
    router.visit(`/exam/${testSlug}/listening`);
};
</script>

<template>
    <Head title="LISTENING" />

    <!-- Access Blocked Warning -->
    <div v-if="accessError" class="min-h-screen bg-gray-50 p-8">
        <div class="mx-auto max-w-4xl">
            <div class="overflow-hidden border-2 border-red-200 bg-white shadow-xl">
                <!-- Warning Header -->
                <div class="bg-gradient-to-r from-red-500 to-orange-500 px-8 py-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white/20">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Access Restricted</h1>
                            <p class="mt-1 text-red-100">Listening Practice Module</p>
                        </div>
                    </div>
                </div>

                <!-- Warning Content -->
                <div class="p-8">
                    <div class="mb-6 rounded-xl border-2 border-red-200 bg-red-50 p-6">
                        <div class="flex items-start gap-4">
                            <svg class="mt-1 h-6 w-6 shrink-0 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <div>
                                <h3 class="mb-2 text-lg font-bold text-red-900">Unable to Access Listening Practice</h3>
                                <p class="text-red-800">{{ accessError }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription Status (if user is logged in) -->
                    <div v-if="subscription" class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-6">
                        <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-800">
                            <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Your Subscription Status
                        </h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-lg bg-white p-4">
                                <p class="text-sm text-gray-600">Listening Practice</p>
                                <p class="mt-1 text-lg font-bold" :class="subscription.practice_listening_enabled ? 'text-green-600' : 'text-red-600'">
                                    {{ subscription.practice_listening_enabled ? 'Enabled' : 'Not Available' }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-white p-4">
                                <p class="text-sm text-gray-600">Subscription Status</p>
                                <p class="mt-1 text-lg font-bold" :class="subscription.is_expired ? 'text-red-600' : 'text-green-600'">
                                    {{ subscription.is_expired ? 'Expired' : 'Active' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link
                            href="/student/dashboard"
                            class="flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-3 font-semibold text-white shadow-md transition-all hover:from-blue-600 hover:to-blue-700 hover:shadow-lg"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Go to Dashboard
                        </Link>
                        <Link
                            href="/practice"
                            class="flex items-center justify-center gap-2 rounded-lg border-2 border-gray-300 bg-white px-6 py-3 font-semibold text-gray-700 shadow-sm transition-all hover:border-gray-400 hover:bg-gray-50"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Browse Other Practices
                        </Link>
                    </div>

                    <!-- Contact Message -->
                    <div class="mt-6 rounded-lg bg-blue-50 p-4">
                        <p class="text-sm text-blue-800">
                            <strong>Need Help?</strong>
                            Contact your administrator to upgrade your subscription or enable this module.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Normal Content -->
    <div v-else class="bg-gray-100 p-4 sm:p-6 lg:p-8">
        <!-- View Mode Switcher -->
        <div class="mb-6 flex justify-center sm:mb-8">
            <div class="relative inline-flex rounded-xl bg-white p-1 shadow-lg sm:p-1.5">
                <!-- Sliding Background Indicator -->
                <div
                    class="absolute top-1 bottom-1 w-[calc(50%-4px)] rounded-lg bg-linear-to-r from-[#75609f] to-[#8b74b2] shadow-md transition-all duration-300 ease-out sm:top-1.5 sm:bottom-1.5 sm:w-[calc(50%-6px)]"
                    :class="activeView === 'cambridge' ? 'left-1 sm:left-1.5' : 'left-[calc(50%+2px)] sm:left-[calc(50%+3px)]'"
                ></div>

                <!-- Cambridge Tab -->
                <button
                    class="relative z-10 flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition-all duration-300 sm:gap-2 sm:px-6 sm:py-3 sm:text-sm"
                    :class="activeView === 'cambridge' ? 'text-white' : 'text-gray-600 hover:text-gray-900'"
                    @click="activeView = 'cambridge'"
                >
                    <BookOpen
                        class="h-3.5 w-3.5 transition-transform duration-300 sm:h-4 sm:w-4"
                        :class="activeView === 'cambridge' ? 'scale-110' : 'scale-100'"
                    />
                    <span>Cambridge</span>
                </button>

                <!-- Topic Wise Tab -->
                <button
                    class="relative z-10 flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition-all duration-300 sm:gap-2 sm:px-6 sm:py-3 sm:text-sm"
                    :class="activeView === 'topicwise' ? 'text-white' : 'text-gray-600 hover:text-gray-900'"
                    @click="activeView = 'topicwise'"
                >
                    <Layers
                        class="h-3.5 w-3.5 transition-transform duration-300 sm:h-4 sm:w-4"
                        :class="activeView === 'topicwise' ? 'scale-110' : 'scale-100'"
                    />
                    <span class="hidden sm:inline">Topic Wise</span>
                    <span class="sm:hidden">Topics</span>
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
            mode="out-in"
        >
            <!-- Cambridge Content -->
            <div v-if="activeView === 'cambridge'" key="cambridge">
                <div v-for="section in LISTENINGSections" :key="section.level" class="mb-8 sm:mb-12">
                    <!-- Section Header -->
                    <div class="mb-4 rounded-xl bg-gray-800 p-4 text-center sm:mb-6 lg:mb-0 lg:flex lg:h-32 lg:items-center lg:text-left">
                        <div class="flex flex-col items-center justify-center text-white lg:w-56 lg:shrink-0">
                            <h2 class="text-xl font-bold sm:text-2xl">LISTENING</h2>
                            <p class="text-base sm:text-lg">
                                ACADEMIC <span class="text-2xl font-bold text-[#75609f] sm:text-3xl">{{ section.level }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Test Cards Grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:-mt-20 lg:grid-cols-4 lg:pl-60">
                        <div v-for="test in section.tests" :key="test.id" class="rounded-lg bg-white shadow-md">
                            <div class="rounded-t-lg bg-[#75609f] p-2.5 text-white sm:p-3">
                                <button
                                    v-if="test.slug"
                                    class="inline-block cursor-pointer rounded-lg bg-white/20 p-1.5 text-sm font-bold shadow transition-all hover:bg-white/30 sm:text-base"
                                    @click="handleFullTestClick(test.slug, test.parts[0]?.locked ?? true)"
                                >
                                    {{ test.title }}
                                </button>
                                <h3 v-else class="inline-block rounded-lg p-1.5 text-sm font-bold shadow sm:text-base">{{ test.title }}</h3>
                            </div>
                            <div class="space-y-2 p-3 sm:space-y-3 sm:p-4">
                                <button
                                    v-for="part in test.parts"
                                    :key="part.name"
                                    class="flex w-full items-center rounded-md p-1 transition-all hover:bg-gray-50"
                                    :class="{ 'cursor-not-allowed opacity-60': part.locked, 'cursor-pointer': !part.locked }"
                                    :disabled="part.locked"
                                    @click="handlePartClick(test.id, part.part, part.locked)"
                                >
                                    <component
                                        :is="part.locked ? Lock : PlayCircle"
                                        class="mr-2 h-4 w-4 shrink-0 sm:mr-3 sm:h-5 sm:w-5"
                                        :class="part.locked ? 'text-yellow-500' : 'text-blue-500'"
                                    />
                                    <div class="flex w-full flex-col text-left">
                                        <p class="text-xs font-medium text-gray-800 sm:text-sm">{{ part.name }}</p>
                                        <p class="text-[10px] text-gray-500 sm:text-xs">{{ part.description }}</p>
                                        <hr class="my-1 w-full" />
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Topic Wise Content -->
            <div v-else key="topicwise">
                <!-- Question Type Filters -->
                <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700 sm:text-base">Question Types</span>
                        <button
                            v-for="type in questionTypes"
                            :key="type"
                            @click="activeQuestionType = type"
                            :class="[
                                'rounded-full px-3 py-1 text-xs font-medium transition-colors sm:px-4 sm:text-sm',
                                activeQuestionType === type ? 'bg-[#75609f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                            ]"
                        >
                            {{ type }}
                        </button>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <!-- Task List Sidebar -->
                    <div class="lg:col-span-4 xl:col-span-3">
                        <div class="rounded-lg bg-white shadow-sm">
                            <div class="border-b border-gray-100 p-3 sm:p-4">
                                <h3 class="text-sm font-semibold text-gray-800 sm:text-base">
                                    Practice Questions
                                    <span class="ml-2 rounded-full bg-[#75609f]/10 px-2 py-0.5 text-xs text-[#75609f]">
                                        {{ filteredTasks.length }}
                                    </span>
                                </h3>
                            </div>
                            <ul class="max-h-[500px] overflow-y-auto">
                                <li
                                    v-for="task in filteredTasks"
                                    :key="task.id"
                                    @click="selectTask(task)"
                                    :class="[
                                        'flex cursor-pointer items-center gap-3 border-b border-gray-50 p-3 transition-colors sm:p-4',
                                        activeTask && activeTask.id === task.id ? 'bg-[#75609f] text-white' : 'hover:bg-gray-50',
                                    ]"
                                >
                                    <Headphones
                                        class="h-4 w-4 shrink-0 sm:h-5 sm:w-5"
                                        :class="activeTask && activeTask.id === task.id ? 'text-white' : 'text-[#75609f]'"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-medium sm:text-sm">{{ task.name }}</p>
                                        <p
                                            class="truncate text-[10px] sm:text-xs"
                                            :class="activeTask && activeTask.id === task.id ? 'text-white/70' : 'text-gray-500'"
                                        >
                                            {{ task.type }} - {{ task.questions }}
                                        </p>
                                    </div>
                                    <Lock v-if="task.locked" class="h-4 w-4 shrink-0 text-yellow-500" />
                                </li>
                                <li v-if="filteredTasks.length === 0" class="p-4 text-center text-sm text-gray-500">
                                    No questions found for this type.
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Task Details -->
                    <div class="lg:col-span-8 xl:col-span-9">
                        <div v-if="activeTask" class="rounded-lg bg-white p-4 shadow-sm sm:p-6">
                            <!-- Task Header -->
                            <div class="mb-6 flex flex-col gap-4 border-b border-gray-100 pb-6 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-[#75609f]/10 px-3 py-1 text-xs font-medium text-[#75609f]">
                                            {{ activeTask.type }}
                                        </span>
                                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                            {{ activeTask.source }}
                                        </span>
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600">
                                            {{ activeTask.questions }}
                                        </span>
                                    </div>
                                    <h2 class="text-lg font-bold text-gray-800 sm:text-xl">{{ activeTask.name }}</h2>
                                </div>
                                <button
                                    @click="startPractice"
                                    :disabled="activeTask.locked"
                                    :class="[
                                        'flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors sm:px-6 sm:py-3',
                                        activeTask.locked
                                            ? 'cursor-not-allowed bg-gray-200 text-gray-500'
                                            : 'bg-[#75609f] text-white hover:bg-[#634f87]',
                                    ]"
                                >
                                    <PlayCircle class="h-4 w-4 sm:h-5 sm:w-5" />
                                    <span>Start Practice</span>
                                </button>
                            </div>

                            <!-- Task Description -->
                            <div class="mb-6">
                                <h3 class="mb-2 text-sm font-semibold text-gray-800 sm:text-base">Description</h3>
                                <p class="text-sm text-gray-600 sm:text-base">{{ activeTask.description }}</p>
                            </div>

                            <!-- Tips Section -->
                            <div class="rounded-lg bg-gradient-to-br from-[#75609f]/5 to-[#75609f]/10 p-4 sm:p-6">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800 sm:text-base">Tips for {{ activeTask.type }}</h3>
                                <ul class="space-y-2">
                                    <li v-for="(tip, index) in activeTask.tips" :key="index" class="flex items-start gap-2 text-sm text-gray-600">
                                        <span
                                            class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#75609f] text-xs font-bold text-white"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <span>{{ tip }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Question Type Info -->
                            <div class="mt-6 rounded-lg border border-gray-200 p-4 sm:p-6">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800 sm:text-base">About {{ activeTask.type }} Questions</h3>
                                <div class="text-sm text-gray-600">
                                    <template v-if="activeTask.type === 'Multiple Choice'">
                                        <p>
                                            Multiple choice questions test your ability to understand specific information, main ideas, opinions, or
                                            attitudes. You'll choose from options A, B, or C (sometimes D).
                                        </p>
                                    </template>
                                    <template v-else-if="activeTask.type === 'Matching'">
                                        <p>
                                            Matching questions require you to connect items from two lists. This tests your ability to recognize
                                            relationships and categorize information while listening.
                                        </p>
                                    </template>
                                    <template v-else-if="activeTask.type === 'Map/Diagram Labelling'">
                                        <p>
                                            These questions test your ability to understand descriptions of places or processes. You need to label a
                                            map, plan, or diagram based on what you hear.
                                        </p>
                                    </template>
                                    <template v-else-if="activeTask.type === 'Completion'">
                                        <p>
                                            Completion questions (form, note, table, or summary) require you to fill in missing information. Pay close
                                            attention to word limits and spelling.
                                        </p>
                                    </template>
                                    <template v-else-if="activeTask.type === 'Sentence Completion'">
                                        <p>
                                            Sentence completion tests your ability to identify key information and express it within word limits. Your
                                            answer must be grammatically correct in the sentence.
                                        </p>
                                    </template>
                                    <template v-else-if="activeTask.type === 'Short Answer'">
                                        <p>
                                            Short answer questions require brief, direct responses to specific questions. Focus on question words
                                            (what, where, when, who) and answer concisely.
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- No Task Selected -->
                        <div v-else class="flex min-h-[400px] items-center justify-center rounded-lg bg-white p-6 shadow-sm">
                            <div class="text-center">
                                <Headphones class="mx-auto mb-4 h-12 w-12 text-gray-300" />
                                <p class="text-gray-500">Select a question from the list to view details</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
    <!-- End of Normal Content -->

    <AppFooter />
</template>

<style scoped>
.bg-gray-800 {
    background-color: #1f2937;
}
.bg-white {
    background-color: white;
}
.bg-gray-100 {
    background-color: #f3f4f6;
}
.text-yellow-500 {
    color: #f59e0b;
}
.text-blue-500 {
    color: #3b82f6;
}
</style>
