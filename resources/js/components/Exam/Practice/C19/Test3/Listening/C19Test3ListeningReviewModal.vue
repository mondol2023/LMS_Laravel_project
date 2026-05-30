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

// Question texts for display - C19 Test 3
const questionTexts: Record<number, { text: string; part: number; section: string }> = {
    // Part 1 Questions (1-10) - Local food shops
    1: { text: 'Kite Place – near the ___', part: 1, section: 'Local food shops - Where to go' },
    2: { text: 'Fish market: cross the ___ and turn right', part: 1, section: 'Local food shops - Where to go' },
    3: { text: 'best to go before ___ pm', part: 1, section: 'Local food shops - Where to go' },
    4: { text: "Organic shop: called '___'", part: 1, section: 'Local food shops - Where to go' },
    5: { text: 'look for the large ___ outside', part: 1, section: 'Local food shops - Where to go' },
    6: { text: 'take a ___ minibus, number 289', part: 1, section: 'Local food shops - Where to go' },
    7: { text: 'Fish market: a handful of ___ (type of seaweed)', part: 1, section: 'Local food shops - Shopping' },
    8: { text: 'Organic shop: beans and a ___ for dessert', part: 1, section: 'Local food shops - Shopping' },
    9: { text: 'Organic shop: spices and ___', part: 1, section: 'Local food shops - Shopping' },
    10: { text: 'Bakery: a ___ tart', part: 1, section: 'Local food shops - Shopping' },

    // Part 2 Questions (11-20) - Festival workshops
    11: { text: 'Superheroes - Which information?', part: 2, section: 'Festival workshops - Matching' },
    12: { text: 'Just do it - Which information?', part: 2, section: 'Festival workshops - Matching' },
    13: { text: 'Count on me - Which information?', part: 2, section: 'Festival workshops - Matching' },
    14: { text: 'Speak up - Which information?', part: 2, section: 'Festival workshops - Matching' },
    15: { text: 'Jump for joy - Which information?', part: 2, section: 'Festival workshops - Matching' },
    16: { text: 'Sticks and stones - Which information?', part: 2, section: 'Festival workshops - Matching' },
    17: { text: 'TWO reasons for recommending Alive and Kicking (First)', part: 2, section: 'Festival workshops - Choose TWO' },
    18: { text: 'TWO reasons for recommending Alive and Kicking (Second)', part: 2, section: 'Festival workshops - Choose TWO' },
    19: { text: 'TWO pieces of advice about reading (First)', part: 2, section: 'Festival workshops - Choose TWO' },
    20: { text: 'TWO pieces of advice about reading (Second)', part: 2, section: 'Festival workshops - Choose TWO' },

    // Part 3 Questions (21-30) - Science experiment
    21: { text: 'How does Clare feel about her Year 12 students?', part: 3, section: 'Science experiment - MCQ' },
    22: { text: "How does Jake react to Clare's suggestion?", part: 3, section: 'Science experiment - MCQ' },
    23: { text: 'What problem with animal experiments?', part: 3, section: 'Science experiment - MCQ' },
    24: { text: 'What question should the experiment address?', part: 3, section: 'Science experiment - MCQ' },
    25: { text: 'Clare might consider another experiment involving?', part: 3, section: 'Science experiment - MCQ' },
    26: { text: 'Flowchart: Choose mice which are all the same ___', part: 3, section: 'Science experiment - Flowchart' },
    27: { text: 'Flowchart: Divide mice with a different ___', part: 3, section: 'Science experiment - Flowchart' },
    28: { text: 'Flowchart: Sugar contained in ___', part: 3, section: 'Science experiment - Flowchart' },
    29: { text: 'Flowchart: Weighing chamber to prevent ___', part: 3, section: 'Science experiment - Flowchart' },
    30: { text: 'Flowchart: Do all necessary ___', part: 3, section: 'Science experiment - Flowchart' },

    // Part 4 Questions (31-40) - Microplastics
    31: { text: 'fibres from some ___ during washing', part: 4, section: 'Microplastics - Sources' },
    32: { text: 'They cause injuries to the ___ of wildlife', part: 4, section: 'Microplastics - Effects' },
    33: { text: 'in bottled and tap water, ___ and seafood', part: 4, section: 'Microplastics - Effects' },
    34: { text: 'banned in skin cleaning products and ___', part: 4, section: 'Microplastics - Effects' },
    35: { text: 'enter soil through air, rain and ___', part: 4, section: 'Microplastics - Effects' },
    36: { text: 'Earthworms add ___ to the soil', part: 4, section: 'Microplastics - Study' },
    37: { text: 'microplastics affect the ___ of plants', part: 4, section: 'Microplastics - Study' },
    38: { text: '___ loss in earthworms', part: 4, section: 'Microplastics - Study findings' },
    39: { text: 'a rise in the level of ___ in the soil', part: 4, section: 'Microplastics - Study findings' },
    40: { text: 'changes to soil damage ecosystems and ___', part: 4, section: 'Microplastics - Conclusions' },
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
            return { gradient: 'from-rose-500 to-pink-600', border: 'border-rose-200', bg: 'bg-rose-50' };
        case 2:
            return { gradient: 'from-violet-500 to-purple-600', border: 'border-violet-200', bg: 'bg-violet-50' };
        case 3:
            return { gradient: 'from-sky-500 to-blue-600', border: 'border-sky-200', bg: 'bg-sky-50' };
        case 4:
            return { gradient: 'from-lime-500 to-green-600', border: 'border-lime-200', bg: 'bg-lime-50' };
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
            ringColor: part === 1 ? 'ring-rose-200' : part === 2 ? 'ring-violet-200' : part === 3 ? 'ring-sky-200' : 'ring-lime-200',
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
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-rose-500 to-pink-600">
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
                            class="rounded-lg bg-gradient-to-r from-rose-500 to-pink-600 px-6 py-2 font-medium text-white transition-all duration-200 hover:from-rose-600 hover:to-pink-700"
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
    background: linear-gradient(to bottom, #f43f5e, #ec4899);
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
