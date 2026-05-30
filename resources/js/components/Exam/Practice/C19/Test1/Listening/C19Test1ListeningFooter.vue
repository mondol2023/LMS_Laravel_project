<script setup lang="ts">
interface Props {
    activePart: 'part1' | 'part2' | 'part3' | 'part4';
    answeredQuestions: Set<number>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    navigate: [part: 'part1' | 'part2' | 'part3' | 'part4'];
    scrollToQuestion: [questionNumber: number];
}>();

const handleNavigate = (part: 'part1' | 'part2' | 'part3' | 'part4') => {
    emit('navigate', part);
};

const handleScrollToQuestion = (questionNumber: number) => {
    emit('scrollToQuestion', questionNumber);
};

const getQuestionNumbers = () => {
    if (props.activePart === 'part1') {
        return Array.from({ length: 10 }, (_, i) => i + 1);
    } else if (props.activePart === 'part2') {
        // For part2, show individual buttons for all questions 11-20
        return Array.from({ length: 10 }, (_, i) => i + 11);
    } else if (props.activePart === 'part3') {
        // For part3, show individual buttons for 21-27, then merged button for 28-30
        return [21, 22, 23, 24, 25, 26, 27, 28, 29, 30];
    } else {
        // For part4, show merged buttons for 31-32 and 33-34, then individual buttons for 35-40
        return [31, 32, 33, 34, 35, 36, 37, 38, 39, 40];
    }
};

const handleQuestionClick = (questionRef: number | string) => {
    if (typeof questionRef === 'string') {
        // Handle merged questions like '17-18', '19-20', '28-30', '31-32', '33-34'
        if (questionRef === '17-18') {
            emit('scrollToQuestion', 17);
        } else if (questionRef === '19-20') {
            emit('scrollToQuestion', 19);
        } else if (questionRef === '28-30') {
            emit('scrollToQuestion', 28);
        } else if (questionRef === '31-32') {
            emit('scrollToQuestion', 31);
        } else if (questionRef === '33-34') {
            emit('scrollToQuestion', 33);
        }
    } else {
        // Handle individual question numbers
        emit('scrollToQuestion', questionRef);
    }
};

// Check if a question number is answered
const isQuestionAnswered = (questionRef: number | string): boolean => {
    if (typeof questionRef === 'string') {
        // For merged questions, check if any of the questions in the range are answered
        if (questionRef === '17-18') {
            return props.answeredQuestions.has(17) || props.answeredQuestions.has(18);
        } else if (questionRef === '19-20') {
            return props.answeredQuestions.has(19) || props.answeredQuestions.has(20);
        } else if (questionRef === '28-30') {
            return props.answeredQuestions.has(28) || props.answeredQuestions.has(29) || props.answeredQuestions.has(30);
        } else if (questionRef === '31-32') {
            return props.answeredQuestions.has(31) || props.answeredQuestions.has(32);
        } else if (questionRef === '33-34') {
            return props.answeredQuestions.has(33) || props.answeredQuestions.has(34);
        }
        return false;
    } else {
        // For individual questions
        return props.answeredQuestions.has(questionRef);
    }
};

// Get button classes based on answered status and part
const getButtonClasses = (questionRef: number | string) => {
    const isAnswered = isQuestionAnswered(questionRef);
    const baseClasses = typeof questionRef === 'string' ? 'h-8 w-14 rounded-lg px-2 text-xs' : 'h-8 w-8 rounded-full text-xs';

    if (isAnswered) {
        // Colored background for answered questions
        if (props.activePart === 'part1') {
            return `${baseClasses} bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-md`;
        } else if (props.activePart === 'part2') {
            return `${baseClasses} bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-md`;
        } else if (props.activePart === 'part3') {
            return `${baseClasses} bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-md`;
        } else {
            return `${baseClasses} bg-gradient-to-r from-orange-500 to-red-600 text-white shadow-md`;
        }
    } else {
        // White background for unanswered questions
        return `${baseClasses} bg-white border-2 border-gray-300 text-gray-600 hover:bg-gray-50 hover:border-gray-400`;
    }
};

// Get desktop button classes
const getDesktopButtonClasses = (questionRef: number | string) => {
    const isAnswered = isQuestionAnswered(questionRef);
    const baseClasses = typeof questionRef === 'string' ? 'h-10 w-16 rounded-lg px-2 text-sm font-bold' : 'h-10 w-10 rounded-full text-sm font-bold';

    if (isAnswered) {
        // Colored background for answered questions
        if (props.activePart === 'part1') {
            return `${baseClasses} bg-gradient-to-r from-blue-500 to-indigo-600 text-white hover:from-blue-600 hover:to-indigo-700 focus:ring-blue-500 shadow-md`;
        } else if (props.activePart === 'part2') {
            return `${baseClasses} bg-gradient-to-r from-purple-500 to-pink-600 text-white hover:from-purple-600 hover:to-pink-700 focus:ring-purple-500 shadow-md`;
        } else if (props.activePart === 'part3') {
            return `${baseClasses} bg-gradient-to-r from-green-500 to-emerald-600 text-white hover:from-green-600 hover:to-emerald-700 focus:ring-green-500 shadow-md`;
        } else {
            return `${baseClasses} bg-gradient-to-r from-orange-500 to-red-600 text-white hover:from-orange-600 hover:to-red-700 focus:ring-orange-500 shadow-md`;
        }
    } else {
        // White background for unanswered questions
        return `${baseClasses} bg-white border-2 border-gray-300 text-gray-600 hover:bg-gray-50 hover:border-gray-400 shadow-sm`;
    }
};
</script>

