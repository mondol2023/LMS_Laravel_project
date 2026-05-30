<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowRight, Check, ChevronDown, ChevronUp, Home } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

interface Section {
    name: string;
    duration: number;
    questions: number;
    description: string;
}

interface Exam {
    id: string;
    name: string;
    type: string;
    duration: number;
    sections: Section[];
    instructions: string[];
}

interface Result {
    id: number;
    exam_id: number;
    student_id: number;
    username: string;
    exam_type: string;
    modules: string[];
    listening: any;
    reading: any;
    writing: any;
    speaking: any;
}

interface Props {
    exam: Exam;
    slug: string;
    result: Result | null;
    availableModules: string[];
}

const props = defineProps<Props>();

const submittedSections = ref<Set<string>>(new Set());
const expandedSections = ref<Set<string>>(new Set());
const confirmedSections = ref<Set<string>>(new Set());
const watchedEnoughSections = ref<Set<string>>(new Set());
const sectionTimers = ref<Record<string, ReturnType<typeof setTimeout> | null>>({});
const countdowns = ref<Record<string, number>>({});

// Pre-test checks state
const preTestConfirmed = ref(false);

// Pre-test tips
const preTestTips = [
    {
        title: 'Headphone',
        description: 'Check your headphone works properly.',
    },
    {
        title: 'Time',
        description: 'Remaining time is shown at the top. Answers submit automatically when time ends.',
    },
    {
        title: 'Instructions',
        description: 'Follow instructions for each question set. Use TAB or click numbers to move. Switch parts, highlight text, and take notes.',
    },
    {
        title: 'Flagging',
        description: 'Flag questions for review. Flagged questions appear in the bottom panel.',
    },
    {
        title: 'View & Settings',
        description: 'Scroll to view questions. Click the menu to change colour, contrast, or font size. Pause is available but the timer continues.',
    },
];

// Confirm pre-test checks
const confirmPreTest = () => {
    preTestConfirmed.value = true;
};

// Define section order for sequential unlocking
const sectionOrder = ['listening', 'reading', 'writing', 'speaking'];

// Section timing display
const sectionTimings: Record<string, string> = {
    listening: '30 minutes',
    reading: '1 hour',
    writing: '1 hour',
    speaking: '15 minutes',
};

// YouTube video IDs for each section
const sectionVideoIds: Record<string, string> = {
    listening: '_O2RHxsAugg',
    reading: 'dsCXG9kfRgk',
    writing: 'vteGnnQCuAs',
    speaking: '4_dCncUPBO4',
};

// Section instructions
const sectionInstructions: Record<string, { title: string; points: string[]; subsections?: { title: string; points: string[] }[] }> = {
    listening: {
        title: 'Listening Test',
        points: [],
        subsections: [
            {
                title: 'Instructions to Candidates',
                points: [
                    'Time: Approximately 30 minutes',
                    'Answer all the questions.',
                    'You can change your answers at any time during the test.',
                ],
            },
            {
                title: 'Information',
                points: [
                    'There are 40 questions in this test.',
                    'Each question carries one mark.',
                    'There are four parts to the test.',
                    'You will hear each part once.',
                    'For each part of the test there will be time for you to look through the questions and time for you to check your answers.',
                ],
            },
        ],
    },
    reading: {
        title: 'Reading Test',
        points: [],
        subsections: [
            {
                title: 'Instructions to Candidates',
                points: [
                    'Time: 1 hour',
                    'Answer all the questions.',
                    'You can change your answers at any time during the test.',
                ],
            },
            {
                title: 'Information',
                points: [
                    'There are 40 questions in this test.',
                    'Each question carries one mark.',
                ],
            },
        ],
    },
    writing: {
        title: 'Writing Test',
        points: [],
        subsections: [
            {
                title: 'Instructions to Candidates',
                points: [
                    'Time: 1 hour',
                    'Answer both parts.',
                    'You can change your answers at any time during the test.',
                ],
            },
            {
                title: 'Information',
                points: [
                    'There are two parts in this test.',
                    'Part 2 contributes twice as much as Part 1 to the writing score.',
                ],
            },
        ],
    },
    speaking: {
        title: 'Speaking Test Instructions',
        points: [
            'Duration: 11-14 minutes',
            '3 parts: Introduction, Long Turn, Discussion',
            'Speak clearly and at natural pace',
            'Extend your answers with examples',
            'Ask for clarification if needed',
        ],
    },
};

