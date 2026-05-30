<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// Get username from page props (passed from controller)
const page = usePage();
const username = computed(() => (page.props as any).username || 'Guest');

interface Props {
    testTitle?: string;
    timeLimit?: number;
    currentSection?: string;
    isTimeUp?: boolean;
    audioVolume?: number;
    examStarted?: boolean;
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
    audioVolume: 100,
    examStarted: true,
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

// Timer functionality
const timeRemaining = ref(props.timeLimit * 60);
const testStartTime = ref<number | null>(null);
let timerInterval: number | null = null;

// WiFi connection status
const isOnline = ref(navigator.onLine);
const connectionStrength = ref<'strong' | 'medium' | 'weak'>('strong');

// Options modal state
const showOptionsModal = ref(false);
const optionsView = ref<'main' | 'contrast' | 'textSize' | 'instructions'>('main');

// Contrast theme
const contrastTheme = ref<'black-on-white' | 'white-on-black' | 'yellow-on-black'>('black-on-white');

// Text size
const textSize = ref<'regular' | 'large' | 'extra-large'>('regular');

// Fullscreen state
const isFullscreen = ref(false);

// Pause test functionality
const isPaused = ref(false);

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
    const part1WithLabel = props.part1Notes.map((note) => ({ ...note, part: 'Part 1' as const }));
    const part2WithLabel = props.part2Notes.map((note) => ({ ...note, part: 'Part 2' as const }));
    const part3WithLabel = props.part3Notes.map((note) => ({ ...note, part: 'Part 3' as const }));
    const part4WithLabel = props.part4Notes.map((note) => ({ ...note, part: 'Part 4' as const }));
    return [...part1WithLabel, ...part2WithLabel, ...part3WithLabel, ...part4WithLabel];
});

const totalNotesCount = computed(() => {
    return props.part1Notes.length + props.part2Notes.length + props.part3Notes.length + props.part4Notes.length;
});

// Wake Lock
let wakeLock: WakeLockSentinel | null = null;

// Check if time is critical (less than 1 minute)
const isTimeCritical = computed(() => timeRemaining.value < 60);

// Header background color based on time
const headerBgClass = computed(() => {
    if (isTimeCritical.value) {
        return 'bg-red-100';
    }
    return 'bg-white';
});

// Header text color based on time
const headerTextClass = computed(() => {
    if (isTimeCritical.value) {
        return 'text-red-700';
    }
    return 'text-gray-900';
});

// Format time remaining display
const formatTimeDisplay = computed(() => {
    const minutes = Math.floor(timeRemaining.value / 60);
    const seconds = timeRemaining.value % 60;
    if (minutes > 0) {
        return `${minutes} minute${minutes !== 1 ? 's' : ''} remaining`;
    }
    return `${seconds} second${seconds !== 1 ? 's' : ''} remaining`;
});

const requestWakeLock = async () => {
    try {
        if ('wakeLock' in navigator) {
            wakeLock = await navigator.wakeLock.request('screen');
        }
    } catch {
        // Wake lock request failed
    }
};

const releaseWakeLock = async () => {
    if (wakeLock) {
        await wakeLock.release();
        wakeLock = null;
    }
};

const togglePause = () => {
    isPaused.value = true;
};

const resumeTest = () => {
    isPaused.value = false;
};

const initializeTimer = () => {
    testStartTime.value = Date.now();
    timeRemaining.value = props.timeLimit * 60;
};

