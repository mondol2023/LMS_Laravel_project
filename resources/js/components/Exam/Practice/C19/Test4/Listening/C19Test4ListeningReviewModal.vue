<script setup lang="ts">
import { computed } from 'vue';

interface UserAnswer {
    question: number;
    answer: string;
    questionText: string;
    part: number;
    section: string;
}

interface Props {
    isVisible: boolean;
    userAnswers: Record<string, string>;
    answeredQuestions: Set<number>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

// Question texts for display - C19 Test 4
const questionTexts: Record<number, { text: string; part: number; section: string }> = {
    // Part 1 Questions (1-10) - First day at work
    1: { text: 'Name of supervisor:', part: 1, section: 'First day at work - Notes' },
    2: { text: 'Where to leave coat and bag: use ___ in staffroom', part: 1, section: 'First day at work - Notes' },
    3: { text: 'See Tiffany in HR: to give ___ number', part: 1, section: 'First day at work - Notes' },
    4: { text: 'See Tiffany in HR: to collect ___', part: 1, section: 'First day at work - Notes' },
    5: { text: 'Location of HR office: on ___ floor', part: 1, section: 'First day at work - Notes' },
    6: { text: "Supervisor's mobile number:", part: 1, section: 'First day at work - Notes' },
    7: { text: 'Bakery section notes: Use ___ labels', part: 1, section: 'First day at work - Table' },
    8: { text: 'Sushi counter: Re-stock with ___ boxes', part: 1, section: 'First day at work - Table' },
    9: { text: 'Meat/fish counters: Collect ___ for the fish', part: 1, section: 'First day at work - Table' },
    10: { text: 'Meat/fish counters: Must wear special ___', part: 1, section: 'First day at work - Table' },

    // Part 2 Questions (11-20) - Running club
    11: { text: 'TWO problems with some training programmes (First)', part: 2, section: 'Running club - Choose TWO' },
    12: { text: 'TWO problems with some training programmes (Second)', part: 2, section: 'Running club - Choose TWO' },
    13: { text: 'TWO tips for new runners (First)', part: 2, section: 'Running club - Choose TWO' },
    14: { text: 'TWO tips for new runners (Second)', part: 2, section: 'Running club - Choose TWO' },
    15: { text: 'Ben - Reason for joining', part: 2, section: 'Running club - Matching' },
    16: { text: 'Carla - Reason for joining', part: 2, section: 'Running club - Matching' },
    17: { text: 'Dave - Reason for joining', part: 2, section: 'Running club - Matching' },
    18: { text: 'Emma - Reason for joining', part: 2, section: 'Running club - Matching' },
    19: { text: 'What does the speaker say about the race?', part: 2, section: 'Running club - MCQ' },
    20: { text: 'What does the speaker suggest about club membership?', part: 2, section: 'Running club - MCQ' },

    // Part 3 Questions (21-30) - Bookshop discussion
    21: { text: "How does Jane feel about her grandfather's bookshop?", part: 3, section: 'Bookshop discussion - MCQ' },
    22: { text: 'What surprised Jane about the customers?', part: 3, section: 'Bookshop discussion - MCQ' },
    23: { text: 'What does Jane say about the location?', part: 3, section: 'Bookshop discussion - MCQ' },
    24: { text: 'What challenge did the bookshop face?', part: 3, section: 'Bookshop discussion - MCQ' },
    25: { text: "What is Jane's plan for the future?", part: 3, section: 'Bookshop discussion - MCQ' },
    26: { text: 'Travel books - Location in shop', part: 3, section: 'Bookshop discussion - Matching' },
    27: { text: 'Art books - Location in shop', part: 3, section: 'Bookshop discussion - Matching' },
    28: { text: 'History books - Location in shop', part: 3, section: 'Bookshop discussion - Matching' },
    29: { text: 'Science books - Location in shop', part: 3, section: 'Bookshop discussion - Matching' },
    30: { text: "Children's books - Location in shop", part: 3, section: 'Bookshop discussion - Matching' },

    // Part 4 Questions (31-40) - Tree planting
    31: { text: 'young trees face ___ from other plants', part: 4, section: 'Tree planting - Reforestation' },
    32: { text: 'can provide ___ for local farmers', part: 4, section: 'Tree planting - Reforestation' },
    33: { text: 'risk of ___ if wrong species planted', part: 4, section: 'Tree planting - Reforestation' },
    34: { text: 'monitor the ___ of trees over time', part: 4, section: 'Tree planting - Large-scale projects' },
    35: { text: 'use satellite ___ to track progress', part: 4, section: 'Tree planting - Large-scale projects' },
    36: { text: 'use ___ to plant seeds in remote areas', part: 4, section: 'Tree planting - Lampang project' },
    37: { text: 'the ___ of growth exceeded expectations', part: 4, section: 'Tree planting - Lampang project' },
    38: { text: 'attracted ___ back to the forest', part: 4, section: 'Tree planting - Lampang project' },
    39: { text: 'local people benefit from ___ in nearby rivers', part: 4, section: 'Tree planting - Local communities' },
    40: { text: 'trees help prevent ___ in lowland areas', part: 4, section: 'Tree planting - Local communities' },
};

const processedAnswers = computed(() => {
    const answers: UserAnswer[] = [];

    for (let i = 1; i <= 40; i++) {
        const questionKey = `q${i}`;
        const answer = props.userAnswers[questionKey] || '';
        const questionData = questionTexts[i];

        if (questionData) {
            answers.push({
                question: i,
                answer: answer.trim() || '(No answer)',
                questionText: questionData.text,
                part: questionData.part,
                section: questionData.section,
            });
        }
    }

    return answers;
});

const answersByPart = computed(() => {
    const grouped: Record<number, { section: string; answers: UserAnswer[] }> = {};

    processedAnswers.value.forEach((answer) => {
        if (!grouped[answer.part]) {
            grouped[answer.part] = {
                section: answer.section,
                answers: [],
            };
        }
        grouped[answer.part].answers.push(answer);
    });

    return grouped;
});

const isQuestionAnswered = (questionNumber: number): boolean => {
    return props.answeredQuestions.has(questionNumber);
};

const getPartStyling = (part: number) => {
    switch (part) {
        case 1:
            return { gradient: 'from-teal-500 to-cyan-600', border: 'border-teal-200', bg: 'bg-teal-50' };
        case 2:
            return { gradient: 'from-rose-500 to-pink-600', border: 'border-rose-200', bg: 'bg-rose-50' };
        case 3:
            return { gradient: 'from-violet-500 to-purple-600', border: 'border-violet-200', bg: 'bg-violet-50' };
        case 4:
            return { gradient: 'from-emerald-500 to-green-600', border: 'border-emerald-200', bg: 'bg-emerald-50' };
        default:
            return { gradient: 'from-gray-500 to-gray-600', border: 'border-gray-200', bg: 'bg-gray-50' };
    }
};

const getQuestionCardStyling = (questionNumber: number, part: number) => {
    const partStyling = getPartStyling(part);
    const isAnswered = isQuestionAnswered(questionNumber);

    if (isAnswered) {
        return {
            cardClass: `border-2 ${partStyling.border} ${partStyling.bg} shadow-md ring-2 ring-opacity-50`,
            answerBgClass: `bg-gradient-to-r ${partStyling.gradient} text-white`,
            questionNumberClass: `bg-gradient-to-r ${partStyling.gradient}`,
            ringColor: part === 1 ? 'ring-teal-200' : part === 2 ? 'ring-rose-200' : part === 3 ? 'ring-violet-200' : 'ring-emerald-200',
        };
    } else {
        return {
            cardClass: `border border-gray-200 bg-gray-50`,
            answerBgClass: `bg-white border border-gray-200`,
            questionNumberClass: `bg-gray-400`,
            ringColor: '',
        };
    }
};

const handleClose = () => {
    emit('close');
};

const handleOverlayClick = (event: MouseEvent) => {
    if (event.target === event.currentTarget) {
        handleClose();
    }
};
</script>

<template>
    <Teleport to="body">
        <div v-if="isVisible" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4" @click="handleOverlayClick">
            <div class="relative z-[10000] max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-2xl">
                <!-- Modal Header -->
                <div class="sticky top-0 z-10 border-b border-gray-200 bg-white px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="flex items-center gap-3 text-xl font-bold text-gray-900">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-teal-500 to-cyan-600">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                    ></path>
                                </svg>
                            </div>
                            Review Your Answers
                        </h2>
                        <button @click="handleClose" class="rounded-lg p-2 transition-colors hover:bg-gray-100">
                            <svg class="h-6 w-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Review all your answers before submitting.</p>
                </div>