<template>
    <div class="fixed right-0 bottom-0 left-0 z-30 border-t border-gray-200 bg-white/95 shadow-lg backdrop-blur-sm">
        <!-- Mobile Layout -->
        <div class="block px-3 py-2 lg:hidden">
            <!-- Part Navigation -->
            <div class="mb-3 flex items-center justify-center gap-1">
                <button
                    @click="handleNavigate('part1')"
                    class="flex-1 rounded-md px-2 py-2 text-xs font-medium transition-all duration-200"
                    :class="activePart === 'part1' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Part 1
                </button>
                <button
                    @click="handleNavigate('part2')"
                    class="flex-1 rounded-md px-2 py-2 text-xs font-medium transition-all duration-200"
                    :class="activePart === 'part2' ? 'bg-purple-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Part 2
                </button>
                <button
                    @click="handleNavigate('part3')"
                    class="flex-1 rounded-md px-2 py-2 text-xs font-medium transition-all duration-200"
                    :class="activePart === 'part3' ? 'bg-green-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Part 3
                </button>
                <button
                    @click="handleNavigate('part4')"
                    class="flex-1 rounded-md px-2 py-2 text-xs font-medium transition-all duration-200"
                    :class="activePart === 'part4' ? 'bg-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Part 4
                </button>
            </div>

            <!-- Question Numbers - Improved layout for mobile -->
            <div class="flex flex-wrap justify-center gap-2">
                <button
                    v-for="n in getQuestionNumbers()"
                    :key="n"
                    @click="handleQuestionClick(n)"
                    class="font-bold shadow-sm transition-all duration-200 focus:ring-2 focus:ring-offset-1 focus:outline-none active:scale-95"
                    :class="[getButtonClasses(n), isQuestionAnswered(n) ? 'text-white focus:ring-purple-300' : 'text-gray-600 focus:ring-gray-300']"
                >
                    {{ n }}
                </button>
            </div>
        </div>

        <!-- Desktop/Tablet Layout -->
        <div class="hidden px-4 py-3 lg:block">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <!-- Part Navigation -->
                    <div class="flex items-center justify-center gap-2 xl:justify-start">
                        <button
                            @click="handleNavigate('part1')"
                            class="flex items-center gap-2 rounded-lg px-4 py-2 font-medium transition-all duration-200"
                            :class="
                                activePart === 'part1'
                                    ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md"
                                :class="activePart === 'part1' ? 'bg-white/20' : 'bg-blue-100'"
                            >
                                <svg
                                    class="h-3 w-3"
                                    :class="activePart === 'part1' ? 'text-white' : 'text-blue-600'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                    ></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="text-sm font-semibold">Part 1</div>
                                <div class="text-xs opacity-80">Q 1-10</div>
                            </div>
                        </button>

                        <button
                            @click="handleNavigate('part2')"
                            class="flex items-center gap-2 rounded-lg px-4 py-2 font-medium transition-all duration-200"
                            :class="
                                activePart === 'part2'
                                    ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md"
                                :class="activePart === 'part2' ? 'bg-white/20' : 'bg-purple-100'"
                            >
                                <svg
                                    class="h-3 w-3"
                                    :class="activePart === 'part2' ? 'text-white' : 'text-purple-600'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                    ></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="text-sm font-semibold">Part 2</div>
                                <div class="text-xs opacity-80">Q 11-20</div>
                            </div>
                        </button>

                        <button
                            @click="handleNavigate('part3')"
                            class="flex items-center gap-2 rounded-lg px-4 py-2 font-medium transition-all duration-200"
                            :class="
                                activePart === 'part3'
                                    ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md"
                                :class="activePart === 'part3' ? 'bg-white/20' : 'bg-green-100'"
                            >
                                <svg
                                    class="h-3 w-3"
                                    :class="activePart === 'part3' ? 'text-white' : 'text-green-600'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                    ></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="text-sm font-semibold">Part 3</div>
                                <div class="text-xs opacity-80">Q 21-30</div>
                            </div>
                        </button>

                        <button
                            @click="handleNavigate('part4')"
                            class="flex items-center gap-2 rounded-lg px-4 py-2 font-medium transition-all duration-200"
                            :class="
                                activePart === 'part4'
                                    ? 'bg-gradient-to-r from-orange-500 to-red-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md"
                                :class="activePart === 'part4' ? 'bg-white/20' : 'bg-orange-100'"
                            >
                                <svg
                                    class="h-3 w-3"
                                    :class="activePart === 'part4' ? 'text-white' : 'text-orange-600'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                    ></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="text-sm font-semibold">Part 4</div>
                                <div class="text-xs opacity-80">Q 31-40</div>
                            </div>
                        </button>
                    </div>

                    <!-- Question Numbers Navigation -->
                    <div class="flex justify-center xl:justify-end">
                        <div class="flex max-w-lg flex-wrap justify-center gap-2.5">
                            <button
                                v-for="n in getQuestionNumbers()"
                                :key="n"
                                @click="handleQuestionClick(n)"
                                class="transition-all duration-200 hover:scale-105 focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                :class="[getDesktopButtonClasses(n), isQuestionAnswered(n) ? 'text-white' : 'text-gray-600']"
                            >
                                {{ n }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
