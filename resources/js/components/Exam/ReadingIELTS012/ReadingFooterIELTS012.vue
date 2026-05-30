<script setup lang="ts">
interface Props {
    activePart: 'part1' | 'part2' | 'part3';
    answeredQuestions: Set<number>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    navigate: [part: 'part1' | 'part2' | 'part3'];
    scrollToQuestion: [questionNumber: number];
}>();

const handleNavigate = (part: 'part1' | 'part2' | 'part3') => {
    emit('navigate', part);
};

const handleScrollToQuestion = (questionNumber: number) => {
    emit('scrollToQuestion', questionNumber);
};

const getQuestionNumbers = () => {
    if (props.activePart === 'part1') {
        return Array.from({ length: 13 }, (_, i) => i + 1);
    } else if (props.activePart === 'part2') {
        return Array.from({ length: 13 }, (_, i) => i + 14);
    } else {
        return Array.from({ length: 14 }, (_, i) => i + 27);
    }
};

// Check if a question is answered
const isQuestionAnswered = (questionNumber: number): boolean => {
    return props.answeredQuestions.has(questionNumber);
};

// Get button classes based on answered status and part
const getButtonClasses = (questionNumber: number) => {
    const isAnswered = isQuestionAnswered(questionNumber);
    const baseClasses = 'h-7 w-7 rounded-full text-xs font-bold shadow-sm transition-all duration-200 hover:scale-110';

    if (isAnswered) {
        // Colored background for answered questions
        if (props.activePart === 'part1') {
            return `${baseClasses} bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white`;
        } else if (props.activePart === 'part2') {
            return `${baseClasses} bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white`;
        } else {
            return `${baseClasses} bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white`;
        }
    } else {
        // White background for unanswered questions
        return `${baseClasses} bg-white border-2 border-gray-300 text-gray-600 hover:bg-gray-50 hover:border-gray-400`;
    }
};

// Get desktop button classes
const getDesktopButtonClasses = (questionNumber: number) => {
    const isAnswered = isQuestionAnswered(questionNumber);
    const baseClasses =
        'h-10 w-10 rounded-full text-sm font-bold shadow-md transition-all duration-200 hover:scale-110 hover:shadow-lg focus:ring-2 focus:ring-offset-2 focus:outline-none';

    if (isAnswered) {
        // Colored background for answered questions
        if (props.activePart === 'part1') {
            return `${baseClasses} bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 focus:ring-blue-500 text-white`;
        } else if (props.activePart === 'part2') {
            return `${baseClasses} bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 focus:ring-purple-500 text-white`;
        } else {
            return `${baseClasses} bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:ring-green-500 text-white`;
        }
    } else {
        // White background for unanswered questions
        return `${baseClasses} bg-white border-2 border-gray-300 text-gray-600 hover:bg-gray-50 hover:border-gray-400`;
    }
};
</script>

<template>
    <div class="fixed right-0 bottom-0 left-0 z-30 border-t border-gray-200 bg-white/95 shadow-lg backdrop-blur-sm">
        <!-- Mobile Layout -->
        <div class="block space-y-3 px-4 py-3 md:hidden">
            <!-- Part Navigation -->
            <div class="flex items-center justify-center gap-1">
                <button
                    @click="handleNavigate('part1')"
                    class="flex-1 rounded-lg px-3 py-2 text-xs font-medium transition-all duration-200"
                    :class="activePart === 'part1' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Part 1
                </button>
                <button
                    @click="handleNavigate('part2')"
                    class="flex-1 rounded-lg px-3 py-2 text-xs font-medium transition-all duration-200"
                    :class="activePart === 'part2' ? 'bg-purple-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Part 2
                </button>
                <button
                    @click="handleNavigate('part3')"
                    class="flex-1 rounded-lg px-3 py-2 text-xs font-medium transition-all duration-200"
                    :class="activePart === 'part3' ? 'bg-green-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                >
                    Part 3
                </button>
            </div>

            <!-- Question Numbers -->
            <div class="flex max-h-20 flex-wrap justify-center gap-1 overflow-y-auto">
                <button v-for="n in getQuestionNumbers()" :key="n" @click="handleScrollToQuestion(n)" :class="getButtonClasses(n)">
                    {{ n }}
                </button>
            </div>
        </div>

        <!-- Desktop Layout -->
        <div class="hidden px-6 py-4 md:block">
            <div class="mx-auto max-w-7xl">
                <div class="flex items-center justify-between">
                    <!-- Part Navigation -->
                    <div class="flex items-center gap-4">
                        <button
                            @click="handleNavigate('part1')"
                            class="group flex min-w-[140px] items-center gap-3 rounded-xl px-6 py-3 font-medium transition-all duration-200"
                            :class="
                                activePart === 'part1'
                                    ? 'scale-105 transform bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg'
                                    : 'bg-gray-100 text-gray-600 hover:scale-102 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg"
                                :class="activePart === 'part1' ? 'bg-white/20' : 'bg-blue-100'"
                            >
                                <svg
                                    class="h-4 w-4"
                                    :class="activePart === 'part1' ? 'text-white' : 'text-blue-600'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    ></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="text-sm font-bold">Reading Part 1</div>
                                <div class="text-xs opacity-80">Questions 1-14</div>
                            </div>
                        </button>

                        <button
                            @click="handleNavigate('part2')"
                            class="group flex min-w-[140px] items-center gap-3 rounded-xl px-6 py-3 font-medium transition-all duration-200"
                            :class="
                                activePart === 'part2'
                                    ? 'scale-105 transform bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-lg'
                                    : 'bg-gray-100 text-gray-600 hover:scale-102 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg"
                                :class="activePart === 'part2' ? 'bg-white/20' : 'bg-purple-100'"
                            >
                                <svg
                                    class="h-4 w-4"
                                    :class="activePart === 'part2' ? 'text-white' : 'text-purple-600'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-5H8z"
                                    ></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="text-sm font-bold">Reading Part 2</div>
                                <div class="text-xs opacity-80">Questions 15-27</div>
                            </div>
                        </button>

                        <button
                            @click="handleNavigate('part3')"
                            class="group flex min-w-[140px] items-center gap-3 rounded-xl px-6 py-3 font-medium transition-all duration-200"
                            :class="
                                activePart === 'part3'
                                    ? 'scale-105 transform bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg'
                                    : 'bg-gray-100 text-gray-600 hover:scale-102 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg"
                                :class="activePart === 'part3' ? 'bg-white/20' : 'bg-green-100'"
                            >
                                <svg
                                    class="h-4 w-4"
                                    :class="activePart === 'part3' ? 'text-white' : 'text-green-600'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
                                    ></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="text-sm font-bold">Reading Part 3</div>
                                <div class="text-xs opacity-80">Questions 27-40</div>
                            </div>
                        </button>
                    </div>

                    <!-- Question Numbers Navigation -->
                    <div class="flex flex-wrap gap-1">
                        <button v-for="n in getQuestionNumbers()" :key="n" @click="handleScrollToQuestion(n)" :class="getDesktopButtonClasses(n)">
                            {{ n }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hover\:scale-102:hover {
    transform: scale(1.02);
}
</style>
