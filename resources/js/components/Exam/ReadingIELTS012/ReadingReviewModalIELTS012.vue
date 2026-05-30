<script setup lang="ts">
import { computed } from 'vue';

interface UserAnswer {
    question: number;
    answer: string;
    questionText: string;
    part: number;
    passageTitle: string;
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

// Question texts for display
const questionTexts: Record<number, { text: string; part: number; passageTitle: string }> = {
    // Part 1 Questions (1-14) - Green virtues of green sand
    1: { text: 'a description of plans to expand production of AFM', part: 1, passageTitle: 'Green virtues of green sand' },
    2: { text: 'the identification of a potential danger in the raw material for AFM', part: 1, passageTitle: 'Green virtues of green sand' },
    3: { text: 'an example of AFM use in the export market', part: 1, passageTitle: 'Green virtues of green sand' },
    4: { text: 'a comparison of the value of green glass and other types of glass', part: 1, passageTitle: 'Green virtues of green sand' },
    5: { text: 'a list of potential applications of AFM in the domestic market', part: 1, passageTitle: 'Green virtues of green sand' },
    6: {
        text: 'the conclusions drawn from laboratory checks on the process of AFM production',
        part: 1,
        passageTitle: 'Green virtues of green sand',
    },
    7: { text: 'identification of current funding for the production of green sand', part: 1, passageTitle: 'Green virtues of green sand' },
    8: { text: 'an explanation of the chosen brand name for crushed green glass', part: 1, passageTitle: 'Green virtues of green sand' },
    9: { text: 'a description of plans for exporting AFM', part: 1, passageTitle: 'Green virtues of green sand' },
    10: { text: 'a description of what has to happen before AFM is accepted for general use', part: 1, passageTitle: 'Green virtues of green sand' },
    11: { text: 'The use of crushed green glass (AFM) may help to save a diminishing ___', part: 1, passageTitle: 'Green virtues of green sand' },
    12: { text: 'while solving a major problem for the ___', part: 1, passageTitle: 'Green virtues of green sand' },
    13: { text: 'Only glass from bottles that have been used for ___ can be used', part: 1, passageTitle: 'Green virtues of green sand' },
    14: { text: 'AFM is more effective than ___ as a water filter', part: 1, passageTitle: 'Green virtues of green sand' },

    // Part 2 Questions (15-27) - Reading Passage 2
    15: { text: 'The number of sharks in the Mediterranean has risen in recent years.', part: 2, passageTitle: 'Passage 2' },
    16: { text: 'Tourism in the Mediterranean is now causing problems for marine life.', part: 2, passageTitle: 'Passage 2' },
    17: { text: 'Plastic pollution in the Mediterranean mainly comes from cruise ships.', part: 2, passageTitle: 'Passage 2' },
    18: { text: 'Local fishing communities are actively supporting conservation efforts.', part: 2, passageTitle: 'Passage 2' },
    19: { text: 'Marine protected areas in the Mediterranean are achieving their goals.', part: 2, passageTitle: 'Passage 2' },
    20: { text: 'What is the main focus of marine conservation efforts?', part: 2, passageTitle: 'Passage 2' },
    21: { text: 'According to the passage, what poses the greatest threat to marine ecosystems?', part: 2, passageTitle: 'Passage 2' },
    22: { text: 'What does the author suggest about current conservation policies?', part: 2, passageTitle: 'Passage 2' },
    23: { text: 'Which factor is mentioned as contributing to species decline?', part: 2, passageTitle: 'Passage 2' },
    24: { text: 'What is identified as a barrier to effective conservation?', part: 2, passageTitle: 'Passage 2' },
    25: { text: 'According to the passage, what could improve conservation outcomes?', part: 2, passageTitle: 'Passage 2' },
    26: { text: 'What does the author conclude about marine conservation?', part: 2, passageTitle: 'Passage 2' },
    27: { text: 'Which approach does the author recommend for future conservation?', part: 2, passageTitle: 'Passage 2' },

    // Part 3 Questions (28-40) - Reading Passage 3
    28: { text: 'Overview paragraph for section A-F', part: 3, passageTitle: 'Passage 3' },
    29: { text: 'Overview paragraph for section A-F', part: 3, passageTitle: 'Passage 3' },
    30: { text: 'Overview paragraph for section A-F', part: 3, passageTitle: 'Passage 3' },
    31: { text: 'Overview paragraph for section A-F', part: 3, passageTitle: 'Passage 3' },
    32: { text: 'Overview paragraph for section A-F', part: 3, passageTitle: 'Passage 3' },
    33: { text: 'Overview paragraph for section A-F', part: 3, passageTitle: 'Passage 3' },
    34: { text: 'The practice has existed for ___', part: 3, passageTitle: 'Passage 3' },
    35: { text: 'Traditional material used was ___', part: 3, passageTitle: 'Passage 3' },
    36: { text: 'Some examples are now housed in ___', part: 3, passageTitle: 'Passage 3' },
    37: { text: 'Modern variations can be found on ___', part: 3, passageTitle: 'Passage 3' },
    38: { text: "What is the author's main argument about traditional practices?", part: 3, passageTitle: 'Passage 3' },
    39: { text: 'According to the passage, what challenge do practitioners face?', part: 3, passageTitle: 'Passage 3' },
    40: { text: 'What does the author suggest for preserving traditions?', part: 3, passageTitle: 'Passage 3' },
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
                passageTitle: questionData.passageTitle,
            });
        }
    }

    return answers;
});

