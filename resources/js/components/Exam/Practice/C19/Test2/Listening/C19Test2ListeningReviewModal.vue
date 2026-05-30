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

// Question texts for display - C19 Test 2
const questionTexts: Record<number, { text: string; part: number; section: string }> = {
    // Part 1 Questions (1-10) - Guitar Group
    1: { text: 'What is your occupation? ___', part: 1, section: 'Guitar Group - Registration Form' },
    2: { text: 'How did you hear about us? ___', part: 1, section: 'Guitar Group - Registration Form' },
    3: { text: 'Plays: classical and ___ music', part: 1, section: 'Guitar Group - Registration Form' },
    4: { text: 'Evening: has to look after his ___', part: 1, section: 'Guitar Group - Registration Form' },
    5: { text: 'Meets Wednesdays ___ p.m.', part: 1, section: 'Guitar Group - Table' },
    6: { text: 'Meets in the ___ room at the college', part: 1, section: 'Guitar Group - Table' },
    7: { text: 'Plays: traditional and ___ music', part: 1, section: 'Guitar Group - Table' },
    8: { text: 'Meets: ___ a.m.', part: 1, section: 'Guitar Group - Table' },
    9: { text: 'Meets: at ___ community centre', part: 1, section: 'Guitar Group - Table' },
    10: { text: 'Plays mainly ___ music', part: 1, section: 'Guitar Group - Table' },

    // Part 2 Questions (11-20) - Lifeboat Volunteer
    11: { text: 'Why did Lee first volunteer as a crew member at the Lifeboat station?', part: 2, section: 'Lifeboat Volunteer - Multiple Choice' },
    12: { text: 'What surprised Lee about being a crew member?', part: 2, section: 'Lifeboat Volunteer - Multiple Choice' },
    13: { text: 'What does Lee think is the best part of being a volunteer?', part: 2, section: 'Lifeboat Volunteer - Multiple Choice' },
    14: { text: 'What does Lee say about the Lifeboat volunteers?', part: 2, section: 'Lifeboat Volunteer - Multiple Choice' },
    15: { text: 'Why is Lee raising money for the Lifeboat?', part: 2, section: 'Lifeboat Volunteer - Multiple Choice' },
    16: { text: 'What does the charity spend most money on?', part: 2, section: 'Lifeboat Volunteer - Multiple Choice' },
    17: { text: 'What TWO things does Lee think are needed to be a volunteer? (First choice)', part: 2, section: 'Lifeboat Volunteer - Choose TWO' },
    18: { text: 'What TWO things does Lee think are needed to be a volunteer? (Second choice)', part: 2, section: 'Lifeboat Volunteer - Choose TWO' },
    19: { text: 'What TWO things does the charity do for the local community? (First choice)', part: 2, section: 'Lifeboat Volunteer - Choose TWO' },
    20: { text: 'What TWO things does the charity do for the local community? (Second choice)', part: 2, section: 'Lifeboat Volunteer - Choose TWO' },

    // Part 3 Questions (21-30) - Recycling Footwear
    21: { text: "What does Max say about the__(Rachel's suggestion)?", part: 3, section: 'Recycling Footwear - Multiple Choice' },
    22: {
        text: 'What aspect of the__(training shoes) does Rachel think deserves more attention?',
        part: 3,
        section: 'Recycling Footwear - Multiple Choice',
    },
    23: { text: 'What surprised Max when he researched__(manufacturers)?', part: 3, section: 'Recycling Footwear - Multiple Choice' },
    24: { text: 'What do they agree about__(recycled materials)?', part: 3, section: 'Recycling Footwear - Multiple Choice' },
    25: { text: 'SHOELACES - Which statement applies?', part: 3, section: 'Recycling Footwear - Matching' },
    26: { text: 'SOLE - Which statement applies?', part: 3, section: 'Recycling Footwear - Matching' },
    27: { text: 'UPPER - Which statement applies?', part: 3, section: 'Recycling Footwear - Matching' },
    28: { text: 'TONGUE - Which statement applies?', part: 3, section: 'Recycling Footwear - Matching' },
    29: { text: 'What do they decide to do next for their__(presentation)?', part: 3, section: 'Recycling Footwear - Multiple Choice' },
    30: { text: 'What does Rachel say about their__(progress)?', part: 3, section: 'Recycling Footwear - Multiple Choice' },

    31: { text: 'Tardigrades are commonly known as water ___', part: 4, section: 'Tardigrades - Physical Appearance' },
    32: { text: 'They have eight legs, each ending in four to eight ___', part: 4, section: 'Tardigrades - Physical Appearance' },
    33: { text: 'Tardigrades are most commonly found in ___', part: 4, section: 'Tardigrades - Habitat' },
    34: { text: 'Scientists have shown that they can also survive in a ___', part: 4, section: 'Tardigrades - Habitat' },
    35: { text: 'Cryptobiosis involves tardigrades going into a state of ___', part: 4, section: 'Tardigrades - Cryptobiosis' },
    36: { text: 'Tardigrades can survive temperatures down to minus ___ degrees', part: 4, section: 'Tardigrades - Cryptobiosis' },
    37: { text: 'Tardigrades mainly feed on ___', part: 4, section: 'Tardigrades - Feeding' },
    38: { text: 'Some species are ___ and eat other tardigrades', part: 4, section: 'Tardigrades - Feeding' },
    39: { text: 'Tardigrades are not currently under any ___', part: 4, section: 'Tardigrades - Conservation Status' },
    40: { text: 'However, some species are thought to be at risk from ___ change', part: 4, section: 'Tardigrades - Conservation Status' },
};

