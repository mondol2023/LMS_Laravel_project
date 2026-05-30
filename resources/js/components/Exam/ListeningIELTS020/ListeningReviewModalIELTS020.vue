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

// Question texts for display
const questionTexts: Record<number, { text: string; part: number; section: string }> = {
    // Part 1 Questions (1-10)
    1: { text: 'Cancel appointment with the ___', part: 1, section: 'Things to do before we go' },
    2: { text: 'Begin taking the ___', part: 1, section: 'Things to do before we go' },
    3: { text: 'Buy ___, a small bag, a spare', part: 1, section: 'Things to do before we go' },
    4: { text: 'a small bag, a spare ___', part: 1, section: 'Things to do before we go' },
    5: { text: 'an electrical ___', part: 1, section: 'Things to do before we go' },
    6: { text: 'Book a ___', part: 1, section: 'Things to do before we go' },
    7: { text: "Vet's details: Name: Colin ___", part: 1, section: "Instructions for Laura's mum" },
    8: { text: 'Tel: ___', part: 1, section: "Instructions for Laura's mum" },
    9: { text: 'Address: Fore street (opposite the ___)', part: 1, section: "Instructions for Laura's mum" },
    10: { text: 'Meet the heating engineer on ___', part: 1, section: "Instructions for Laura's mum" },

    // Part 2 Questions (11-20)
    11: { text: 'Why was the Film Festival started?', part: 2, section: 'Adbourne Film Festival' },
    12: { text: 'What is the price range for tickets?', part: 2, section: 'Adbourne Film Festival' },
    13: { text: 'As well as online, tickets for the films can be obtained', part: 2, section: 'Adbourne Film Festival' },
    14: { text: "Last year's winning film was about", part: 2, section: 'Adbourne Film Festival' },
    15: { text: 'This year the competition prize is', part: 2, section: 'Adbourne Film Festival' },
    16: { text: 'The deadline for entering a film in the competition is the end of', part: 2, section: 'Adbourne Film Festival' },
    17: { text: 'What TWO main criteria are used to judge the film competition? (Question 17)', part: 2, section: 'Adbourne Film Festival' },
    18: { text: 'What TWO main criteria are used to judge the film competition? (Question 18)', part: 2, section: 'Adbourne Film Festival' },
    19: { text: 'What TWO changes will be made to the competition next year? (Question 19)', part: 2, section: 'Adbourne Film Festival' },
    20: { text: 'What TWO changes will be made to the competition next year? (Question 20)', part: 2, section: 'Adbourne Film Festival' },

    // Part 3 Questions (21-30)
    21: { text: 'Leela and Jake chose this article because', part: 3, section: 'Research on web-based crosswords' },
    22: { text: 'How did Leela and Jake persuade students to take part in their research?', part: 3, section: 'Research on web-based crosswords' },
    23: { text: 'Leela and Jake changed the design of the original questionnaire because', part: 3, section: 'Research on web-based crosswords' },
    24: { text: 'Leela was surprised by the fact that', part: 3, section: 'Research on web-based crosswords' },
    25: {
        text: 'What TWO things did respondents say they liked most about doing the crossword? (Question 25)',
        part: 3,
        section: 'Research on web-based crosswords',
    },
    26: {
        text: 'What TWO things did respondents say they liked most about doing the crossword? (Question 26)',
        part: 3,
        section: 'Research on web-based crosswords',
    },
    27: {
        text: 'What TWO problems did students identify with the crossword software? (Question 27)',
        part: 3,
        section: 'Research on web-based crosswords',
    },
    28: {
        text: 'What TWO problems did students identify with the crossword software? (Question 28)',
        part: 3,
        section: 'Research on web-based crosswords',
    },
    29: {
        text: 'What TWO recommendations did Leela and Jake make for future research? (Question 29)',
        part: 3,
        section: 'Research on web-based crosswords',
    },
    30: {
        text: 'What TWO recommendations did Leela and Jake make for future research? (Question 30)',
        part: 3,
        section: 'Research on web-based crosswords',
    },

    // Part 4 Questions (31-40)
    31: { text: 'Workers involved in the study were employed at a ___', part: 4, section: 'Job satisfaction study' },
    32: {
        text: 'Despite some apparent differences between groups of workers, the survey results were statistically ___',
        part: 4,
        section: 'Job satisfaction study',
    },
    33: { text: "The speaker analysed the study's ___ to identify any problems with it", part: 4, section: 'Job satisfaction study' },
    34: { text: 'The various sub-groups were ___ in size', part: 4, section: 'Job satisfaction study' },
    35: { text: 'Workers in the part-time group were mainly ___', part: 4, section: 'Job satisfaction study' },
    36: { text: 'The ___ of workers who agreed to take part in the study was disappointing', part: 4, section: 'Job satisfaction study' },
    37: {
        text: 'Researchers were unable to ___ the circumstances in which workers filled out the questionnaire',
        part: 4,
        section: 'Job satisfaction study',
    },
    38: { text: 'In future, the overall size of the ___ should be increased', part: 4, section: 'Future recommendations' },
    39: { text: 'In future studies, workers should be prevented from having discussions with ___', part: 4, section: 'Future recommendations' },
    40: { text: 'Workers should be reassured that their responses to questions are ___', part: 4, section: 'Future recommendations' },
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
            return { gradient: 'from-blue-500 to-indigo-600', border: 'border-blue-200', bg: 'bg-blue-50' };
        case 2:
            return { gradient: 'from-purple-500 to-pink-600', border: 'border-purple-200', bg: 'bg-purple-50' };
        case 3:
            return { gradient: 'from-green-500 to-emerald-600', border: 'border-green-200', bg: 'bg-green-50' };
        case 4:
            return { gradient: 'from-orange-500 to-red-600', border: 'border-orange-200', bg: 'bg-orange-50' };
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
            ringColor: part === 1 ? 'ring-blue-200' : part === 2 ? 'ring-purple-200' : part === 3 ? 'ring-green-200' : 'ring-orange-200',
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
        <div class="relative z-[10000] max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-2xl">
            <!-- Modal Header -->
            <div class="sticky top-0 z-10 border-b border-gray-200 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-3 text-xl font-bold text-gray-900">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-purple-500 to-pink-600">
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
                                :class="`h-10 w-10 bg-gradient-to-r ${getPartStyling(partNumber).gradient} flex items-center justify-center rounded-lg`"
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
                        class="rounded-lg bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-2 font-medium text-white transition-all duration-200 hover:from-purple-600 hover:to-pink-700"
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
    background: linear-gradient(to bottom, #a855f7, #ec4899);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #9333ea, #db2777);
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