// Group answers by part
const answersByPart = computed(() => {
    const grouped: Record<number, { passageTitle: string; answers: UserAnswer[] }> = {};

    processedAnswers.value.forEach((answer) => {
        if (!grouped[answer.part]) {
            grouped[answer.part] = {
                passageTitle: answer.passageTitle,
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
            return { gradient: 'from-blue-500 to-indigo-600', border: 'border-blue-200', bg: 'bg-blue-50' };
        case 2:
            return { gradient: 'from-purple-500 to-pink-600', border: 'border-purple-200', bg: 'bg-purple-50' };
        case 3:
            return { gradient: 'from-green-500 to-emerald-600', border: 'border-green-200', bg: 'bg-green-50' };
        default:
            return { gradient: 'from-gray-500 to-gray-600', border: 'border-gray-200', bg: 'bg-gray-50' };
    }
};

// Get question card styling based on answered status
const getQuestionCardStyling = (questionNumber: number, part: number) => {
    const partStyling = getPartStyling(part);
    const isAnswered = isQuestionAnswered(questionNumber);

    if (isAnswered) {
        // Highlighted styling for answered questions
        return {
            cardClass: `border-2 ${partStyling.border} ${partStyling.bg} shadow-md ring-2 ring-opacity-50`,
            answerBgClass: `bg-gradient-to-r ${partStyling.gradient} text-white`,
            questionNumberClass: `bg-gradient-to-r ${partStyling.gradient}`,
            ringColor: part === 1 ? 'ring-blue-200' : part === 2 ? 'ring-purple-200' : 'ring-green-200',
        };
    } else {
        // Normal styling for unanswered questions
        return {
            cardClass: `border border-gray-200 bg-gray-50`,
            answerBgClass: `bg-white border border-gray-200`,
            questionNumberClass: `bg-gray-400`,
            ringColor: '',
        };
    }
};

// Get question range for each part
const getQuestionRange = (part: number) => {
    switch (part) {
        case 1:
            return 'Questions 1-14';
        case 2:
            return 'Questions 15-27';
        case 3:
            return 'Questions 28-40';
        default:
            return '';
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
    <div v-if="isVisible" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4" @click="handleOverlayClick">
        <div class="relative z-[10000] max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-2xl">
            <!-- Modal Header -->
            <div class="sticky top-0 z-10 border-b border-gray-200 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-3 text-xl font-bold text-gray-900">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                ></path>
                            </svg>
                        </div>
                        Review Your Reading Answers
                    </h2>
                    <button @click="handleClose" class="rounded-lg p-2 transition-colors hover:bg-gray-100">
                        <svg class="h-6 w-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    Review all your answers before submitting the reading test. You can still make changes by closing this modal.
                </p>
            </div>

            <!-- Modal Content -->
            <div class="max-h-[calc(90vh-140px)] overflow-y-auto">
                <div class="space-y-8 p-6">
                    <!-- Loop through parts -->
                    <div v-for="(partData, partNumber) in answersByPart" :key="partNumber" class="space-y-4">
                        <!-- Part Header -->
                        <div class="mb-6 flex items-center gap-4">
                            <div
                                :class="`h-12 w-12 bg-gradient-to-r ${getPartStyling(partNumber).gradient} flex items-center justify-center rounded-lg shadow-lg`"
                            >
                                <span class="text-lg font-bold text-white">{{ partNumber }}</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900">Reading Passage {{ partNumber }}</h3>
                                <p class="text-sm font-medium text-gray-600">{{ partData.passageTitle }}</p>
                                <p class="text-xs text-gray-500">{{ getQuestionRange(partNumber) }}</p>
                            </div>
                        </div>

                        <!-- Questions Grid -->
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                            <div
                                v-for="answer in partData.answers"
                                :key="answer.question"
                                :class="`relative rounded-lg p-4 transition-all duration-300 hover:shadow-lg ${getQuestionCardStyling(answer.question, partNumber).cardClass} ${getQuestionCardStyling(answer.question, partNumber).ringColor}`"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        :class="`h-7 w-7 flex-shrink-0 ${getQuestionCardStyling(answer.question, partNumber).questionNumberClass} flex items-center justify-center rounded-full shadow-sm`"
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
                                        :class="`h-3 w-3 rounded-full bg-gradient-to-r ${getPartStyling(partNumber).gradient} shadow-sm ring-2 ring-white`"
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
                        class="rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-2 font-medium text-white shadow-lg transition-all duration-200 hover:from-blue-600 hover:to-indigo-700"
                    >
                        Continue Test
                    </button>
                </div>
            </div>
        </div>
    </div>
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
    background: linear-gradient(to bottom, #3b82f6, #6366f1);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #2563eb, #4f46e5);
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