                <!-- Modal Content -->
                <div class="max-h-[calc(90vh-140px)] overflow-y-auto">
                    <div class="space-y-6 p-6">
                        <div v-for="(partData, partNumber) in answersByPart" :key="partNumber" class="space-y-4">
                            <div class="mb-4 flex items-center gap-3">
                                <div
                                    :class="`flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-r ${getPartStyling(partNumber).gradient}`"
                                >
                                    <span class="text-sm font-bold text-white">{{ partNumber }}</span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Part {{ partNumber }}</h3>
                                    <p class="text-sm text-gray-600">{{ partData.section }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div
                                    v-for="answer in partData.answers"
                                    :key="answer.question"
                                    :class="`relative rounded-lg p-4 transition-all duration-300 hover:shadow-lg ${getQuestionCardStyling(answer.question, partNumber).cardClass} ${getQuestionCardStyling(answer.question, partNumber).ringColor}`"
                                >
                                    <div class="flex items-start gap-3">
                                        <div
                                            :class="`flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full shadow-sm ${getQuestionCardStyling(answer.question, partNumber).questionNumberClass}`"
                                        >
                                            <span class="text-xs font-bold text-white">{{ answer.question }}</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p
                                                :class="`mb-2 text-sm leading-relaxed font-medium ${isQuestionAnswered(answer.question) ? 'text-gray-800' : 'text-gray-600'}`"
                                            >
                                                {{ answer.questionText }}
                                            </p>
                                            <div
                                                :class="`rounded-lg px-3 py-2 shadow-sm transition-all duration-200 ${getQuestionCardStyling(answer.question, partNumber).answerBgClass}`"
                                            >
                                                <span
                                                    :class="`text-sm font-medium ${answer.answer === '(No answer)' ? (isQuestionAnswered(answer.question) ? 'text-white/80 italic' : 'text-gray-400 italic') : isQuestionAnswered(answer.question) ? 'text-white' : 'text-gray-900'}`"
                                                >
                                                    {{ answer.answer }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="isQuestionAnswered(answer.question)" class="absolute top-2 right-2">
                                        <div
                                            :class="`h-3 w-3 rounded-full bg-gradient-to-r shadow-sm ring-2 ring-white ${getPartStyling(partNumber).gradient}`"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="sticky bottom-0 border-t border-gray-200 bg-white px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium text-green-600">{{ props.answeredQuestions.size }}</span> of
                            <span class="font-medium">40</span> questions answered
                        </div>
                        <button
                            @click="handleClose"
                            class="rounded-lg bg-gradient-to-r from-teal-500 to-cyan-600 px-6 py-2 font-medium text-white transition-all duration-200 hover:from-teal-600 hover:to-cyan-700"
                        >
                            Continue Test
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #14b8a6, #06b6d4);
    border-radius: 3px;
}

.fixed {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.relative.z-\[10000\] {
    animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
