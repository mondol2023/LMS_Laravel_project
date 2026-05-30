<script setup lang="ts">
import AppFooter from '@/components/Common/AppFooter.vue';
import PracticeLayout from '@/layouts/PracticeLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, FileText, Layers, Lock, PlayCircle } from 'lucide-vue-next';
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
const readingSections = [
    {
        level: 20,
        slug: 'C20',
        tests: [
            {
                id: 1,
                title: 'Test1',
                slug: 'practice001',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: false, part: 'part3' },
                ],
            },
            {
                id: 2,
                title: 'Test2',
                slug: 'practice002',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: false, part: 'part3' },
                ],
            },
            {
                id: 3,
                title: 'Test3',
                slug: 'practice003',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: false, part: 'part3' },
                ],
            },
            {
                id: 4,
                title: 'Test4',
                slug: 'practice004',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: false, part: 'part3' },
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
                    { name: 'Part 1', description: 'Questions 1-13', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: false, part: 'part3' },
                ],
            },
            {
                id: 6,
                title: 'Test2',
                slug: 'practice006',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: false, part: 'part3' },
                ],
            },
            {
                id: 7,
                title: 'Test3',
                slug: 'practice007',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: false, part: 'part3' },
                ],
            },
            {
                id: 8,
                title: 'Test4',
                slug: 'practice008',
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: false, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: false, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: false, part: 'part3' },
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
                    { name: 'Part 1', description: 'Questions 1-13', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: true, part: 'part3' },
                ],
            },
            {
                id: 2,
                title: 'Test2',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: true, part: 'part3' },
                ],
            },
            {
                id: 3,
                title: 'Test3',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: true, part: 'part3' },
                ],
            },
            {
                id: 4,
                title: 'Test4',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: true, part: 'part3' },
                ],
            },
        ],
    },
    {
        level: 17,
        slug: 'C17',
        tests: [
            {
                id: 1,
                title: 'Test1',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: true, part: 'part3' },
                ],
            },
            {
                id: 2,
                title: 'Test2',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: true, part: 'part3' },
                ],
            },
            {
                id: 3,
                title: 'Test3',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: true, part: 'part3' },
                ],
            },
            {
                id: 4,
                title: 'Test4',
                slug: undefined,
                parts: [
                    { name: 'Part 1', description: 'Questions 1-13', locked: true, part: 'part1' },
                    { name: 'Part 2', description: 'Questions 14-26', locked: true, part: 'part2' },
                    { name: 'Part 3', description: 'Questions 27-40', locked: true, part: 'part3' },
                ],
            },
        ],
    },
];

