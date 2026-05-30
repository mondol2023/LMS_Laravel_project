<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    testTitle?: string;
    timeLimit?: number; // in minutes
    currentSection?: string;
    part1Notes?: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>;
    part2Notes?: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>;
    part3Notes?: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>;
}

const props = withDefaults(defineProps<Props>(), {
    testTitle: 'IELTS Academic Reading Test',
    timeLimit: 60,
    currentSection: 'Reading',
    part1Notes: () => [],
    part2Notes: () => [],
    part3Notes: () => [],
});

// Timer functionality with persistence
const timeRemaining = ref(props.timeLimit * 60);
const testStartTime = ref<number | null>(null);
let timerInterval: number | null = null;

// Timer functionality

const formatTime = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;

    if (hours > 0) {
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

const getTimerColor = (): string => {
    const percentage = (timeRemaining.value / (props.timeLimit * 60)) * 100;
    if (percentage <= 5) return 'text-red-600 animate-pulse';
    if (percentage <= 10) return 'text-red-600';
    if (percentage <= 25) return 'text-orange-600';
    return 'text-purple-600';
};

const getTimerBgColor = (): string => {
    const percentage = (timeRemaining.value / (props.timeLimit * 60)) * 100;
    if (percentage <= 5) return 'bg-red-50 border-red-200';
    if (percentage <= 10) return 'bg-red-50 border-red-200';
    if (percentage <= 25) return 'bg-orange-50 border-orange-200';
    return 'bg-purple-50 border-purple-200';
};

const initializeTimer = () => {
    testStartTime.value = Date.now();
    timeRemaining.value = props.timeLimit * 60;
    console.log('Reading timer initialized');
};

const updateTimer = () => {
    if (testStartTime.value) {
        const elapsed = Math.floor((Date.now() - testStartTime.value) / 1000);
        const remaining = Math.max(0, props.timeLimit * 60 - elapsed);
        timeRemaining.value = remaining;
        if (remaining === 0) {
            stopTimer();
            emit('timeUp');
        }
    }
};

const startTimer = () => {
    if (timerInterval) return;

    timerInterval = setInterval(updateTimer, 1000);
};

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
};
// Notes functionality
const showNotes = ref(false);
const toggleNotes = () => {
    showNotes.value = !showNotes.value;
    emit('toggleNotes', showNotes.value);
};

// Notes drawer functionality
const showNotesDrawer = ref(false);

const toggleNotesDrawer = () => {
    showNotesDrawer.value = !showNotesDrawer.value;
};

const closeNotesDrawer = () => {
    showNotesDrawer.value = false;
};

// Computed property to combine all notes from all parts
const allNotes = computed(() => {
    const part1WithLabel = props.part1Notes.map((note) => ({ ...note, part: 'Passage 1' as const }));
    const part2WithLabel = props.part2Notes.map((note) => ({ ...note, part: 'Passage 2' as const }));
    const part3WithLabel = props.part3Notes.map((note) => ({ ...note, part: 'Passage 3' as const }));
    return [...part1WithLabel, ...part2WithLabel, ...part3WithLabel];
});

const totalNotesCount = computed(() => {
    return props.part1Notes.length + props.part2Notes.length + props.part3Notes.length;
});

// Fullscreen functionality
const isFullscreen = ref(false);

const toggleFullscreen = () => {
    if (!isFullscreen.value) {
        // Enter fullscreen
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        } else if ((document.documentElement as any).webkitRequestFullscreen) {
            (document.documentElement as any).webkitRequestFullscreen();
        } else if ((document.documentElement as any).msRequestFullscreen) {
            (document.documentElement as any).msRequestFullscreen();
        }
    } else {
        // Exit fullscreen
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if ((document as any).webkitExitFullscreen) {
            (document as any).webkitExitFullscreen();
        } else if ((document as any).msExitFullscreen) {
            (document as any).msExitFullscreen();
        }
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!(document.fullscreenElement || (document as any).webkitFullscreenElement || (document as any).msFullscreenElement);
};

// Submit functionality
const handleSubmit = () => {
    emit('showSubmitModal');
};

// Review functionality
const handleReview = () => {
    emit('showReviewModal');
};