// Check if all available modules are completed
const allModulesCompleted = computed(() => {
    if (availableModules.value.length === 0) return false;
    return availableModules.value.every((mod) => submittedSections.value.has(mod.toLowerCase()));
});

const availableModules = computed(() => props.availableModules);

const goToHome = () => {
    router.visit('/');
};

// Get YouTube embed URL for a specific section
const getYouTubeEmbedUrl = (sectionName: string): string => {
    const videoId = sectionVideoIds[sectionName.toLowerCase()] || '_O2RHxsAugg';
    return `https://www.youtube.com/embed/${videoId}?rel=0&modestbranding=1&autoplay=1&enablejsapi=1`;
};

// Pre-test video URL
const preTestVideoUrl = computed(() => {
    return `https://www.youtube.com/embed/4_dCncUPBO4?rel=0&modestbranding=1&enablejsapi=1`;
});

// Check if section has been watched for 5+ seconds
const hasWatchedEnough = (sectionName: string) => {
    return watchedEnoughSections.value.has(sectionName.toLowerCase());
};

// Get remaining countdown for a section
const getCountdown = (sectionName: string) => {
    return countdowns.value[sectionName.toLowerCase()] || 0;
};

// Start countdown timer when section is expanded
const startWatchTimer = (sectionName: string) => {
    const name = sectionName.toLowerCase();

    // Don't start timer if already watched enough or already confirmed
    if (watchedEnoughSections.value.has(name) || confirmedSections.value.has(name)) {
        return;
    }

    // Clear existing timer if any
    if (sectionTimers.value[name]) {
        clearInterval(sectionTimers.value[name]!);
    }

    // Initialize countdown to 5 seconds
    countdowns.value[name] = 5;

    // Start countdown interval
    sectionTimers.value[name] = setInterval(() => {
        if (countdowns.value[name] > 0) {
            countdowns.value[name]--;
        }

        if (countdowns.value[name] === 0) {
            watchedEnoughSections.value.add(name);
            if (sectionTimers.value[name]) {
                clearInterval(sectionTimers.value[name]!);
                sectionTimers.value[name] = null;
            }
        }
    }, 1000);
};

// Stop timer when section is collapsed
const stopWatchTimer = (sectionName: string) => {
    const name = sectionName.toLowerCase();
    if (sectionTimers.value[name]) {
        clearInterval(sectionTimers.value[name]!);
        sectionTimers.value[name] = null;
    }
};

const checkSubmittedSections = () => {
    if (!props.result) return;

    const newSubmittedSections = new Set<string>();

    const hasValidData = (sectionData: any) => {
        if (!sectionData) return false;
        if (Array.isArray(sectionData)) {
            return sectionData.length > 0 && sectionData.some((item) => item.ans && item.ans.trim() !== '');
        }
        if (typeof sectionData === 'object') {
            if (Object.keys(sectionData).length === 0) return false;
            if (sectionData.completed_at || sectionData.submitted) return true;
            const numericKeys = Object.keys(sectionData).filter((key) => !isNaN(Number(key)));
            if (numericKeys.length > 0) {
                return numericKeys.some((key) => {
                    const task = sectionData[key];
                    return task && task.ans && task.ans.trim() !== '';
                });
            }
        }
        return false;
    };

    if (hasValidData(props.result.listening)) newSubmittedSections.add('listening');
    if (hasValidData(props.result.reading)) newSubmittedSections.add('reading');
    if (hasValidData(props.result.writing)) newSubmittedSections.add('writing');

    submittedSections.value = newSubmittedSections;
};

// Check if a section is submitted
const isSectionSubmitted = (sectionName: string) => {
    return submittedSections.value.has(sectionName.toLowerCase());
};