// Process answers for display
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

// Group answers by part
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

// Check if a question is answered
const isQuestionAnswered = (questionNumber: number): boolean => {
    return props.answeredQuestions.has(questionNumber);
};

// Get part styling
const getPartStyling = (part: number) => {
    switch (part) {
        case 1:
            return { gradient: 'from-cyan-500 to-teal-600', border: 'border-cyan-200', bg: 'bg-cyan-50' };
        case 2:
            return { gradient: 'from-blue-500 to-indigo-600', border: 'border-blue-200', bg: 'bg-blue-50' };
        case 3:
            return { gradient: 'from-emerald-500 to-green-600', border: 'border-emerald-200', bg: 'bg-emerald-50' };
        case 4:
            return { gradient: 'from-orange-500 to-amber-600', border: 'border-orange-200', bg: 'bg-orange-50' };
        default:
            return { gradient: 'from-gray-500 to-gray-600', border: 'border-gray-200', bg: 'bg-gray-50' };
    }
};

// Get question card styling based on answered status
const getQuestionCardStyling = (questionNumber: number, part: number) => {
    const partStyling = getPartStyling(part);
    const isAnswered = isQuestionAnswered(questionNumber);

    if (isAnswered) {
        return {
            cardClass: `border-2 ${partStyling.border} ${partStyling.bg} shadow-md ring-2 ring-opacity-50`,
            answerBgClass: `bg-gradient-to-r ${partStyling.gradient} text-white`,
            questionNumberClass: `bg-gradient-to-r ${partStyling.gradient}`,
            ringColor: part === 1 ? 'ring-cyan-200' : part === 2 ? 'ring-blue-200' : part === 3 ? 'ring-emerald-200' : 'ring-orange-200',
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

// Handle click outside modal to close
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
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-cyan-500 to-teal-600">
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
                    <p class="mt-2 text-sm text-gray-600">
                        Review all your answers before submitting the test. You can still make changes by closing this modal.
                    </p>
                </div>

                <!-- Modal Content -->
                <div class="max-h-[calc(90vh-140px)] overflow-y-auto">
                    <div class="space-y-6 p-6">
                        <!-- Loop through parts -->
                        <div v-for="(partData, partNumber) in answersByPart" :key="partNumber" class="space-y-4">
                            <!-- Part Header -->
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

                            <!-- Questions Grid -->
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
                                    <!-- Status indicator for answered questions -->
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
                            class="rounded-lg bg-gradient-to-r from-cyan-500 to-teal-600 px-6 py-2 font-medium text-white transition-all duration-200 hover:from-cyan-600 hover:to-teal-700"
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
/* Custom scrollbar for modal content */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #06b6d4, #14b8a6);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #0891b2, #0d9488);
}

/* Modal animation */
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