// --- Topic Wise Section Data (14 Question Types) ---
const questionTypes = [
    'All',
    'Matching Headings',
    'True/False/Not Given',
    'Matching Paragraphs',
    'Summary Completion',
    'Sentence Completion',
    'Multiple Choice',
    'List Selection',
    'Choosing a Title',
    'Classification',
    'Matching Sentence Endings',
    'Table Completion',
    'Flow Chart Completion',
    'Diagram Completion',
    'Short Answer',
];
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
    // Matching Headings
    {
        id: 1,
        name: 'C20 Test1 Part1 - Matching Headings',
        type: 'Matching Headings',
        source: 'Cambridge 20',
        testId: 1,
        part: 'part1',
        questions: 'Q1-7',
        description: 'Match each paragraph with the correct heading from the list of headings.',
        tips: ['Skim each paragraph for the main idea', 'Look for topic sentences at the beginning or end', 'Cross out headings as you use them'],
        locked: false,
    },
    {
        id: 2,
        name: 'C19 Test2 Part2 - Matching Headings',
        type: 'Matching Headings',
        source: 'Cambridge 19',
        testId: 6,
        part: 'part2',
        questions: 'Q14-20',
        description: 'Choose the correct heading for each section from the list provided.',
        tips: ['Read headings first to know what to look for', 'Focus on the overall theme, not just keywords', 'Some headings may not be used'],
        locked: false,
    },
    // True/False/Not Given
    {
        id: 3,
        name: 'C20 Test1 Part2 - True/False/Not Given',
        type: 'True/False/Not Given',
        source: 'Cambridge 20',
        testId: 1,
        part: 'part2',
        questions: 'Q14-19',
        description: 'Decide if the statements agree with the information in the passage.',
        tips: ['TRUE = exactly matches the passage', 'FALSE = contradicts the passage', 'NOT GIVEN = information not in the passage'],
        locked: false,
    },
    {
        id: 4,
        name: 'C20 Test3 Part1 - True/False/Not Given',
        type: 'True/False/Not Given',
        source: 'Cambridge 20',
        testId: 3,
        part: 'part1',
        questions: 'Q1-6',
        description: 'Are the following statements True, False, or Not Given?',
        tips: ["Don't use your own knowledge", 'Be careful with absolute words like "always" or "never"', 'Statements follow passage order'],
        locked: false,
    },
    {
        id: 5,
        name: 'C19 Test1 Part3 - Yes/No/Not Given',
        type: 'True/False/Not Given',
        source: 'Cambridge 19',
        testId: 5,
        part: 'part3',
        questions: 'Q34-40',
        description: "Do the statements agree with the writer's views/claims?",
        tips: ["YES/NO questions test the writer's opinion", 'Look for opinion language and arguments', 'Distinguish between facts and opinions'],
        locked: false,
    },
    // Matching Paragraphs (Locating Information)
    {
        id: 6,
        name: 'C20 Test2 Part3 - Matching Paragraphs',
        type: 'Matching Paragraphs',
        source: 'Cambridge 20',
        testId: 2,
        part: 'part3',
        questions: 'Q27-32',
        description: 'Which paragraph contains the following information?',
        tips: ['Scan for keywords and their synonyms', 'A paragraph may be used more than once', 'Some paragraphs may not be used'],
        locked: false,
    },
    {
        id: 7,
        name: 'C19 Test3 Part2 - Matching Paragraphs',
        type: 'Matching Paragraphs',
        source: 'Cambridge 19',
        testId: 7,
        part: 'part2',
        questions: 'Q14-19',
        description: 'In which paragraph can you find the following information?',
        tips: ['Underline key ideas in questions', 'Read for meaning, not just word matching', 'This type often takes longer - manage time well'],
        locked: false,
    },
    // Summary Completion
    {
        id: 8,
        name: 'C20 Test1 Part3 - Summary Completion',
        type: 'Summary Completion',
        source: 'Cambridge 20',
        testId: 1,
        part: 'part3',
        questions: 'Q33-37',
        description: 'Complete the summary using words from the passage.',
        tips: ['Check the word limit carefully', 'Answers come from a specific section', 'Grammar must fit the sentence'],
        locked: false,
    },
    {
        id: 9,
        name: 'C19 Test4 Part1 - Summary Completion',
        type: 'Summary Completion',
        source: 'Cambridge 19',
        testId: 8,
        part: 'part1',
        questions: 'Q1-8',
        description: 'Complete the summary with NO MORE THAN TWO WORDS.',
        tips: ['Read the whole summary first for context', 'Predict the type of word needed', 'Copy words exactly from the passage'],
        locked: false,
    },
    // Sentence Completion
    {
        id: 10,
        name: 'C20 Test2 Part1 - Sentence Completion',
        type: 'Sentence Completion',
        source: 'Cambridge 20',
        testId: 2,
        part: 'part1',
        questions: 'Q8-13',
        description: 'Complete each sentence with NO MORE THAN TWO WORDS.',
        tips: ['Identify the part of the passage that matches', 'Check your answer fits grammatically', 'Answers follow the order of the passage'],
        locked: false,
    },
    {
        id: 11,
        name: 'C19 Test2 Part3 - Sentence Completion',
        type: 'Sentence Completion',
        source: 'Cambridge 19',
        testId: 6,
        part: 'part3',
        questions: 'Q35-40',
        description: 'Complete each sentence using ONE WORD ONLY from the passage.',
        tips: [
            'Underline keywords in the incomplete sentence',
            'Find the relevant section in the passage',
            'The word must complete the meaning correctly',
        ],
        locked: false,
    },
    // Multiple Choice
    {
        id: 12,
        name: 'C20 Test3 Part2 - Multiple Choice',
        type: 'Multiple Choice',
        source: 'Cambridge 20',
        testId: 3,
        part: 'part2',
        questions: 'Q20-23',
        description: 'Choose the correct letter A, B, C or D.',
        tips: ['Read all options before deciding', 'Eliminate obviously wrong answers', 'Look for evidence in the text'],
        locked: false,
    },
    {
        id: 13,
        name: 'C19 Test1 Part2 - Multiple Choice',
        type: 'Multiple Choice',
        source: 'Cambridge 19',
        testId: 5,
        part: 'part2',
        questions: 'Q21-26',
        description: 'Choose the correct answer A, B, C or D for each question.',
        tips: ['Questions follow passage order', 'Watch for distractors using similar words', 'The correct answer is a paraphrase of the text'],
        locked: false,
    },
    // List Selection
    {
        id: 14,
        name: 'C20 Test4 Part2 - List Selection',
        type: 'List Selection',
        source: 'Cambridge 20',
        testId: 4,
        part: 'part2',
        questions: 'Q20-22',
        description: 'Choose THREE answers from the list of options.',
        tips: ['Read how many answers are required', 'All correct answers have equal weight', 'Scan the relevant section for each option'],
        locked: false,
    },
    {
        id: 15,
        name: 'C19 Test3 Part3 - List Selection',
        type: 'List Selection',
        source: 'Cambridge 19',
        testId: 7,
        part: 'part3',
        questions: 'Q37-40',
        description: 'Choose FOUR letters from the list A-H.',
        tips: ['Options may be paraphrased from the text', 'Order of answers usually does not matter', 'Focus on finding evidence for each option'],
        locked: false,
    },
    // Choosing a Title
    {
        id: 16,
        name: 'C20 Test2 Part2 - Choosing a Title',
        type: 'Choosing a Title',
        source: 'Cambridge 20',
        testId: 2,
        part: 'part2',
        questions: 'Q26',
        description: 'Choose the most suitable title for the passage.',
        tips: [
            'Consider the main theme of the entire passage',
            'Avoid titles that are too specific or too broad',
            'The title should cover all main points',
        ],
        locked: false,
    },
    {
        id: 29,
        name: 'C19 Test1 Part1 - Choosing a Title',
        type: 'Choosing a Title',
        source: 'Cambridge 19',
        testId: 5,
        part: 'part1',
        questions: 'Q13',
        description: 'Select the best title for the reading passage from the options given.',
        tips: [
            'Read the introduction and conclusion carefully',
            'The title reflects the main argument or purpose',
            'Eliminate titles that only cover part of the content',
        ],
        locked: false,
    },
    // Classification
    {
        id: 17,
        name: 'C20 Test1 Part2 - Classification',
        type: 'Classification',
        source: 'Cambridge 20',
        testId: 1,
        part: 'part2',
        questions: 'Q20-23',
        description: 'Classify the following statements according to the categories given.',
        tips: [
            'Understand each category clearly first',
            'Look for names, dates, or categories in the passage',
            'Match statements to the correct group',
        ],
        locked: false,
    },
    {
        id: 18,
        name: 'C19 Test4 Part2 - Classification',
        type: 'Classification',
        source: 'Cambridge 19',
        testId: 8,
        part: 'part2',
        questions: 'Q18-22',
        description: 'Match each statement with the correct category A, B or C.',
        tips: ['Scan for category keywords in the passage', 'Read around the keywords for context', 'Categories may appear multiple times'],
        locked: false,
    },
    // Matching Sentence Endings
    {
        id: 19,
        name: 'C20 Test4 Part1 - Matching Sentence Endings',
        type: 'Matching Sentence Endings',
        source: 'Cambridge 20',
        testId: 4,
        part: 'part1',
        questions: 'Q5-9',
        description: 'Complete each sentence by choosing the correct ending A-G.',
        tips: [
            'Read sentence beginnings first',
            'Check that the completed sentence is grammatically correct',
            'Endings follow the order of information in the passage',
        ],
        locked: false,
    },
    {
        id: 20,
        name: 'C19 Test1 Part1 - Matching Sentence Endings',
        type: 'Matching Sentence Endings',
        source: 'Cambridge 19',
        testId: 5,
        part: 'part1',
        questions: 'Q6-10',
        description: 'Match each sentence beginning with the correct ending.',
        tips: ['Some endings may not be used', 'Look for paraphrases of the text', 'The meaning must match the passage'],
        locked: false,
    },
    // Table Completion
    {
        id: 21,
        name: 'C20 Test3 Part3 - Table Completion',
        type: 'Table Completion',
        source: 'Cambridge 20',
        testId: 3,
        part: 'part3',
        questions: 'Q33-38',
        description: 'Complete the table using NO MORE THAN TWO WORDS.',
        tips: ['Study the table structure first', 'Column and row headings give context', 'Information may not be in passage order'],
        locked: false,
    },
    {
        id: 22,
        name: 'C19 Test2 Part1 - Table Completion',
        type: 'Table Completion',
        source: 'Cambridge 19',
        testId: 6,
        part: 'part1',
        questions: 'Q5-10',
        description: 'Complete the table with ONE WORD ONLY.',
        tips: ['Read column headers to understand categories', 'Scan for related information in the passage', 'Check spelling carefully'],
        locked: false,
    },
    // Flow Chart Completion
    {
        id: 23,
        name: 'C20 Test4 Part3 - Flow Chart Completion',
        type: 'Flow Chart Completion',
        source: 'Cambridge 20',
        testId: 4,
        part: 'part3',
        questions: 'Q35-40',
        description: 'Complete the flow chart with words from the passage.',
        tips: ['Follow the arrows to understand the process', 'Information follows a logical sequence', 'Look for sequence words in the passage'],
        locked: false,
    },
    {
        id: 24,
        name: 'C19 Test3 Part1 - Flow Chart Completion',
        type: 'Flow Chart Completion',
        source: 'Cambridge 19',
        testId: 7,
        part: 'part1',
        questions: 'Q8-13',
        description: 'Complete the flow chart using NO MORE THAN TWO WORDS.',
        tips: ['Understand the overall process first', 'Each step connects to the next', 'Use the flow chart to locate information quickly'],
        locked: false,
    },
    // Diagram Completion
    {
        id: 25,
        name: 'C20 Test2 Part1 - Diagram Completion',
        type: 'Diagram Completion',
        source: 'Cambridge 20',
        testId: 2,
        part: 'part1',
        questions: 'Q1-5',
        description: 'Label the diagram using words from the passage.',
        tips: [
            'Study the diagram carefully before reading',
            'Look for descriptive language in the passage',
            'Pay attention to position and direction words',
        ],
        locked: false,
    },
    {
        id: 26,
        name: 'C19 Test4 Part3 - Diagram Completion',
        type: 'Diagram Completion',
        source: 'Cambridge 19',
        testId: 8,
        part: 'part3',
        questions: 'Q33-37',
        description: 'Complete the diagram with NO MORE THAN TWO WORDS.',
        tips: ['Identify what the diagram represents', 'Find the section describing the diagram', 'Labels may be parts, stages, or features'],
        locked: false,
    },
    // Short Answer
    {
        id: 27,
        name: 'C20 Test1 Part3 - Short Answer',
        type: 'Short Answer',
        source: 'Cambridge 20',
        testId: 1,
        part: 'part3',
        questions: 'Q38-40',
        description: 'Answer the questions with NO MORE THAN THREE WORDS.',
        tips: ['Focus on question words (who, what, where, when)', 'Answer directly without extra words', 'Use words from the passage'],
        locked: false,
    },
    {
        id: 28,
        name: 'C19 Test2 Part2 - Short Answer',
        type: 'Short Answer',
        source: 'Cambridge 19',
        testId: 6,
        part: 'part2',
        questions: 'Q24-26',
        description: 'Answer each question using ONE WORD ONLY.',
        tips: ['Questions follow the passage order', 'Find the specific information quickly', 'Check your answer makes sense'],
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
    router.visit(`/reading/test${activeTask.value.testId}/${activeTask.value.part}`);
};