// Check if a section is locked (previous section not completed)
const isSectionLocked = (sectionName: string) => {
    // For partial exams, no section is locked - user can do any section in any order
    if (props.result?.exam_type === 'partial') {
        return false;
    }

    const sectionIndex = sectionOrder.indexOf(sectionName.toLowerCase());
    if (sectionIndex === 0) return false; // First section (listening) is never locked

    // Check if previous section is completed (only for full exams)
    const previousSection = sectionOrder[sectionIndex - 1];
    return !isSectionSubmitted(previousSection);
};

// Toggle section expansion
const toggleSection = (sectionName: string) => {
    const name = sectionName.toLowerCase();
    if (expandedSections.value.has(name)) {
        expandedSections.value.delete(name);
        stopWatchTimer(name);
    } else {
        expandedSections.value.add(name);
        startWatchTimer(name);
    }
};

// Check if section is expanded
const isSectionExpanded = (sectionName: string) => {
    return expandedSections.value.has(sectionName.toLowerCase());
};

// Check if section info is confirmed (video watched)
const isSectionConfirmed = (sectionName: string) => {
    return confirmedSections.value.has(sectionName.toLowerCase());
};

// Confirm section info (mark as watched)
const confirmSectionInfo = (sectionName: string) => {
    confirmedSections.value.add(sectionName.toLowerCase());
};

const startSection = (section: Section) => {
    // Prevent starting locked or submitted sections
    if (isSectionLocked(section.name) || isSectionSubmitted(section.name)) {
        return;
    }

    // Must confirm section info first
    if (!isSectionConfirmed(section.name)) {
        return;
    }

    const sectionName = section.name.toLowerCase();
    const examSlug = props.slug;

    // Map section names to their respective component paths
    const sectionRoutes: Record<string, string> = {
        listening: `/exam/${examSlug}/listening`,
        reading: `/exam/${examSlug}/reading`,
        writing: `/exam/${examSlug}/writing`,
        speaking: `/exam/${examSlug}/speaking`,
    };

    const targetRoute = sectionRoutes[sectionName];

    if (targetRoute) {
        router.visit(targetRoute);
    } else {
        router.visit(`/exam/${examSlug}/start-exam`);
    }
};

// Real-time clock
const currentTime = ref('');
const updateTime = () => {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    currentTime.value = `${hours}:${minutes}`;
};

// Battery status
const batteryLevel = ref(100);
const isCharging = ref(false);
const updateBattery = async () => {
    try {
        if ('getBattery' in navigator) {
            const battery = await (navigator as any).getBattery();
            batteryLevel.value = Math.round(battery.level * 100);
            isCharging.value = battery.charging;

            battery.addEventListener('levelchange', () => {
                batteryLevel.value = Math.round(battery.level * 100);
            });
            battery.addEventListener('chargingchange', () => {
                isCharging.value = battery.charging;
            });
        }
    } catch {
        batteryLevel.value = 85;
    }
};

// Volume control
const volume = ref(100);
const showVolumeSlider = ref(false);
let hideVolumeTimeout: number | null = null;

const showVolumeControl = () => {
    if (hideVolumeTimeout) {
        clearTimeout(hideVolumeTimeout);
        hideVolumeTimeout = null;
    }
    showVolumeSlider.value = true;
};

const hideVolumeControl = () => {
    hideVolumeTimeout = window.setTimeout(() => {
        showVolumeSlider.value = false;
    }, 300);
};

const handleVolumeChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    volume.value = parseInt(target.value);
};

// Initialize YouTube API (for future use if needed)
const initYouTubeAPI = () => {
    // YouTube API initialization placeholder
};

// Update all YouTube iframes volume when volume changes
watch(volume, (newVolume) => {
    // Get all YouTube iframes and send volume command via postMessage
    const iframes = document.querySelectorAll('iframe[id^="youtube-player"]');
    iframes.forEach((iframe) => {
        const iframeElement = iframe as HTMLIFrameElement;
        if (iframeElement.contentWindow) {
            iframeElement.contentWindow.postMessage(
                JSON.stringify({
                    event: 'command',
                    func: 'setVolume',
                    args: [newVolume],
                }),
                '*'
            );
        }
    });
});

// WiFi/Network status
const isOnline = ref(navigator.onLine);
const connectionType = ref<string>('unknown');

