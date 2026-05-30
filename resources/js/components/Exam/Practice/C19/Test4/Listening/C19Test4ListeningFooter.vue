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

const getQuestionNumbers = () => {
    if (props.activePart === 'part1') {
        return Array.from({ length: 10 }, (_, i) => i + 1);
    } else if (props.activePart === 'part2') {
        return Array.from({ length: 10 }, (_, i) => i + 11);
    } else if (props.activePart === 'part3') {
        return Array.from({ length: 10 }, (_, i) => i + 21);
    } else {
        return Array.from({ length: 10 }, (_, i) => i + 31);
    }
};

const handleQuestionClick = (questionRef: number) => {
    emit('scrollToQuestion', questionRef);
};

const isQuestionAnswered = (questionRef: number): boolean => {
    return props.answeredQuestions.has(questionRef);
};

const getButtonClasses = (questionRef: number) => {
    const isAnswered = isQuestionAnswered(questionRef);
    const baseClasses = 'h-8 w-8 rounded-full text-xs';

    if (isAnswered) {
        if (props.activePart === 'part1') {
            return `${baseClasses} bg-gradient-to-r from-teal-500 to-cyan-600 text-white shadow-md`;
        } else if (props.activePart === 'part2') {
            return `${baseClasses} bg-gradient-to-r from-rose-500 to-pink-600 text-white shadow-md`;
        } else if (props.activePart === 'part3') {
            return `${baseClasses} bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-md`;
        } else {
            return `${baseClasses} bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-md`;
        }
    } else {
        return `${baseClasses} bg-white border-2 border-gray-300 text-gray-600 hover:bg-gray-50 hover:border-gray-400`;
    }
};

const getDesktopButtonClasses = (questionRef: number) => {
    const isAnswered = isQuestionAnswered(questionRef);
    const baseClasses = 'h-10 w-10 rounded-full text-sm font-bold';

    if (isAnswered) {
        if (props.activePart === 'part1') {
            return `${baseClasses} bg-gradient-to-r from-teal-500 to-cyan-600 text-white hover:from-teal-600 hover:to-cyan-700 shadow-md`;
        } else if (props.activePart === 'part2') {
            return `${baseClasses} bg-gradient-to-r from-rose-500 to-pink-600 text-white hover:from-rose-600 hover:to-pink-700 shadow-md`;
        } else if (props.activePart === 'part3') {
            return `${baseClasses} bg-gradient-to-r from-violet-500 to-purple-600 text-white hover:from-violet-600 hover:to-purple-700 shadow-md`;
        } else {
            return `${baseClasses} bg-gradient-to-r from-emerald-500 to-green-600 text-white hover:from-emerald-600 hover:to-green-700 shadow-md`;
        }
    } else {
        return `${baseClasses} bg-white border-2 border-gray-300 text-gray-600 hover:bg-gray-50 hover:border-gray-400 shadow-sm`;
    }
};
</script>

<template>
    <div class="fixed right-0 bottom-0 left-0 z-30 border-t border-gray-200 bg-white/95 shadow-lg backdrop-blur-sm">
        <!-- Mobile Layout -->
        <div class="block px-3 py-2 lg:hidden">
            <div class="mb-3 flex items-center justify-center gap-1">
                <button
                    @click="handleNavigate('part1')"
                    class="flex-1 rounded-md px-2 py-2 text-xs font-medium transition-all duration-200"
                    :class="
                        activePart === 'part1'
                            ? 'bg-gradient-to-r from-teal-500 to-cyan-600 text-white shadow-md'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    "
                >
                    Part 1
                </button>
                <button
                    @click="handleNavigate('part2')"
                    class="flex-1 rounded-md px-2 py-2 text-xs font-medium transition-all duration-200"
                    :class="
                        activePart === 'part2'
                            ? 'bg-gradient-to-r from-rose-500 to-pink-600 text-white shadow-md'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    "
                >
                    Part 2
                </button>
                <button
                    @click="handleNavigate('part3')"
                    class="flex-1 rounded-md px-2 py-2 text-xs font-medium transition-all duration-200"
                    :class="
                        activePart === 'part3'
                            ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-md'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    "
                >
                    Part 3
                </button>
                <button
                    @click="handleNavigate('part4')"
                    class="flex-1 rounded-md px-2 py-2 text-xs font-medium transition-all duration-200"
                    :class="
                        activePart === 'part4'
                            ? 'bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-md'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    "
                >
                    Part 4
                </button>
            </div>

            <div class="flex flex-wrap justify-center gap-2">
                <button
                    v-for="n in getQuestionNumbers()"
                    :key="n"
                    @click="handleQuestionClick(n)"
                    class="font-bold shadow-sm transition-all duration-200 focus:ring-2 focus:ring-offset-1 focus:outline-none active:scale-95"
                    :class="[getButtonClasses(n)]"
                >
                    {{ n }}
                </button>
            </div>
        </div>

        <!-- Desktop Layout -->
        <div class="hidden px-4 py-3 lg:block">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-center justify-center gap-2 xl:justify-start">
                        <button
                            @click="handleNavigate('part1')"
                            class="flex items-center gap-2 rounded-lg px-4 py-2 font-medium transition-all duration-200"
                            :class="
                                activePart === 'part1'
                                    ? 'bg-gradient-to-r from-teal-500 to-cyan-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md"
                                :class="activePart === 'part1' ? 'bg-white/20' : 'bg-teal-100'"
                            >
                                <svg
                                    class="h-3 w-3"
                                    :class="activePart === 'part1' ? 'text-white' : 'text-teal-600'"
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
                                    ? 'bg-gradient-to-r from-rose-500 to-pink-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md"
                                :class="activePart === 'part2' ? 'bg-white/20' : 'bg-rose-100'"
                            >
                                <svg
                                    class="h-3 w-3"
                                    :class="activePart === 'part2' ? 'text-white' : 'text-rose-600'"
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
                                    ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md"
                                :class="activePart === 'part3' ? 'bg-white/20' : 'bg-violet-100'"
                            >
                                <svg
                                    class="h-3 w-3"
                                    :class="activePart === 'part3' ? 'text-white' : 'text-violet-600'"
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
                                    ? 'bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            "
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md"
                                :class="activePart === 'part4' ? 'bg-white/20' : 'bg-emerald-100'"
                            >
                                <svg
                                    class="h-3 w-3"
                                    :class="activePart === 'part4' ? 'text-white' : 'text-emerald-600'"
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

                    <div class="flex justify-center xl:justify-end">
                        <div class="flex max-w-lg flex-wrap justify-center gap-2.5">
                            <button
                                v-for="n in getQuestionNumbers()"
                                :key="n"
                                @click="handleQuestionClick(n)"
                                class="transition-all duration-200 hover:scale-105 focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                :class="[getDesktopButtonClasses(n)]"
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