const handlePartClick = (testId: number, part: string, locked: boolean) => {
    if (locked) return;
    router.visit(`/reading/test${testId}/${part}`);
};

const handleFullTestClick = (testSlug: string | undefined, locked: boolean) => {
    if (locked || !testSlug) return;
    router.visit(`/exam/${testSlug}/reading`);
};

const getQuestionTypeDescription = (type: string): string => {
    const descriptions: Record<string, string> = {
        'Matching Headings':
            'Matching Headings tests your ability to identify the main idea of each paragraph. You must select the most suitable heading from a list for each paragraph or section.',
        'True/False/Not Given':
            'True/False/Not Given (or Yes/No/Not Given) tests whether statements agree with, contradict, or are not mentioned in the passage. This requires careful reading and understanding of what is explicitly stated.',
        'Matching Paragraphs':
            'Matching Paragraphs (Locating Information) requires you to find which paragraph contains specific information. This tests your scanning and skimming abilities.',
        'Summary Completion':
            'Summary Completion requires you to complete a summary of part or all of the passage using words from the text or from a list of options.',
        'Sentence Completion':
            'Sentence Completion tests your ability to find specific information and express it in a grammatically correct way within the given word limit.',
        'Multiple Choice':
            'Multiple Choice questions test detailed understanding of specific points or overall understanding of main points. You choose from options A, B, C, or D.',
        'List Selection':
            'List Selection requires you to choose multiple correct answers from a longer list of options. This tests your ability to identify several pieces of information.',
        'Choosing a Title':
            'Choosing a Title tests your understanding of the main theme and purpose of the entire passage. The correct title should encompass the overall message.',
        Classification:
            'Classification (Matching Features) requires you to match statements or features to different categories mentioned in the passage.',
        'Matching Sentence Endings':
            'Matching Sentence Endings tests your ability to understand the main ideas and details. You match the beginning of a sentence with its correct ending.',
        'Table Completion':
            'Table Completion requires you to fill in gaps in a table using information from the passage. This tests your ability to locate and understand specific details.',
        'Flow Chart Completion':
            'Flow Chart Completion tests your understanding of a process or sequence of events described in the passage. Information typically follows the passage order.',
        'Diagram Completion':
            'Diagram Completion requires you to label parts of a diagram using words from the passage. This tests your ability to understand descriptions of objects or processes.',
        'Short Answer':
            'Short Answer questions require brief answers using words from the passage. They test your ability to locate and understand specific factual information.',
    };
    return descriptions[type] || 'Practice this question type to improve your reading skills.';
};
</script>