const updateNetworkStatus = () => {
    isOnline.value = navigator.onLine;
    if ('connection' in navigator) {
        const conn = (navigator as any).connection;
        connectionType.value = conn?.effectiveType || 'unknown';
    }
};

const handleOnline = () => {
    isOnline.value = true;
    updateNetworkStatus();
};

const handleOffline = () => {
    isOnline.value = false;
};

let timeInterval: number | null = null;

// Set up on component mount
onMounted(() => {
    checkSubmittedSections();

    // Auto-confirm pre-test if any section is already completed
    if (submittedSections.value.size > 0) {
        preTestConfirmed.value = true;
    }

    // Initialize clock, battery, and network status
    updateTime();
    timeInterval = window.setInterval(updateTime, 10000);
    updateBattery();
    updateNetworkStatus();

    // Initialize YouTube API
    initYouTubeAPI();

    // Listen for network status changes
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
});

// Clean up timers and listeners on unmount
onUnmounted(() => {
    Object.values(sectionTimers.value).forEach((timer) => {
        if (timer) {
            clearInterval(timer);
        }
    });
    if (timeInterval) clearInterval(timeInterval);
    if (hideVolumeTimeout) clearTimeout(hideVolumeTimeout);
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
});
</script>

<template>
    <Head :title="`${exam.name} - IELTS on Computer`" />
    <div class="min-h-screen bg-white">
        <!-- Top Navigation Header -->
        <header class="sticky top-0 z-50 border-b border-gray-200 bg-white">
            <div class="flex h-14 items-center justify-between px-3 sm:h-16 sm:px-4">
                <!-- Left: IELTS Logo + Username -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <img src="/images/LandingUI/ielts.png" alt="IELTS" class="h-12 w-auto object-contain pl-6 sm:h-18" />
                    <span class="-mt-4 text-sm font-semibold text-gray-900 sm:text-base">
                        {{ result?.username || 'Guest' }}
                    </span>
                </div>

                <!-- Right: Exam Type -->
                <span v-if="result" class="mr-4 text-xs font-medium text-gray-600 sm:text-sm">
                    {{ result.exam_type === 'full' ? 'Full Mock Test' : 'Partial Mock Test' }}
                </span>
            </div>
        </header>

        <!-- Main Content -->
        <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 sm:py-10">
            <!-- Page Title -->
            <h1 class="mb-6 text-2xl font-medium text-[#a31621] sm:mb-8 sm:text-3xl">IELTS on Computer</h1>

            <!-- Today Section -->
            <div class="mb-6 flex items-center gap-3 sm:mb-8">
                <span class="text-base font-bold text-gray-900 sm:text-lg">Today</span>
                <div class="h-px flex-1 bg-gray-300"></div>
            </div>

            <!-- Pre-test checks card -->
            <div class="mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm sm:mb-6">
                <div class="flex items-center justify-between p-4 sm:p-5">
                    <span class="text-base font-semibold text-gray-900 sm:text-lg">Pre-test checks</span>
                    <Check v-if="preTestConfirmed" class="h-5 w-5 text-green-600 sm:h-6 sm:w-6" />
                </div>
                <!-- Dropdown content (hidden after confirmed) -->
                <div v-if="!preTestConfirmed" class="border-t border-gray-100 bg-gray-50 px-4 py-3 sm:px-5 sm:py-4">
                    <!-- Test Tips -->
                    <ul class="space-y-2.5">
                        <li
                            v-for="(tip, index) in preTestTips"
                            :key="index"
                            class="flex items-start gap-2 text-xs text-gray-700 sm:text-sm"
                        >
                            <Check class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-green-500 sm:h-4 sm:w-4" />
                            <span>
                                <span class="font-semibold text-gray-900">{{ tip.title }}:</span>
                                {{ tip.description }}
                            </span>
                        </li>
                    </ul>

                    <!-- Video Section -->
                    <div class="mt-4">
                        <p class="mb-3 text-sm font-medium text-gray-700 sm:text-base">
                            To know better about the system, watch the video:
                        </p>
                        <div class="aspect-video w-full overflow-hidden rounded-lg bg-black">
                            <iframe
                                id="youtube-player-pretest"
                                :src="preTestVideoUrl"
                                class="youtube-player h-full w-full border-0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </div>
                    </div>

                    <!-- Confirm Button -->
                    <button
                        @click="confirmPreTest"
                        class="mt-4 w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-800 sm:py-3 sm:text-base"
                    >
                        I confirm all checks are complete
                    </button>
                </div>
            </div>

            <!-- Section Cards (always visible) -->
            <div class="space-y-4 sm:space-y-6">
                <div
                    v-for="section in exam.sections"
                    :key="section.name"
                    class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                >
                    <!-- Section Header -->
                    <div class="p-4 sm:p-5">
                        <h2 class="text-lg font-bold text-gray-900 sm:text-xl">{{ section.name }}</h2>
                        <p :class="['mt-1 text-sm font-medium', isSectionSubmitted(section.name) ? 'text-green-600' : 'text-[#a31621]']">
                            {{ isSectionSubmitted(section.name) ? 'Completed' : 'Not completed' }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            Timing: {{ sectionTimings[section.name.toLowerCase()] || `${section.duration} minutes` }}
                        </p>

                        <!-- Test Information Accordion (only if not submitted and not locked) -->
                        <div v-if="!isSectionSubmitted(section.name) && !isSectionLocked(section.name)" class="mt-4">
                            <button
                                @click="toggleSection(section.name)"
                                class="flex w-full items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-left transition-colors hover:bg-gray-50 sm:px-4 sm:py-2.5"
                            >
                                <component
                                    :is="isSectionExpanded(section.name) ? ChevronUp : ChevronDown"
                                    class="h-4 w-4 text-gray-500 sm:h-5 sm:w-5"
                                />
                                <span class="text-sm text-gray-700 sm:text-base">Test information.</span>
                                <span v-if="isSectionConfirmed(section.name)" class="ml-1 text-sm font-medium text-green-600 sm:text-base">
                                    Confirmed
                                </span>
                            </button>

                            <!-- Expanded Content with Instructions and YouTube Video -->
                            <div
                                v-if="isSectionExpanded(section.name)"
                                class="mt-3 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-3 sm:mt-4 sm:p-4"
                            >
                                <!-- Section Instructions -->
                                <div
                                    v-if="sectionInstructions[section.name.toLowerCase()]"
                                    class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4"
                                >
                                    <h3 class="mb-2 text-sm font-bold text-gray-900 sm:mb-3 sm:text-base">
                                        {{ sectionInstructions[section.name.toLowerCase()].title }}
                                    </h3>

                                    <!-- Simple points (for sections without subsections) -->
                                    <ul
                                        v-if="sectionInstructions[section.name.toLowerCase()].points.length > 0"
                                        class="space-y-1.5 sm:space-y-2"
                                    >
                                        <li
                                            v-for="(point, index) in sectionInstructions[section.name.toLowerCase()].points"
                                            :key="index"
                                            class="flex items-start gap-2 text-xs text-gray-700 sm:text-sm"
                                        >
                                            <span class="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-gray-400"></span>
                                            <span>{{ point }}</span>
                                        </li>
                                    </ul>

                                    <!-- Subsections (for Listening with Instructions & Information) -->
                                    <div
                                        v-if="sectionInstructions[section.name.toLowerCase()].subsections"
                                        class="space-y-4"
                                    >
                                        <div
                                            v-for="(subsection, subIndex) in sectionInstructions[section.name.toLowerCase()].subsections"
                                            :key="subIndex"
                                        >
                                            <h4 class="mb-2 text-xs font-semibold text-gray-800 uppercase sm:text-sm">
                                                {{ subsection.title }}
                                            </h4>
                                            <ul class="space-y-1.5 sm:space-y-2">
                                                <li
                                                    v-for="(point, pointIndex) in subsection.points"
                                                    :key="pointIndex"
                                                    class="flex items-start gap-2 text-xs text-gray-700 sm:text-sm"
                                                >
                                                    <span class="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-gray-400"></span>
                                                    <span>{{ point }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- YouTube Video Embed -->
                                <div class="aspect-video w-full overflow-hidden rounded-lg bg-black">
                                    <iframe
                                        :id="`youtube-player-${section.name.toLowerCase()}`"
                                        :src="getYouTubeEmbedUrl(section.name)"
                                        class="youtube-player h-full w-full border-0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                    ></iframe>
                                </div>

                                <!-- Confirm Button -->
                                <button
                                    v-if="!isSectionConfirmed(section.name)"
                                    @click="confirmSectionInfo(section.name)"
                                    :disabled="!hasWatchedEnough(section.name)"
                                    :class="[
                                        'w-full rounded-lg px-4 py-2 text-sm font-medium transition-colors sm:py-2.5 sm:text-base',
                                        hasWatchedEnough(section.name)
                                            ? 'cursor-pointer bg-green-600 text-white hover:bg-green-700'
                                            : 'cursor-not-allowed bg-gray-300 text-gray-500',
                                    ]"
                                >
                                    <span v-if="!hasWatchedEnough(section.name)">
                                        I have watched the video - Confirm ({{ getCountdown(section.name) }}s)
                                    </span>
                                    <span v-else> I have watched the video - Confirm </span>
                                </button>
                                <div
                                    v-else
                                    class="flex items-center justify-center gap-2 rounded-lg bg-green-50 px-4 py-2 text-sm font-medium text-green-700 sm:py-2.5 sm:text-base"
                                >
                                    <Check class="h-4 w-4 sm:h-5 sm:w-5" />
                                    Information Confirmed
                                </div>
                            </div>
                        </div>

                        <!-- Locked Message -->
                        <div
                            v-if="isSectionLocked(section.name)"
                            class="mt-3 rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-600 italic sm:mt-4 sm:px-4 sm:py-2.5"
                        >
                            Complete {{ sectionOrder[sectionOrder.indexOf(section.name.toLowerCase()) - 1] }} section first to unlock
                        </div>

                        <!-- Start Button -->
                        <button
                            v-if="!isSectionSubmitted(section.name) && !isSectionLocked(section.name) && isSectionConfirmed(section.name)"
                            @click="startSection(section)"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 sm:px-5 sm:py-2.5 sm:text-base"
                        >
                            <ArrowRight class="h-4 w-4 sm:h-5 sm:w-5" />
                            Start {{ section.name }}
                        </button>

                        <!-- Completed Badge -->
                        <div
                            v-if="isSectionSubmitted(section.name)"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-green-50 px-4 py-2 text-sm font-medium text-green-700 sm:px-5 sm:py-2.5 sm:text-base"
                        >
                            <Check class="h-4 w-4 sm:h-5 sm:w-5" />
                            Section Completed
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Modules Completed - Home Button -->
            <div
                v-if="allModulesCompleted"
                class="mt-8 flex flex-col items-center gap-4 rounded-lg border-2 border-green-200 bg-green-50 p-6 text-center sm:mt-10 sm:gap-5 sm:p-8"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-600 sm:h-16 sm:w-16">
                    <Check class="h-7 w-7 text-white sm:h-8 sm:w-8" />
                </div>
                <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">All Sections Completed!</h2>
                <p class="max-w-md text-sm text-gray-600 sm:text-base">
                    You have successfully completed all exam sections.
                </p>
                <button
                    @click="goToHome"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-6 py-2.5 text-base font-medium text-white transition-colors hover:bg-gray-800 sm:px-8 sm:py-3 sm:text-lg"
                >
                    <Home class="h-5 w-5 sm:h-6 sm:w-6" />
                    Go to Home
                </button>
            </div>

            <!-- Spacer for fixed footer -->
            <div class="h-20"></div>
        </div>

        <!-- Fixed Footer: Brand Bar -->
        <footer class="fixed right-0 bottom-0 left-0 z-30 bg-gray-200 px-4 py-3 text-gray-800">
            <div class="flex items-center justify-between">
                <!-- Brand Name: English (black) Therapy (red) -->
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold">
                        <span class="text-black">English</span>
                        <span class="text-red-500"> Therapy</span>
                    </span>
                </div>

                <!-- Right Section: Time, WiFi, Sound, Battery -->
                <div class="flex items-center gap-5">
                    <!-- Real Time Clock -->
                    <span class="text-2xl font-bold text-gray-800 tabular-nums">{{ currentTime }}</span>

                    <!-- WiFi Icon -->
                    <div class="flex items-center" :title="isOnline ? `Connected (${connectionType})` : 'No Connection'">
                        <!-- Online: WiFi Icon -->
                        <svg v-if="isOnline" class="h-7 w-7 text-gray-700" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z" />
                        </svg>
                        <!-- Offline: No WiFi Icon -->
                        <svg v-else class="h-7 w-7 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.99 9C19.15 5.16 13.8 3.76 8.84 4.78l2.52 2.52c3.47-.17 6.99 1.05 9.63 3.7l2-2zm-4 4c-1.29-1.29-2.84-2.13-4.49-2.56l3.53 3.53.96-.97zM2 3.05L5.07 6.1C3.6 6.82 2.22 7.78 1 9l2 2c1.02-1.02 2.17-1.85 3.41-2.48l2.09 2.09C7.07 11.25 5.86 12.13 5 13l2 2c1.05-1.05 2.41-1.73 3.88-2.02l9.26 9.26 1.41-1.41L3.41 1.64 2 3.05zM9 17l3 3 3-3c-1.65-1.66-4.34-1.66-6 0z" />
                        </svg>
                    </div>

                    <!-- Volume/Sound Control with Slider -->
                    <div class="relative" @mouseenter="showVolumeControl" @mouseleave="hideVolumeControl">
                        <button class="p-1 text-gray-700 transition-colors hover:text-gray-900" :title="`Volume: ${volume}%`">
                            <!-- High Volume -->
                            <svg v-if="volume > 50" class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"
                                />
                            </svg>
                            <!-- Medium Volume -->
                            <svg v-else-if="volume > 0" class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z" />
                            </svg>
                            <!-- Muted -->
                            <svg v-else class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"
                                />
                            </svg>
                        </button>

                        <!-- Volume Slider Popup -->
                        <Transition name="fade">
                            <div
                                v-if="showVolumeSlider"
                                class="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-3 shadow-lg"
                                @mouseenter="showVolumeControl"
                                @mouseleave="hideVolumeControl"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-xs font-medium text-gray-600">{{ volume }}%</span>
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        :value="volume"
                                        @input="handleVolumeChange"
                                        class="volume-slider h-24 w-2 cursor-pointer appearance-none rounded-full bg-gray-200"
                                    />
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Battery Icon -->
                    <div class="flex items-center gap-1" :title="`Battery: ${batteryLevel}%${isCharging ? ' (Charging)' : ''}`">
                        <div class="relative">
                            <svg class="h-7 w-7 text-gray-700" fill="currentColor" viewBox="0 0 24 24">
                                <rect x="2" y="7" width="18" height="10" rx="1" stroke="currentColor" stroke-width="1.5" fill="none" />
                                <rect x="20" y="10" width="2" height="4" rx="0.5" fill="currentColor" />
                                <rect
                                    x="3"
                                    y="8"
                                    :width="Math.max(1, (batteryLevel / 100) * 16)"
                                    height="8"
                                    :fill="isCharging ? '#22c55e' : batteryLevel > 20 ? 'currentColor' : '#ef4444'"
                                />
                            </svg>
                            <!-- Charging Lightning Bolt Overlay -->
                            <svg v-if="isCharging" class="absolute inset-0 h-7 w-7" viewBox="0 0 24 24">
                                <path d="M12 6L8 12h3l-1 6 5-7h-3l1-5z" fill="#ffffff" stroke="#22c55e" stroke-width="0.5" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
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

/* Vertical Volume Slider */
.volume-slider {
    writing-mode: vertical-lr;
    direction: rtl;
    -webkit-appearance: none;
    appearance: none;
}

.volume-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 16px;
    height: 16px;
    background: #374151;
    border-radius: 50%;
    cursor: pointer;
}

.volume-slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: #374151;
    border-radius: 50%;
    cursor: pointer;
    border: none;
}

.volume-slider::-webkit-slider-runnable-track {
    background: #e5e7eb;
    border-radius: 9999px;
}

.volume-slider::-moz-range-track {
    background: #e5e7eb;
    border-radius: 9999px;
}
</style>