// Focus note functionality
const focusNote = (note: (typeof allNotes.value)[0]) => {
    // Emit event to parent to focus on the note
    emit('focusNote', note);
    // Close the drawer
    closeNotesDrawer();
};

// Delete note functionality
const deleteNote = (noteId: number, part: string) => {
    emit('deleteNote', { id: noteId, part });
};

// Emits
const emit = defineEmits<{
    timeUp: [];
    toggleNotes: [show: boolean];
    submitTest: [];
    showSubmitModal: [];
    showReviewModal: [];
    focusNote: [note: { id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }];
    deleteNote: [data: { id: number; part: string }];
}>();

// Lifecycle
onMounted(() => {
    initializeTimer();
    startTimer();

    // Listen for fullscreen changes
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('msfullscreenchange', handleFullscreenChange);
});

onUnmounted(() => {
    stopTimer();

    // Remove fullscreen event listeners
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.removeEventListener('msfullscreenchange', handleFullscreenChange);
});
</script>

<template>
    <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur-sm">
        <div class="container mx-auto px-3 sm:px-4 lg:px-6">
            <!-- Mobile Layout -->
            <div class="block md:hidden">
                <!-- Top Row - Logo and Buttons -->
                <div class="flex h-14 items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📝</span>
                        <h1 class="text-sm font-bold text-gray-900">IELTS Mock Test</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="handleReview"
                            class="flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                ></path>
                            </svg>
                            Review
                        </button>
                        <button
                            @click="handleSubmit"
                            class="flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-red-700"
                        >
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Submit
                        </button>
                    </div>
                </div>

                <!-- Bottom Row - Timer, Notes, and Controls -->
                <div class="flex h-12 items-center justify-between border-t border-gray-200 py-2">
                    <!-- Timer -->
                    <div class="flex items-center gap-2 rounded-lg border px-2 py-1 transition-all duration-300" :class="getTimerBgColor()">
                        <svg class="h-3 w-3" :class="getTimerColor()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <div class="font-mono text-sm font-bold" :class="getTimerColor()">
                            {{ formatTime(timeRemaining) }}
                        </div>
                    </div>

                    <!-- Notes Toggle -->
                    <button
                        @click="toggleNotesDrawer"
                        class="relative flex items-center justify-center rounded-lg p-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100"
                        title="Notes"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            ></path>
                        </svg>
                        <span
                            v-if="totalNotesCount > 0"
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-indigo-600 text-[9px] text-white"
                        >
                            {{ totalNotesCount }}
                        </span>
                    </button>

                    <!-- Fullscreen Toggle -->
                    <button
                        @click="toggleFullscreen"
                        class="flex items-center justify-center rounded-lg p-1.5 text-sm font-medium transition-colors hover:bg-gray-100"
                        :class="isFullscreen ? 'bg-purple-50 text-purple-700' : 'text-gray-700'"
                        :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'"
                    >
                        <svg v-if="!isFullscreen" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
                            ></path>
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 9V4.5M9 9H4.5M9 9L3.5 3.5M15 9h4.5M15 9V4.5M15 9l5.5-5.5M9 15v4.5M9 15H4.5M9 15l-5.5 5.5M15 15h4.5M15 15v4.5m0-4.5l5.5 5.5"
                            ></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Desktop Layout -->
            <div class="hidden h-16 items-center justify-between md:flex">
                <!-- Left Section - Logo -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">📝</span>
                        <h1 class="text-lg font-bold text-gray-900">IELTS Mock Test</h1>
                    </div>
                </div>

                <!-- Center Section - Timer -->
                <div class="flex items-center gap-4">
                    <div
                        class="flex items-center gap-3 rounded-xl border-2 px-4 py-1 shadow-lg transition-all duration-300"
                        :class="getTimerBgColor()"
                    >
                        <div class="relative">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/60 shadow-sm backdrop-blur-sm">
                                <svg class="h-5 w-5" :class="getTimerColor()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="font-mono text-2xl leading-none font-bold" :class="getTimerColor()">
                                {{ formatTime(timeRemaining) }}
                            </div>
                            <div class="mt-1 text-xs font-medium text-gray-600">Time Remaining</div>
                        </div>
                    </div>
                </div>

                <!-- Right Section - Tools -->
                <div class="flex items-center gap-2">
                    <!-- Notes Toggle -->
                    <button
                        @click="toggleNotesDrawer"
                        class="relative flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100"
                        title="Notes"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            ></path>
                        </svg>
                        <span class="hidden lg:block">Notes</span>
                        <span
                            v-if="totalNotesCount > 0"
                            class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[10px] text-white"
                        >
                            {{ totalNotesCount }}
                        </span>
                    </button>

                    <!-- Fullscreen Toggle -->
                    <button
                        @click="toggleFullscreen"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-100"
                        :class="isFullscreen ? 'bg-purple-50 text-purple-700' : 'text-gray-700'"
                        :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'"
                    >
                        <svg v-if="!isFullscreen" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
                            ></path>
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 9V4.5M9 9H4.5M9 9L3.5 3.5M15 9h4.5M15 9V4.5M15 9l5.5-5.5M9 15v4.5M9 15H4.5M9 15l-5.5 5.5M15 15h4.5M15 15v4.5m0-4.5l5.5 5.5"
                            ></path>
                        </svg>
                        <span class="hidden lg:block">{{ isFullscreen ? 'Exit' : 'Zoom' }}</span>
                    </button>

                    <!-- Review Button -->
                    <!--                    <button-->
                    <!--                        @click="handleReview"-->
                    <!--                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"-->
                    <!--                        title="Review Answers"-->
                    <!--                    >-->
                    <!--                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
                    <!--                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>-->
                    <!--                        </svg>-->
                    <!--                        <span class="hidden lg:block">Review</span>-->
                    <!--                    </button>-->

                    <!-- Submit Button -->
                    <button
                        @click="handleSubmit"
                        class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        <span class="hidden lg:block">Submit</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Notes Drawer -->
    <Teleport to="body">
        <!-- Drawer -->
        <Transition name="slide">
            <div v-if="showNotesDrawer" class="fixed top-0 right-0 z-[9999] flex h-full w-full flex-col overflow-hidden bg-white shadow-2xl sm:w-96">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">My Notes</h3>
                                <div class="flex gap-2 text-xs text-white/90">
                                    <span>Passage 1: {{ part1Notes.length }}</span>
                                    <span>•</span>
                                    <span>Passage 2: {{ part2Notes.length }}</span>
                                    <span>•</span>
                                    <span>Passage 3: {{ part3Notes.length }}</span>
                                </div>
                            </div>
                        </div>
                        <button
                            @click="closeNotesDrawer"
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 text-white transition-colors hover:bg-white/30"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                    <div v-if="allNotes.length === 0" class="flex h-full flex-col items-center justify-center py-12 text-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </div>
                        <h4 class="mb-2 text-lg font-semibold text-gray-900">No notes yet</h4>
                        <p class="max-w-xs text-sm text-gray-600">Select text in the passage and click "Note" to add notes and reminders.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="note in allNotes"
                            :key="note.id"
                            class="group rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:border-indigo-300 hover:shadow-md"
                        >
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-blue-100 text-blue-800': note.part === 'Passage 1',
                                        'bg-green-100 text-green-800': note.part === 'Passage 2',
                                        'bg-purple-100 text-purple-800': note.part === 'Passage 3',
                                    }"
                                >
                                    {{ note.part }}
                                </span>
                                <button
                                    @click.stop="deleteNote(note.id, note.part)"
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-red-50 text-red-600 opacity-0 transition-all group-hover:opacity-100 hover:bg-red-100"
                                    title="Delete note"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div
                                @click="focusNote(note)"
                                class="mb-2 cursor-pointer rounded border border-yellow-200 bg-yellow-50 px-3 py-2 transition-colors hover:bg-yellow-100"
                            >
                                <p class="text-sm font-medium text-gray-900 italic">"{{ note.selectedText }}"</p>
                            </div>
                            <div
                                @click="focusNote(note)"
                                class="cursor-pointer rounded border border-indigo-200 bg-indigo-50 px-3 py-2 transition-colors hover:bg-indigo-100"
                            >
                                <p class="text-sm whitespace-pre-wrap text-gray-700">{{ note.note }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>