<template>
    <Head title="READING" />

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
                            <p class="mt-1 text-red-100">Reading Practice Module</p>
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
                                <h3 class="mb-2 text-lg font-bold text-red-900">Unable to Access Reading Practice</h3>
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
                                <p class="text-sm text-gray-600">Reading Practice</p>
                                <p class="mt-1 text-lg font-bold" :class="subscription.practice_reading_enabled ? 'text-green-600' : 'text-red-600'">
                                    {{ subscription.practice_reading_enabled ? 'Enabled' : 'Not Available' }}
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
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                />
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
                    class="absolute top-1 bottom-1 w-[calc(50%-4px)] rounded-lg bg-linear-to-r from-[#9051ae] to-[#a86bc4] shadow-md transition-all duration-300 ease-out sm:top-1.5 sm:bottom-1.5 sm:w-[calc(50%-6px)]"
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
                <div v-for="section in readingSections" :key="section.level" class="mb-8 sm:mb-12">
                    <!-- Section Header -->
                    <div class="mb-4 rounded-xl bg-gray-800 p-4 text-center sm:mb-6 lg:mb-0 lg:flex lg:h-32 lg:items-center lg:text-left">
                        <div class="flex flex-col items-center justify-center text-white lg:w-56 lg:shrink-0">
                            <h2 class="text-xl font-bold sm:text-2xl">READING</h2>
                            <p class="text-base sm:text-lg">
                                ACADEMIC <span class="text-2xl font-bold text-[#9051ae] sm:text-3xl">{{ section.level }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Test Cards Grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:-mt-20 lg:grid-cols-4 lg:pl-60">
                        <div v-for="test in section.tests" :key="test.id" class="rounded-lg bg-white shadow-md">
                            <div class="rounded-t-lg bg-[#9051ae] p-2.5 text-white sm:p-3">
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
                        <span class="w-full text-sm font-semibold text-gray-700 sm:w-auto sm:text-base">Question Types</span>
                        <button
                            v-for="type in questionTypes"
                            :key="type"
                            @click="activeQuestionType = type"
                            :class="[
                                'rounded-full px-2.5 py-1 text-[10px] font-medium transition-colors sm:px-3 sm:text-xs',
                                activeQuestionType === type ? 'bg-[#9051ae] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
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
                                    <span class="ml-2 rounded-full bg-[#9051ae]/10 px-2 py-0.5 text-xs text-[#9051ae]">
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
                                        activeTask && activeTask.id === task.id ? 'bg-[#9051ae] text-white' : 'hover:bg-gray-50',
                                    ]"
                                >
                                    <FileText
                                        class="h-4 w-4 shrink-0 sm:h-5 sm:w-5"
                                        :class="activeTask && activeTask.id === task.id ? 'text-white' : 'text-[#9051ae]'"
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
                                        <span class="rounded-full bg-[#9051ae]/10 px-3 py-1 text-xs font-medium text-[#9051ae]">
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
                                            : 'bg-[#9051ae] text-white hover:bg-[#7a3f94]',
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
                            <div class="rounded-lg bg-gradient-to-br from-[#9051ae]/5 to-[#9051ae]/10 p-4 sm:p-6">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800 sm:text-base">Tips for {{ activeTask.type }}</h3>
                                <ul class="space-y-2">
                                    <li v-for="(tip, index) in activeTask.tips" :key="index" class="flex items-start gap-2 text-sm text-gray-600">
                                        <span
                                            class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#9051ae] text-xs font-bold text-white"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <span>{{ tip }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Question Type Info -->
                            <div class="mt-6 rounded-lg border border-gray-200 p-4 sm:p-6">
                                <h3 class="mb-3 text-sm font-semibold text-gray-800 sm:text-base">About {{ activeTask.type }}</h3>
                                <p class="text-sm text-gray-600">{{ getQuestionTypeDescription(activeTask.type) }}</p>
                            </div>
                        </div>

                        <!-- No Task Selected -->
                        <div v-else class="flex min-h-[400px] items-center justify-center rounded-lg bg-white p-6 shadow-sm">
                            <div class="text-center">
                                <FileText class="mx-auto mb-4 h-12 w-12 text-gray-300" />
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
