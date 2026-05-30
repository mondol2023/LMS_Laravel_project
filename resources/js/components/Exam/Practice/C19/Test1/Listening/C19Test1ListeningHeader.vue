<script setup lang="ts">
import { FileText, Headphones, Maximize2, Minimize2, Pause, Play, Send, SkipBack, SkipForward, Volume2, VolumeX } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    testTitle?: string;
    timeLimit?: number; // in minutes
    currentSection?: string;
    isTimeUp?: boolean;
    part1Notes?: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>;
    part2Notes?: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>;
    part3Notes?: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>;
    part4Notes?: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>;
}

const props = withDefaults(defineProps<Props>(), {
    testTitle: 'IELTS Academic Listening Test',
    timeLimit: 32,
    currentSection: 'Listening',
    isTimeUp: false,
    part1Notes: () => [],
    part2Notes: () => [],
    part3Notes: () => [],
    part4Notes: () => [],
});

// Audio state
const audioRef = ref<HTMLAudioElement | null>(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(0.7); // Default volume
const isMuted = ref(false);
const isDraggingProgress = ref(false);
const isSeeking = ref(false);

// Timer functionality with persistence
const timeRemaining = ref(props.timeLimit * 60);
const testStartTime = ref<number | null>(null);
let timerInterval: number | null = null;

// Timer functionality

// Fullscreen state
const isFullscreen = ref(false);

// Notes functionality
const showNotesDrawer = ref(false);

const toggleNotesDrawer = () => {
    showNotesDrawer.value = !showNotesDrawer.value;
};

const closeNotesDrawer = () => {
    showNotesDrawer.value = false;
};

// Computed property to combine all notes from all parts
const allNotes = computed(() => {
    const part1WithLabel = props.part1Notes.map((note) => ({ ...note, part: 'Part 1' as const }));
    const part2WithLabel = props.part2Notes.map((note) => ({ ...note, part: 'Part 2' as const }));
    const part3WithLabel = props.part3Notes.map((note) => ({ ...note, part: 'Part 3' as const }));
    const part4WithLabel = props.part4Notes.map((note) => ({ ...note, part: 'Part 4' as const }));
    return [...part1WithLabel, ...part2WithLabel, ...part3WithLabel, ...part4WithLabel];
});

const totalNotesCount = computed(() => {
    return props.part1Notes.length + props.part2Notes.length + props.part3Notes.length + props.part4Notes.length;
});

const formatTime = (seconds: number): string => {
    if (!Number.isFinite(seconds)) return '00:00';

    const totalSeconds = Math.floor(seconds); // ⬅️ KEY FIX

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const remainingSeconds = totalSeconds % 60;

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
    console.log('Listening timer initialized');
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

// Audio functions
const loadAudio = () => {
    if (audioRef.value) {
        audioRef.value.src = '/audio/listening15.mp3';
        audioRef.value.load();
    }
};

// Fullscreen functionality
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

// Audio event handlers
const onAudioPlay = () => {
    isPlaying.value = true;
};

const onAudioPause = () => {
    isPlaying.value = false;
};

const onTimeUpdate = () => {
    if (audioRef.value && !isDraggingProgress.value) {
        currentTime.value = audioRef.value.currentTime;
    }
};

const onLoadedMetadata = () => {
    if (audioRef.value) {
        duration.value = audioRef.value.duration;
        audioRef.value.volume = volume.value; // Set initial volume
    }
};

const onAudioError = () => {
    console.error('Audio failed to load. Please check if the audio file exists at /audio/listening9.mp3');
};

const togglePlayPause = () => {
    if (audioRef.value) {
        if (isPlaying.value) {
            audioRef.value.pause();
        } else {
            audioRef.value.play();
        }
    }
};

const seekAudio = (event: Event) => {
    if (audioRef.value) {
        const target = event.target as HTMLInputElement;
        const seekTime = parseFloat(target.value);
        audioRef.value.currentTime = seekTime;
        currentTime.value = seekTime; // Update immediately for responsiveness
    }
};

const changeVolume = (event: Event) => {
    if (audioRef.value) {
        const target = event.target as HTMLInputElement;
        const newVolume = parseFloat(target.value);
        audioRef.value.volume = newVolume;
        volume.value = newVolume;
        if (newVolume > 0 && isMuted.value) {
            isMuted.value = false;
        } else if (newVolume === 0 && !isMuted.value) {
            isMuted.value = true;
        }
    }
};

const toggleMute = () => {
    if (audioRef.value) {
        if (isMuted.value) {
            audioRef.value.volume = volume.value > 0 ? volume.value : 0.7; // Restore to previous volume or default
            isMuted.value = false;
        } else {
            audioRef.value.volume = 0;
            isMuted.value = true;
        }
    }
};

// Computed progress percentage
const progressPercent = computed(() => {
    if (!duration.value) return 0;
    return (currentTime.value / duration.value) * 100;
});

// Skip forward/backward
const skipForward = () => {
    if (audioRef.value) {
        audioRef.value.currentTime = Math.min(audioRef.value.currentTime + 10, duration.value);
    }
};

const skipBackward = () => {
    if (audioRef.value) {
        audioRef.value.currentTime = Math.max(audioRef.value.currentTime - 10, 0);
    }
};

// Seek by clicking on progress bar
const seekByClick = (event: MouseEvent) => {
    if (audioRef.value && duration.value) {
        const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
        const percent = (event.clientX - rect.left) / rect.width;
        audioRef.value.currentTime = percent * duration.value;
    }
};

// Set volume by clicking on volume bar
const setVolumeByClick = (event: MouseEvent) => {
    if (audioRef.value) {
        const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
        const percent = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
        audioRef.value.volume = percent;
        volume.value = percent;
        isMuted.value = percent === 0;
    }
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
    loadAudio();
    initializeTimer();
    startTimer();

    if (audioRef.value) {
        audioRef.value.volume = volume.value;
        if (isMuted.value) {
            audioRef.value.volume = 0;
        }
    }

    // Listen for fullscreen changes
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('msfullscreenchange', handleFullscreenChange);

    // Auto-start audio
    setTimeout(() => {
        if (audioRef.value) {
            audioRef.value
                .play()
                .then(() => {
                    console.log('Audio started automatically');
                })
                .catch((e) => {
                    console.log('Auto-play prevented by browser:', e);
                });
        }
    }, 500);
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
    <nav class="sticky top-0 z-50 border-b border-gray-200/50 bg-gradient-to-r from-slate-50 via-white to-slate-50 shadow-lg backdrop-blur-xl">
        <div class="container mx-auto px-3 sm:px-4 lg:px-6">
            <!-- Mobile Layout -->
            <div class="block md:hidden">
                <!-- Top Row - Logo and Submit -->
                <div class="flex h-14 items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 shadow-lg shadow-purple-500/30"
                            >
                                <Headphones class="h-5 w-5 text-white" />
                            </div>
                            <div
                                class="absolute -right-0.5 -bottom-0.5 h-3 w-3 rounded-full border-2 border-white bg-green-500"
                                :class="{ 'animate-pulse': isPlaying }"
                            ></div>
                        </div>
                        <h1 class="bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-sm font-bold text-transparent">IELTS Listening</h1>
                    </div>
                    <button
                        @click="handleSubmit"
                        class="group flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-rose-500 to-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-lg shadow-red-500/30 transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-red-500/40 active:scale-95"
                    >
                        <Send class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                        Submit
                    </button>
                </div>

                <!-- Audio Player Card - Mobile (Light Theme) -->
                <div class="mb-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
                    <!-- Controls Row -->
                    <div class="flex items-center gap-3">
                        <!-- Skip Back -->
                        <button
                            @click="skipBackward"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition-all hover:bg-gray-100 hover:text-violet-600 active:scale-95"
                        >
                            <SkipBack class="h-4 w-4" />
                        </button>

                        <!-- Play/Pause Button -->
                        <button
                            @click="togglePlayPause"
                            class="group relative flex h-11 w-11 items-center justify-center rounded-full bg-violet-600 text-white shadow-md transition-all duration-200 hover:bg-violet-700 hover:shadow-lg active:scale-95"
                        >
                            <Transition name="icon-fade" mode="out-in">
                                <Play v-if="!isPlaying" class="ml-0.5 h-5 w-5" fill="currentColor" :key="'play'" />
                                <Pause v-else class="h-5 w-5" fill="currentColor" :key="'pause'" />
                            </Transition>
                        </button>

                        <!-- Skip Forward -->
                        <button
                            @click="skipForward"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition-all hover:bg-gray-100 hover:text-violet-600 active:scale-95"
                        >
                            <SkipForward class="h-4 w-4" />
                        </button>

                        <!-- Time Display -->
                        <div class="ml-auto font-mono text-xs">
                            <span class="font-medium text-violet-600">{{ formatTime(currentTime) }}</span>
                            <span class="text-gray-400"> / </span>
                            <span class="text-gray-500">{{ formatTime(duration) }}</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="group mt-3">
                        <div class="relative h-1.5 cursor-pointer overflow-hidden rounded-full bg-gray-200" @click="seekByClick">
                            <div
                                class="absolute inset-y-0 left-0 rounded-full bg-violet-500 transition-all duration-100"
                                :style="{ width: progressPercent + '%' }"
                            ></div>
                            <!-- Thumb on hover -->
                            <div
                                class="absolute top-1/2 h-3.5 w-3.5 -translate-y-1/2 rounded-full bg-violet-600 opacity-0 shadow-md transition-all group-hover:opacity-100"
                                :style="{ left: `calc(${progressPercent}% - 7px)` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Volume Control -->
                    <div class="mt-3 flex items-center gap-2">
                        <button @click="toggleMute" class="text-gray-500 transition-all hover:text-violet-600">
                            <VolumeX v-if="isMuted" class="h-4 w-4" />
                            <Volume2 v-else class="h-4 w-4" />
                        </button>
                        <div class="relative h-1 flex-1 cursor-pointer overflow-hidden rounded-full bg-gray-200" @click="setVolumeByClick">
                            <div
                                class="absolute inset-y-0 left-0 rounded-full bg-emerald-500 transition-all duration-100"
                                :style="{ width: (isMuted ? 0 : volume * 100) + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons Row - Mobile -->
                <div class="flex items-center justify-between pb-3">
                    <!-- Notes Toggle -->
                    <button
                        @click="toggleNotesDrawer"
                        :disabled="isTimeUp"
                        class="relative flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 transition-all hover:bg-slate-200 disabled:opacity-50"
                    >
                        <FileText class="h-4 w-4" />
                        Notes
                        <span
                            v-if="totalNotesCount > 0"
                            class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-[10px] font-bold text-white shadow-lg"
                        >
                            {{ totalNotesCount }}
                        </span>
                    </button>

                    <!-- Fullscreen Toggle -->
                    <button
                        @click="toggleFullscreen"
                        class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-medium transition-all"
                        :class="isFullscreen ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    >
                        <Minimize2 v-if="isFullscreen" class="h-4 w-4" />
                        <Maximize2 v-else class="h-4 w-4" />
                        {{ isFullscreen ? 'Exit' : 'Fullscreen' }}
                    </button>
                </div>
            </div>

            <!-- Desktop Layout -->
            <div class="hidden h-20 items-center justify-between gap-6 md:flex">
                <!-- Left Section - Logo -->
                <div class="flex min-w-fit items-center gap-3">
                    <div class="relative">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 shadow-lg shadow-purple-500/30 transition-transform hover:scale-105"
                        >
                            <Headphones class="h-6 w-6 text-white" />
                        </div>
                        <div
                            class="absolute -right-0.5 -bottom-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-green-500 shadow-lg"
                            :class="{ 'animate-pulse': isPlaying }"
                        ></div>
                    </div>
                    <div>
                        <h1 class="bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-lg font-bold text-transparent">Nextive Solution</h1>
                        <p class="text-xs text-slate-500">Cambridge 19 - Listening</p>
                    </div>
                </div>

                <!-- Center Section - Audio Player (Light Theme) -->
                <div class="max-w-xl flex-1">
                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-2.5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <!-- Controls -->
                            <div class="flex items-center gap-1">
                                <button
                                    @click="skipBackward"
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition-all hover:bg-gray-100 hover:text-violet-600 active:scale-95"
                                >
                                    <SkipBack class="h-4 w-4" />
                                </button>

                                <!-- Play/Pause -->
                                <button
                                    @click="togglePlayPause"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-600 text-white shadow-md transition-all duration-200 hover:bg-violet-700 hover:shadow-lg active:scale-95"
                                >
                                    <Transition name="icon-fade" mode="out-in">
                                        <Play v-if="!isPlaying" class="ml-0.5 h-5 w-5" fill="currentColor" :key="'play'" />
                                        <Pause v-else class="h-5 w-5" fill="currentColor" :key="'pause'" />
                                    </Transition>
                                </button>

                                <button
                                    @click="skipForward"
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition-all hover:bg-gray-100 hover:text-violet-600 active:scale-95"
                                >
                                    <SkipForward class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Time -->
                            <span class="min-w-[42px] font-mono text-xs font-medium text-violet-600">{{ formatTime(currentTime) }}</span>

                            <!-- Progress Bar -->
                            <div class="group flex-1 cursor-pointer" @click="seekByClick">
                                <div class="relative h-1.5 overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        class="absolute inset-y-0 left-0 rounded-full bg-violet-500 transition-all duration-100"
                                        :style="{ width: progressPercent + '%' }"
                                    ></div>
                                    <!-- Thumb -->
                                    <div
                                        class="absolute top-1/2 h-3.5 w-3.5 -translate-y-1/2 rounded-full bg-violet-600 opacity-0 shadow-md transition-all group-hover:opacity-100"
                                        :style="{ left: `calc(${progressPercent}% - 7px)` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Duration -->
                            <span class="min-w-[42px] font-mono text-xs text-gray-500">{{ formatTime(duration) }}</span>

                            <!-- Volume -->
                            <div class="ml-1 flex items-center gap-2 border-l border-gray-200 pl-2">
                                <button @click="toggleMute" class="text-gray-500 transition-all hover:text-violet-600">
                                    <VolumeX v-if="isMuted" class="h-4 w-4" />
                                    <Volume2 v-else class="h-4 w-4" />
                                </button>
                                <div
                                    class="group relative h-1 w-16 cursor-pointer overflow-hidden rounded-full bg-gray-200"
                                    @click="setVolumeByClick"
                                >
                                    <div
                                        class="absolute inset-y-0 left-0 rounded-full bg-emerald-500 transition-all duration-100"
                                        :style="{ width: (isMuted ? 0 : volume * 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Section - Actions -->
                <div class="flex min-w-fit items-center gap-2">
                    <!-- Notes Toggle -->
                    <button
                        @click="toggleNotesDrawer"
                        :disabled="isTimeUp"
                        class="group relative flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition-all hover:bg-slate-200 hover:shadow-md disabled:opacity-50"
                    >
                        <FileText class="h-4 w-4 transition-transform group-hover:scale-110" />
                        <span class="hidden lg:block">Notes</span>
                        <span
                            v-if="totalNotesCount > 0"
                            class="animate-bounce-subtle absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-[10px] font-bold text-white shadow-lg"
                        >
                            {{ totalNotesCount }}
                        </span>
                    </button>

                    <!-- Fullscreen Toggle -->
                    <button
                        @click="toggleFullscreen"
                        class="group flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-all hover:shadow-md"
                        :class="isFullscreen ? 'bg-violet-100 text-violet-700 hover:bg-violet-200' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    >
                        <Minimize2 v-if="isFullscreen" class="h-4 w-4 transition-transform group-hover:scale-110" />
                        <Maximize2 v-else class="h-4 w-4 transition-transform group-hover:scale-110" />
                        <span class="hidden lg:block">{{ isFullscreen ? 'Exit' : 'Fullscreen' }}</span>
                    </button>

                    <!-- Submit Button -->
                    <button
                        @click="handleSubmit"
                        class="group flex items-center gap-2 rounded-xl bg-gradient-to-r from-rose-500 to-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-red-500/40 active:scale-95"
                    >
                        <Send class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                        <span class="hidden lg:block">Submit</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden Audio Element -->
        <audio
            ref="audioRef"
            @play="onAudioPlay"
            @pause="onAudioPause"
            @timeupdate="onTimeUpdate"
            @loadedmetadata="onLoadedMetadata"
            @error="onAudioError"
            preload="auto"
        />
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
                                    <span>Part 1: {{ part1Notes.length }}</span>
                                    <span>•</span>
                                    <span>Part 2: {{ part2Notes.length }}</span>
                                    <span>•</span>
                                    <span>Part 3: {{ part3Notes.length }}</span>
                                    <span>•</span>
                                    <span>Part 4: {{ part4Notes.length }}</span>
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
                        <p class="max-w-xs text-sm text-gray-600">
                            Select text in the listening questions and click "Note" to add notes and reminders.
                        </p>
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
                                        'bg-blue-100 text-blue-800': note.part === 'Part 1',
                                        'bg-green-100 text-green-800': note.part === 'Part 2',
                                        'bg-purple-100 text-purple-800': note.part === 'Part 3',
                                        'bg-orange-100 text-orange-800': note.part === 'Part 4',
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
/* Icon Fade Transition */
.icon-fade-enter-active,
.icon-fade-leave-active {
    transition: all 0.15s ease;
}

.icon-fade-enter-from,
.icon-fade-leave-to {
    opacity: 0;
    transform: scale(0.8);
}

/* Subtle bounce animation for notification badges */
.animate-bounce-subtle {
    animation: bounce-subtle 2s ease-in-out infinite;
}

@keyframes bounce-subtle {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-2px);
    }
}

/* Notes Drawer Transitions */
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
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>