const updateTimer = () => {
    if (testStartTime.value) {
        const elapsed = Math.floor((Date.now() - testStartTime.value) / 1000);
        const remaining = Math.max(0, props.timeLimit * 60 - elapsed);
        timeRemaining.value = remaining;
        if (remaining === 0) {
            stopTimer();
            emit('timeUp');
            setTimeout(() => {
                emit('submitTest');
            }, 500);
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
        audioRef.value.src = '/audio/listening20.m4a';
        audioRef.value.load();
    }
};

// Fullscreen change handler
const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

// Audio event handlers
const onAudioPlay = () => {
    isPlaying.value = true;
};

const onAudioPause = () => {
    isPlaying.value = false;
};

const onTimeUpdate = () => {
    if (audioRef.value) {
        currentTime.value = audioRef.value.currentTime;
    }
};

const onLoadedMetadata = () => {
    if (audioRef.value) {
        duration.value = audioRef.value.duration;
    }
};

const onAudioError = () => {};

// Watch for volume changes from parent (footer volume control)
watch(
    () => props.audioVolume,
    (newVolume) => {
        if (audioRef.value) {
            audioRef.value.volume = newVolume / 100;
        }
    },
    { immediate: true },
);

// Options modal functions
const openOptionsModal = () => {
    showOptionsModal.value = true;
    optionsView.value = 'main';
};

const closeOptionsModal = () => {
    showOptionsModal.value = false;
    optionsView.value = 'main';
};

const navigateToContrast = () => {
    optionsView.value = 'contrast';
};

const navigateToTextSize = () => {
    optionsView.value = 'textSize';
};

const navigateToInstructions = () => {
    optionsView.value = 'instructions';
};

const navigateBack = () => {
    optionsView.value = 'main';
};

// Contrast functions
const setContrastTheme = (theme: 'black-on-white' | 'white-on-black' | 'yellow-on-black') => {
    contrastTheme.value = theme;
    emit('contrastChange', theme);
};

// Text size functions
const setTextSize = (size: 'regular' | 'large' | 'extra-large') => {
    textSize.value = size;
    const sizeMap = { regular: 16, large: 20, 'extra-large': 24 };
    emit('fontSizeChange', sizeMap[size]);
};

// Modal styling based on contrast theme
const modalBgClass = computed(() => {
    switch (contrastTheme.value) {
        case 'white-on-black':
            return 'bg-gray-900 text-white';
        case 'yellow-on-black':
            return 'bg-gray-900 text-yellow-400';
        case 'black-on-white':
        default:
            return 'bg-white text-gray-900';
    }
});

const modalTextClass = computed(() => {
    switch (contrastTheme.value) {
        case 'white-on-black':
            return 'text-white';
        case 'yellow-on-black':
            return 'text-yellow-400';
        case 'black-on-white':
        default:
            return 'text-gray-900';
    }
});

const modalBorderClass = computed(() => {
    return contrastTheme.value === 'black-on-white' ? 'border-gray-200' : 'border-gray-700';
});

const modalHoverClass = computed(() => {
    return contrastTheme.value === 'black-on-white' ? 'hover:bg-gray-50' : 'hover:bg-gray-800';
});

// Text size style for modal
const modalTextSizeStyle = computed(() => {
    const sizeMap = { regular: 16, large: 20, 'extra-large': 24 };
    return { fontSize: `${sizeMap[textSize.value]}px` };
});

// Note functionality
const openNotes = () => {
    toggleNotesDrawer();
};

// Focus note functionality
const focusNote = (note: (typeof allNotes.value)[0]) => {
    emit('focusNote', note);
    closeNotesDrawer();
};

// Delete note functionality
const deleteNote = (noteId: number, part: string) => {
    emit('deleteNote', { id: noteId, part });
};

// WiFi status check
const updateConnectionStatus = () => {
    isOnline.value = navigator.onLine;
    // Simulate connection strength based on online status
    if (isOnline.value) {
        connectionStrength.value = 'strong';
    } else {
        connectionStrength.value = 'weak';
    }
};

// Emits
const emit = defineEmits<{
    timeUp: [];
    submitTest: [];
    showSubmitModal: [];
    fontSizeChange: [size: number];
    bgColorChange: [color: 'white' | 'gray'];
    contrastChange: [theme: 'black-on-white' | 'white-on-black' | 'yellow-on-black'];
    focusNote: [note: { id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }];
    deleteNote: [data: { id: number; part: string }];
}>();

// Watch for examStarted to begin timer and audio
watch(
    () => props.examStarted,
    (started) => {
        if (started) {
            initializeTimer();
            startTimer();
            requestWakeLock();
            setTimeout(() => {
                if (audioRef.value) {
                    audioRef.value.play().catch(() => {});
                }
            }, 500);
        }
    },
);

// Lifecycle
onMounted(() => {
    loadAudio();

    document.addEventListener('fullscreenchange', handleFullscreenChange);
    window.addEventListener('online', updateConnectionStatus);
    window.addEventListener('offline', updateConnectionStatus);

    // Only start timer and audio if exam already started (for backwards compatibility)
    if (props.examStarted) {
        initializeTimer();
        startTimer();
        requestWakeLock();

        setTimeout(() => {
            if (audioRef.value) {
                audioRef.value.play().catch(() => {});
            }
        }, 500);
    }
});

onUnmounted(() => {
    stopTimer();
    releaseWakeLock();
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    window.removeEventListener('online', updateConnectionStatus);
    window.removeEventListener('offline', updateConnectionStatus);
});
</script>

<template>
    <nav class="sticky top-0 z-50" :class="headerBgClass">
        <!-- Main Header -->
        <div class="flex h-12 items-center justify-between px-2 sm:h-18 sm:px-4">
            <!-- Left Section: Logo + User Info -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- IELTS Logo -->
                <img src="/images/LandingUI/ielts.png" alt="IELTS" class="ml-4 h-12 w-auto object-contain sm:h-17" />

                <!-- User Info -->
                <div class="flex flex-col leading-tight">
                    <span class="text-xs font-bold sm:text-sm" :class="headerTextClass">
                        {{ username }}
                    </span>
                    <span class="text-[10px] opacity-90 sm:text-xs" :class="headerTextClass">
                        {{ formatTimeDisplay }}
                    </span>
                </div>
            </div>

            <!-- Right Section: Icons -->
            <div class="mr-6 flex items-center gap-3 sm:gap-4">
                <!-- WiFi Icon -->
                <div class="relative" :class="headerTextClass">
                    <svg v-if="isOnline" class="-mt-1 h-6 w-6 sm:h-7 sm:w-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21l-1.41-1.41A2 2 0 0112 18a2 2 0 011.41 1.59L12 21z" />
                        <path
                            d="M12 17c-1.1 0-2.1.45-2.83 1.17l1.41 1.41c.78-.78 2.05-.78 2.83 0l1.41-1.41C14.1 17.45 13.1 17 12 17z"
                            opacity="0.9"
                        />
                        <path
                            d="M12 13c-2.21 0-4.21.9-5.66 2.34l1.41 1.41C8.79 15.71 10.3 15 12 15s3.21.71 4.24 1.76l1.41-1.41C16.21 13.9 14.21 13 12 13z"
                            opacity="0.7"
                        />
                        <path
                            d="M12 9c-3.31 0-6.31 1.35-8.49 3.51l1.41 1.41C6.66 12.28 9.18 11 12 11s5.34 1.28 7.07 3.51l1.41-1.41C18.31 10.35 15.31 9 12 9z"
                            opacity="0.5"
                        />
                    </svg>
                    <svg v-else class="h-5 w-5 text-red-400 sm:h-6 sm:w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21l-1.41-1.41A2 2 0 0112 18a2 2 0 011.41 1.59L12 21z" />
                        <path d="M2.1 3.51L3.51 2.1l18.38 18.38-1.41 1.41L2.1 3.51z" />
                    </svg>
                </div>

                <!-- Pause Button -->
                <button @click="togglePause" class="relative transition-opacity hover:opacity-80" :class="headerTextClass" title="Privacy Mode">
                    <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                <!-- Options/Menu Button (Hamburger) -->
                <button @click="openOptionsModal" class="transition-opacity hover:opacity-80" :class="headerTextClass" title="Options">
                    <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Note Icon -->
                <button @click="openNotes" class="relative transition-opacity hover:opacity-80" :class="headerTextClass" title="Notes">
                    <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>
                    <span
                        v-if="totalNotesCount > 0"
                        class="absolute -top-3 -right-4 flex h-5 w-5 items-center justify-center rounded-full border-1 border-black bg-gray-600 text-[12px] font-bold text-white"
                    >
                        {{ totalNotesCount }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Bottom Border Line -->
        <div class="h-px w-full bg-gray-300"></div>

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

        <!-- Options Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showOptionsModal"
                    class="fixed inset-0 z-[10000] flex items-start justify-center transition-colors duration-300"
                    :class="modalBgClass"
                    :style="modalTextSizeStyle"
                >
                    <!-- Main Options View -->
                    <div v-if="optionsView === 'main'" class="w-full max-w-2xl px-4 py-6">
                        <!-- Header -->
                        <div class="mb-8 flex items-center justify-between">
                            <div></div>
                            <h2 class="text-xl font-semibold" :class="modalTextClass">Options</h2>
                            <button @click="closeOptionsModal" class="text-2xl font-bold transition-opacity hover:opacity-70" :class="modalTextClass">
                                &times;
                            </button>
                        </div>

                        <!-- Menu Items -->
                        <div class="mx-auto max-w-md rounded-lg border" :class="modalBorderClass">
                            <!-- Contrast -->
                            <button
                                @click="navigateToContrast"
                                class="flex w-full items-center justify-between border-b px-4 py-4 transition-colors"
                                :class="[modalTextClass, modalBorderClass, modalHoverClass]"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" stroke-width="2" />
                                        <path stroke-width="2" d="M12 2v20" />
                                        <path d="M12 2a10 10 0 010 20" fill="currentColor" />
                                    </svg>
                                    <span>Contrast</span>
                                </div>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <!-- Text Size -->
                            <button
                                @click="navigateToTextSize"
                                class="flex w-full items-center justify-between border-b px-4 py-4 transition-colors"
                                :class="[modalTextClass, modalBorderClass, modalHoverClass]"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="11" cy="11" r="8" stroke-width="2" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35" />
                                    </svg>
                                    <span>Text size</span>
                                </div>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <!-- Test Instructions -->
                            <button
                                @click="navigateToInstructions"
                                class="flex w-full items-center justify-between px-4 py-4 transition-colors"
                                :class="[modalTextClass, modalHoverClass]"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <span>Test Instructions</span>
                                </div>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Contrast View -->
                    <div v-else-if="optionsView === 'contrast'" class="w-full max-w-3xl px-4 py-6">
                        <!-- Header -->
                        <div class="mb-8 flex items-center justify-between">
                            <button @click="navigateBack" class="flex items-center gap-2 transition-opacity hover:opacity-70" :class="modalTextClass">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Options</span>
                            </button>
                            <h2 class="text-xl font-semibold" :class="modalTextClass">Contrast</h2>
                            <button @click="closeOptionsModal" class="text-2xl font-bold transition-opacity hover:opacity-70" :class="modalTextClass">
                                &times;
                            </button>
                        </div>

                        <!-- Contrast Options -->
                        <div class="mx-auto max-w-md overflow-hidden rounded-lg border" :class="modalBorderClass">
                            <!-- Black on White -->
                            <button
                                @click="setContrastTheme('black-on-white')"
                                class="flex w-full items-center gap-3 border-b border-gray-200 bg-white px-4 py-4 text-gray-900"
                            >
                                <svg
                                    v-if="contrastTheme === 'black-on-white'"
                                    class="h-5 w-5 text-gray-900"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <div v-else class="h-5 w-5"></div>
                                <span>Black on white</span>
                            </button>

                            <!-- White on Black -->
                            <button
                                @click="setContrastTheme('white-on-black')"
                                class="flex w-full items-center gap-3 border-b border-gray-200 bg-gray-900 px-4 py-4 text-white"
                            >
                                <svg v-if="contrastTheme === 'white-on-black'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <div v-else class="h-5 w-5"></div>
                                <span>White on black</span>
                            </button>

                            <!-- Yellow on Black -->
                            <button
                                @click="setContrastTheme('yellow-on-black')"
                                class="flex w-full items-center gap-3 bg-gray-900 px-4 py-4 text-yellow-400"
                            >
                                <svg v-if="contrastTheme === 'yellow-on-black'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <div v-else class="h-5 w-5"></div>
                                <span>Yellow on black</span>
                            </button>
                        </div>
                    </div>

                    <!-- Text Size View -->
                    <div v-else-if="optionsView === 'textSize'" class="w-full max-w-2xl px-4 py-6">
                        <!-- Header -->
                        <div class="mb-8 flex items-center justify-between">
                            <button @click="navigateBack" class="flex items-center gap-2 transition-opacity hover:opacity-70" :class="modalTextClass">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Options</span>
                            </button>
                            <h2 class="text-xl font-semibold" :class="modalTextClass">Text size</h2>
                            <button @click="closeOptionsModal" class="text-2xl font-bold transition-opacity hover:opacity-70" :class="modalTextClass">
                                &times;
                            </button>
                        </div>

                        <!-- Text Size Options -->
                        <div class="mx-auto max-w-md overflow-hidden rounded-lg border" :class="modalBorderClass">
                            <!-- Regular -->
                            <button
                                @click="setTextSize('regular')"
                                class="flex w-full items-center gap-3 border-b px-4 py-4 transition-colors"
                                :class="[
                                    modalTextClass,
                                    modalBorderClass,
                                    textSize === 'regular' ? (contrastTheme === 'black-on-white' ? 'bg-gray-100' : 'bg-gray-800') : '',
                                ]"
                            >
                                <svg
                                    v-if="textSize === 'regular'"
                                    class="h-5 w-5"
                                    :class="modalTextClass"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <div v-else class="h-5 w-5"></div>
                                <span>Regular</span>
                            </button>

                            <!-- Large -->
                            <button
                                @click="setTextSize('large')"
                                class="flex w-full items-center gap-3 border-b px-4 py-4 transition-colors"
                                :class="[
                                    modalTextClass,
                                    modalBorderClass,
                                    textSize === 'large' ? (contrastTheme === 'black-on-white' ? 'bg-gray-100' : 'bg-gray-800') : '',
                                ]"
                            >
                                <svg
                                    v-if="textSize === 'large'"
                                    class="h-5 w-5"
                                    :class="modalTextClass"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <div v-else class="h-5 w-5"></div>
                                <span>Large</span>
                            </button>

                            <!-- Extra Large -->
                            <button
                                @click="setTextSize('extra-large')"
                                class="flex w-full items-center gap-3 px-4 py-4 transition-colors"
                                :class="[
                                    modalTextClass,
                                    textSize === 'extra-large' ? (contrastTheme === 'black-on-white' ? 'bg-gray-100' : 'bg-gray-800') : '',
                                ]"
                            >
                                <svg
                                    v-if="textSize === 'extra-large'"
                                    class="h-5 w-5"
                                    :class="modalTextClass"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <div v-else class="h-5 w-5"></div>
                                <span>Extra large</span>
                            </button>
                        </div>
                    </div>

                    <!-- Instructions View -->
                    <div v-else-if="optionsView === 'instructions'" class="w-full max-w-2xl px-4 py-6">
                        <!-- Header -->
                        <div class="mb-8 flex items-center justify-between">
                            <button @click="navigateBack" class="flex items-center gap-2 transition-opacity hover:opacity-70" :class="modalTextClass">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Options</span>
                            </button>
                            <h2 class="text-xl font-semibold" :class="modalTextClass">Test Instructions</h2>
                            <button @click="closeOptionsModal" class="text-2xl font-bold transition-opacity hover:opacity-70" :class="modalTextClass">
                                &times;
                            </button>
                        </div>

                        <!-- Instructions Content -->
                        <div class="mx-auto max-w-lg space-y-6 overflow-y-auto rounded-lg border p-6" :class="modalBorderClass" style="max-height: calc(100vh - 150px)">
                            <!-- Test Tips -->
                            <div class="space-y-3 text-sm" :class="modalTextClass">
                                <h3 class="text-base font-bold uppercase">Test Tips</h3>
                                <ul class="list-disc space-y-2 pl-5">
                                    <li><strong>Check Your Headphone:</strong> Make sure it works properly before starting.</li>
                                    <li><strong>Remaining Time:</strong> Timer is shown at the top. Manage your time properly.</li>
                                    <li><strong>Question Instructions:</strong> Each question set has its own instructions. Follow them carefully.</li>
                                    <li><strong>Navigation:</strong> Use TAB to move to the next question. Click question numbers at the bottom. Answered questions turn green. You can switch between parts anytime.</li>
                                    <li><strong>Flagging:</strong> You can flag questions for review. Flagged questions show a mark in the bottom panel.</li>
                                    <li><strong>Scroll Bar:</strong> Use scroll bar to view all questions in the part.</li>
                                    <li><strong>Screen Settings:</strong> Click the top three-bar menu to change colour, contrast, or font size. Pause is available but timer continues.</li>
                                    <li><strong>Highlighting & Notes:</strong> You can highlight text and take notes for reference only.</li>
                                    <li><strong>Time End:</strong> When time finishes, answers will be submitted automatically.</li>
                                </ul>
                            </div>

                            <!-- Instructions to Candidates -->
                            <div class="space-y-3 text-sm" :class="modalTextClass">
                                <h3 class="text-base font-bold uppercase">Instructions to Candidates</h3>
                                <ul class="list-disc space-y-2 pl-5">
                                    <li>Time: Approximately 30 minutes</li>
                                    <li>Answer all the questions.</li>
                                    <li>You can change your answers at any time during the test.</li>
                                </ul>
                            </div>

                            <!-- Information -->
                            <div class="space-y-3 text-sm" :class="modalTextClass">
                                <h3 class="text-base font-bold uppercase">Information</h3>
                                <ul class="list-disc space-y-2 pl-5">
                                    <li>There are 40 questions in this test.</li>
                                    <li>Each question carries one mark.</li>
                                    <li>There are four parts to the test.</li>
                                    <li>You will hear each part once.</li>
                                    <li>For each part of the test there will be time for you to look through the questions and time for you to check your answers.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Privacy Blur Overlay -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="isPaused" class="fixed inset-0 z-[10000] flex items-center justify-center bg-white/70 backdrop-blur-[4px]">
                    <div class="text-center">
                        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full border-2 border-gray-900">
                            <svg class="h-10 w-10 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                />
                            </svg>
                        </div>
                        <h2 class="mb-2 text-2xl font-bold text-gray-900">Privacy Mode</h2>
                        <p class="mb-2 text-gray-600">Screen is hidden for privacy</p>
                        <p class="mb-6 text-sm text-gray-500">Time and audio continue running</p>
                        <button
                            @click="resumeTest"
                            class="inline-flex items-center gap-2 border border-gray-900 bg-gray-900 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                />
                            </svg>
                            Show Screen
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Notes Drawer -->
        <Teleport to="body">
            <!-- Backdrop -->
            <Transition name="fade">
                <div v-if="showNotesDrawer" class="fixed inset-0 z-[9998] bg-black/50" @click="closeNotesDrawer"></div>
            </Transition>

            <!-- Drawer -->
            <Transition name="slide">
                <div
                    v-if="showNotesDrawer"
                    class="fixed top-0 right-0 z-[9999] flex h-full w-full flex-col overflow-hidden bg-white shadow-2xl sm:w-96"
                >
                    <!-- Header -->
                    <div class="border-b border-gray-300 bg-white px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center bg-black">
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
                                    <h3 class="text-lg font-bold text-black">My Notes</h3>
                                    <div class="flex gap-2 text-xs text-gray-600">
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
                                class="flex h-8 w-8 items-center justify-center text-gray-500 transition-colors hover:bg-gray-100 hover:text-black"
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
                            <div class="mb-4 flex h-16 w-16 items-center justify-center bg-gray-100">
                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </div>
                            <h4 class="mb-2 text-lg font-semibold text-black">No notes yet</h4>
                            <p class="max-w-xs text-sm text-gray-600">
                                Select text in the listening questions and click "Note" to add notes and reminders.
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="note in allNotes"
                                :key="note.id"
                                class="group relative border border-gray-300 bg-white p-4 transition-all hover:border-black hover:shadow-md"
                            >
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <span class="bg-black px-2.5 py-0.5 text-xs font-bold text-white">
                                        {{ note.part }}
                                    </span>
                                    <button
                                        @click.stop="deleteNote(note.id, note.part)"
                                        class="flex h-6 w-6 items-center justify-center bg-black text-white opacity-0 transition-all group-hover:opacity-100 hover:bg-gray-800"
                                        title="Delete note"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div
                                    @click="focusNote(note)"
                                    class="mb-2 cursor-pointer border border-gray-200 bg-gray-50 px-3 py-2 transition-colors hover:bg-gray-100"
                                >
                                    <p class="mb-1 text-xs font-medium text-gray-500">Selected Text:</p>
                                    <p class="text-sm text-gray-700 italic">"{{ note.selectedText }}"</p>
                                </div>
                                <div
                                    @click="focusNote(note)"
                                    class="cursor-pointer border border-gray-200 bg-white px-3 py-2 transition-colors hover:bg-gray-50"
                                >
                                    <p class="mb-1 text-xs font-medium text-gray-500">Your Note:</p>
                                    <p class="text-sm font-medium whitespace-pre-wrap text-black">{{ note.note }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </nav>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Notes Drawer Slide Transition */
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>
