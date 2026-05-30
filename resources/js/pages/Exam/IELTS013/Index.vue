<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, BookOpen, CheckCircle, Home, Lock, PlayCircle, Video, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

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
const showExitConfirmModal = ref(false);
const showVideoModal = ref(false);
const currentVideoSection = ref<Section | null>(null);
const watchedVideos = ref<Set<string>>(new Set());
const videoElement = ref<HTMLVideoElement | null>(null);

// Define section order for sequential unlocking
const sectionOrder = ['listening', 'reading', 'writing', 'speaking'];

// Check if all available modules are completed
const allModulesCompleted = computed(() => {
    if (availableModules.value.length === 0) return false;
    return availableModules.value.every((mod) => submittedSections.value.has(mod.toLowerCase()));
});

const availableModules = computed(() => props.availableModules);

const goToDashboard = () => {
    router.visit('/student/dashboard');
};

// Get video URL for each section (you can update these URLs)
const getSectionVideoUrl = (sectionName: string): string => {
    const videos: Record<string, string> = {
        listening: 'https://www.w3schools.com/html/mov_bbb.mp4', // Replace with actual video URLs
        reading: 'https://www.w3schools.com/html/mov_bbb.mp4',
        writing: 'https://www.w3schools.com/html/mov_bbb.mp4',
        speaking: 'https://www.w3schools.com/html/mov_bbb.mp4',
    };
    return videos[sectionName.toLowerCase()] || '';
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

// Check if video has been watched for a section
const hasWatchedVideo = (sectionName: string) => {
    return watchedVideos.value.has(sectionName.toLowerCase());
};

// Open video modal for a section
const openVideoModal = (section: Section) => {
    if (isSectionLocked(section.name) || isSectionSubmitted(section.name)) {
        return;
    }
    currentVideoSection.value = section;
    showVideoModal.value = true;
};

// Close video modal
const closeVideoModal = () => {
    if (videoElement.value) {
        videoElement.value.pause();
        videoElement.value.currentTime = 0;
    }
    showVideoModal.value = false;
    currentVideoSection.value = null;
};

// Mark video as watched and enable start button
const handleVideoEnd = () => {
    if (currentVideoSection.value) {
        watchedVideos.value.add(currentVideoSection.value.name.toLowerCase());
    }
};

const startSection = (section: Section) => {
    // Prevent starting locked, submitted, or unwatched sections
    if (isSectionLocked(section.name) || isSectionSubmitted(section.name) || !hasWatchedVideo(section.name)) {
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
        // Fallback to generic start exam route
        router.visit(`/exam/${examSlug}/start-exam`);
    }
};

const handleCancelExit = () => {
    showExitConfirmModal.value = false;
};

const handleConfirmExit = () => {
    showExitConfirmModal.value = false;
    router.visit('/available-exams');
};

// Prevent back button functionality
// const preventBackButton = (event: PopStateEvent) => {
//     // Push the current state back to prevent navigation
//     window.history.pushState(null, '', window.location.pathname);
//
//     // Show confirmation dialog
//     if (confirm('Are you sure you want to leave the exam? Your progress may be lost.')) {
//         // If user confirms, allow them to leave
//         window.removeEventListener('popstate', preventBackButton);
//         window.history.back();
//     }
// };

// Set up back button prevention on component mount
onMounted(() => {
    // Check submitted sections from result prop
    checkSubmittedSections();

    // Add current state to history
    // window.history.pushState(null, '', window.location.pathname);

    // Listen for back button clicks
    // window.addEventListener('popstate', preventBackButton);

    // Prevent refresh and close tab
    // const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    //     event.preventDefault();
    //     event.returnValue = 'Are you sure you want to leave the exam? Your progress may be lost.';
    //     return 'Are you sure you want to leave the exam? Your progress may be lost.';
    // };
    //
    // window.addEventListener('beforeunload', handleBeforeUnload);
    //
    // // Clean up function will be returned by onUnmounted
    // onUnmounted(() => {
    //     window.removeEventListener('popstate', preventBackButton);
    //     window.removeEventListener('beforeunload', handleBeforeUnload);
    // });
});
</script>

<template>
    <Head :title="`${exam.name} - IELTS Mock Test`" />
    <div class="min-h-screen bg-white">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur-sm">
            <div class="container mx-auto px-4">
                <div class="flex h-14 items-center justify-between sm:h-16">
                    <div class="flex items-center gap-2">
                        <img src="/logo.png" alt="English Therapy" class="h-9 w-9 rounded-full object-contain sm:h-10 sm:w-10" />
                        <div class="grid text-left">
                            <span class="truncate text-sm leading-tight font-bold text-black sm:text-lg">English Therapy</span>
                            <span class="hidden truncate text-xs font-medium text-gray-600 sm:block">IELTS Success Partner</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-3">
                        <Badge
                            v-if="result"
                            variant="outline"
                            class="hidden border-gray-300 bg-gray-50 text-xs font-medium text-gray-700 sm:inline-flex sm:text-sm"
                        >
                            {{ result.exam_type === 'full' ? 'Full Mock Test' : 'Partial Mock Test' }}
                        </Badge>
                        <Badge variant="outline" class="border-gray-300 bg-gray-50 text-xs font-medium text-gray-700 sm:text-sm">
                            {{ result?.username || 'Guest' }}
                        </Badge>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-6 sm:py-12">
            <div class="mx-auto max-w-6xl space-y-6 sm:space-y-8">
                <!-- Header -->
                <div class="space-y-4 text-center sm:space-y-6">
                    <div
                        class="inline-flex items-center gap-2 border border-gray-300 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 sm:px-4 sm:py-2 sm:text-sm"
                    >
                        <PlayCircle class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                        Ready to Start
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl md:text-4xl">
                        {{ exam.name }}
                    </h1>
                    <div v-if="result && result.exam_type === 'partial'" class="mx-auto max-w-2xl">
                        <div class="inline-flex flex-wrap items-center justify-center gap-2 border border-gray-300 bg-gray-50 px-3 py-2 sm:px-4">
                            <span class="text-xs font-semibold text-gray-700 sm:text-sm">Selected Modules:</span>
                            <Badge
                                v-for="module in availableModules"
                                :key="module"
                                class="border-gray-400 bg-gray-100 text-xs text-gray-800 capitalize sm:text-sm"
                            >
                                {{ module }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <!-- Test Sections -->
                <Card class="border-2 border-gray-200 bg-white">
                    <CardHeader class="px-4 py-4 sm:px-6 sm:py-6">
                        <CardTitle class="flex items-center gap-2 text-lg text-black sm:text-2xl">
                            <BookOpen class="h-5 w-5 text-gray-700 sm:h-6 sm:w-6" />
                            Test Sections
                        </CardTitle>
                        <CardDescription class="text-sm sm:text-lg">
                            Complete all {{ exam.sections.length }} sections to finish your IELTS practice test
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-4 pb-4 sm:px-6 sm:pb-6">
                        <div class="grid gap-4 sm:gap-6">
                            <div
                                v-for="(section, index) in exam.sections"
                                :key="section.name"
                                :class="[
                                    'flex flex-col gap-4 rounded-xl border p-4 shadow-sm transition-all duration-300 sm:flex-row sm:items-center sm:justify-between sm:rounded-2xl sm:p-6',
                                    isSectionSubmitted(section.name)
                                        ? 'border-gray-200 bg-gray-50 opacity-75'
                                        : isSectionLocked(section.name)
                                          ? 'border-gray-200 bg-gray-100 opacity-60'
                                          : 'border-gray-200 bg-white hover:border-gray-400 hover:shadow-md',
                                ]"
                            >
                                <div class="flex items-start gap-3 sm:items-center sm:gap-4">
                                    <div
                                        :class="[
                                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-full shadow-lg transition-all duration-300 sm:h-12 sm:w-12',
                                            isSectionSubmitted(section.name)
                                                ? 'bg-gray-600'
                                                : isSectionLocked(section.name)
                                                  ? 'bg-gray-400'
                                                  : 'bg-black',
                                        ]"
                                    >
                                        <CheckCircle v-if="isSectionSubmitted(section.name)" class="h-5 w-5 text-white sm:h-6 sm:w-6" />
                                        <Lock v-else-if="isSectionLocked(section.name)" class="h-5 w-5 text-white sm:h-6 sm:w-6" />
                                        <span v-else class="text-base font-bold text-white sm:text-lg">{{ index + 1 }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3
                                                :class="[
                                                    'text-lg font-bold sm:text-xl',
                                                    isSectionSubmitted(section.name)
                                                        ? 'text-gray-500'
                                                        : isSectionLocked(section.name)
                                                          ? 'text-gray-400'
                                                          : 'text-gray-900',
                                                ]"
                                            >
                                                {{ section.name }}
                                            </h3>
                                            <Badge v-if="isSectionSubmitted(section.name)" class="border-gray-400 bg-gray-100 text-xs text-gray-700">
                                                <CheckCircle class="mr-1 h-3 w-3" />
                                                Completed
                                            </Badge>
                                            <Badge
                                                v-else-if="isSectionLocked(section.name)"
                                                class="border-gray-300 bg-gray-200 text-xs text-gray-600"
                                            >
                                                <Lock class="mr-1 h-3 w-3" />
                                                Locked
                                            </Badge>
                                            <Badge
                                                v-else-if="hasWatchedVideo(section.name)"
                                                class="border-gray-400 bg-gray-100 text-xs text-gray-700"
                                            >
                                                <CheckCircle class="mr-1 h-3 w-3" />
                                                Ready
                                            </Badge>
                                        </div>
                                        <p
                                            :class="[
                                                'mt-1 text-xs sm:text-sm',
                                                isSectionSubmitted(section.name)
                                                    ? 'text-gray-400'
                                                    : isSectionLocked(section.name)
                                                      ? 'text-gray-400'
                                                      : 'text-gray-600',
                                            ]"
                                        >
                                            {{ section.description }}
                                        </p>
                                        <p v-if="isSectionLocked(section.name)" class="mt-1.5 text-xs text-gray-500 italic sm:mt-2">
                                            Complete {{ sectionOrder[sectionOrder.indexOf(section.name.toLowerCase()) - 1] }} section first
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2 sm:shrink-0 sm:gap-3">
                                    <!-- Video Button -->
                                    <Button
                                        v-if="!isSectionSubmitted(section.name) && !isSectionLocked(section.name)"
                                        @click="openVideoModal(section)"
                                        variant="outline"
                                        :class="[
                                            'rounded-lg px-3 py-1.5 text-sm font-semibold transition-all duration-200 sm:rounded-xl sm:px-4 sm:py-2',
                                            hasWatchedVideo(section.name)
                                                ? 'border-gray-400 bg-gray-50 text-gray-700 hover:bg-gray-100'
                                                : 'border-gray-400 bg-gray-50 text-gray-700 hover:bg-gray-100',
                                        ]"
                                    >
                                        <span class="flex items-center gap-1.5 sm:gap-2">
                                            <Video class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                            {{ hasWatchedVideo(section.name) ? 'Rewatch' : 'Watch Instructions' }}
                                        </span>
                                    </Button>

                                    <!-- Start Button -->
                                    <Button
                                        v-if="!isSectionSubmitted(section.name) && !isSectionLocked(section.name)"
                                        @click="startSection(section)"
                                        :disabled="!hasWatchedVideo(section.name)"
                                        :class="[
                                            'rounded-lg px-4 py-1.5 text-sm font-semibold shadow-lg transition-all duration-200 sm:rounded-xl sm:px-6 sm:py-2',
                                            hasWatchedVideo(section.name)
                                                ? 'bg-black text-white hover:scale-105 hover:bg-gray-800'
                                                : 'cursor-not-allowed bg-gray-300 text-gray-500',
                                        ]"
                                    >
                                        <span class="flex items-center gap-1.5 sm:gap-2">
                                            <PlayCircle class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                            Start
                                        </span>
                                    </Button>

                                    <!-- Locked State -->
                                    <div
                                        v-if="isSectionLocked(section.name)"
                                        class="cursor-not-allowed rounded-lg bg-gray-200 px-4 py-1.5 text-sm font-semibold text-gray-500 sm:rounded-xl sm:px-6 sm:py-2"
                                    >
                                        <span class="flex items-center gap-1.5 sm:gap-2">
                                            <Lock class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                            Locked
                                        </span>
                                    </div>

                                    <!-- Completed State -->
                                    <div
                                        v-if="isSectionSubmitted(section.name)"
                                        class="rounded-lg bg-gray-100 px-4 py-1.5 text-sm font-semibold text-gray-700 sm:rounded-xl sm:px-6 sm:py-2"
                                    >
                                        <span class="flex items-center gap-1.5 sm:gap-2">
                                            <CheckCircle class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                            Submitted
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- All Modules Completed - Home Button -->
                <div
                    v-if="allModulesCompleted"
                    class="flex flex-col items-center gap-3 border-2 border-gray-300 bg-gray-50 p-6 text-center sm:gap-4 sm:p-8"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-black sm:h-16 sm:w-16">
                        <CheckCircle class="h-6 w-6 text-white sm:h-8 sm:w-8" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">All Sections Completed!</h2>
                    <p class="max-w-md text-sm text-gray-600 sm:text-base">
                        You have successfully completed all exam sections. Return to your dashboard to view your results.
                    </p>
                    <Button
                        @click="goToDashboard"
                        class="mt-1 bg-black px-6 py-2.5 text-base font-semibold text-white shadow-lg hover:bg-gray-800 sm:mt-2 sm:px-8 sm:py-3 sm:text-lg"
                    >
                        <span class="flex items-center gap-2">
                            <Home class="h-4 w-4 sm:h-5 sm:w-5" />
                            Go to Dashboard
                        </span>
                    </Button>
                </div>

                <!-- Important Notices -->
                <div class="space-y-3 pt-4 sm:space-y-4 sm:pt-8">
                    <div class="border border-gray-300 bg-gray-50 p-4 sm:p-6">
                        <h3 class="mb-1.5 flex items-center gap-2 text-base font-semibold text-gray-800 sm:mb-2 sm:text-lg">
                            <Video class="h-4 w-4 shrink-0 sm:h-5 sm:w-5" />
                            Video Instructions Required
                        </h3>
                        <p class="text-sm text-gray-600 sm:text-base">
                            Watch the instructional video for each section before starting. The Start button will be enabled after completing the
                            video.
                        </p>
                    </div>

                    <div class="border border-gray-300 bg-gray-50 p-4 sm:p-6">
                        <h3 class="mb-1.5 flex items-center gap-2 text-base font-semibold text-gray-800 sm:mb-2 sm:text-lg">
                            <Lock class="h-4 w-4 shrink-0 sm:h-5 sm:w-5" />
                            Sequential Unlocking
                        </h3>
                        <p class="text-sm text-gray-600 sm:text-base">
                            Sections unlock in order: Listening → Reading → Writing. Complete each section to proceed to the next.
                        </p>
                    </div>

                    <div class="border border-gray-300 bg-gray-50 p-4 sm:p-6">
                        <h3 class="mb-1.5 flex items-center gap-2 text-base font-semibold text-gray-800 sm:mb-2 sm:text-lg">
                            <AlertTriangle class="h-4 w-4 shrink-0 sm:h-5 sm:w-5" />
                            Timer & Navigation
                        </h3>
                        <p class="text-sm text-gray-600 sm:text-base">
                            Once you start a section, the timer begins and cannot be paused. Navigation is restricted during active sections for exam
                            integrity.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exit Confirmation Modal -->
        <div
            v-if="showExitConfirmModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click="handleCancelExit"
        >
            <div class="w-full max-w-md transform rounded-2xl bg-white p-5 shadow-2xl transition-all duration-300 sm:rounded-3xl sm:p-8" @click.stop>
                <!-- Modal Header -->
                <div class="mb-4 flex items-start justify-between gap-2 sm:mb-6">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 sm:h-12 sm:w-12">
                            <AlertTriangle class="h-5 w-5 text-gray-700 sm:h-6 sm:w-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 sm:text-xl">Leave Exam?</h3>
                            <p class="text-xs text-gray-500 sm:text-sm">Confirm exit to exam list</p>
                        </div>
                    </div>
                    <Button @click="handleCancelExit" variant="ghost" size="sm" class="shrink-0 p-1 text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </Button>
                </div>

                <!-- Modal Content -->
                <div class="mb-6 space-y-3 sm:mb-8 sm:space-y-4">
                    <div class="rounded-lg border border-gray-300 bg-gray-50 p-3 sm:rounded-xl sm:p-4">
                        <h4 class="mb-1.5 text-sm font-semibold text-gray-800 sm:mb-2 sm:text-base">Warning</h4>
                        <ul class="space-y-0.5 text-xs text-gray-700 sm:space-y-1 sm:text-sm">
                            <li>• Your exam progress will be saved</li>
                            <li>• You can return to continue later</li>
                            <li>• Timer will stop for incomplete sections</li>
                            <li>• All submitted sections remain submitted</li>
                        </ul>
                    </div>

                    <div class="p-3 text-center sm:p-4">
                        <p class="text-sm font-medium text-gray-700 sm:text-base">
                            Are you sure you want to leave this exam and return to the exam list?
                        </p>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex flex-col gap-2 sm:flex-row sm:gap-3">
                    <Button
                        @click="handleCancelExit"
                        variant="outline"
                        class="order-2 flex-1 border-gray-300 py-2.5 hover:bg-gray-50 sm:order-1 sm:py-3"
                    >
                        Stay in Exam
                    </Button>
                    <Button
                        @click="handleConfirmExit"
                        class="order-1 flex-1 bg-black py-2.5 font-semibold text-white hover:bg-gray-800 sm:order-2 sm:py-3"
                    >
                        Leave Exam
                    </Button>
                </div>
            </div>
        </div>

        <!-- Video Instructions Modal -->
        <div
            v-if="showVideoModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
            @click="closeVideoModal"
        >
            <div class="w-full max-w-4xl transform rounded-2xl bg-white p-4 shadow-2xl transition-all duration-300 sm:rounded-3xl sm:p-8" @click.stop>
                <!-- Modal Header -->
                <div class="mb-4 flex items-start justify-between gap-2 sm:mb-6">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 sm:h-12 sm:w-12">
                            <Video class="h-5 w-5 text-gray-700 sm:h-6 sm:w-6" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 sm:text-2xl">{{ currentVideoSection?.name }} Instructions</h3>
                            <p class="text-xs text-gray-500 sm:text-sm">Watch this video before starting the section</p>
                        </div>
                    </div>
                    <Button @click="closeVideoModal" variant="ghost" size="sm" class="shrink-0 p-1 text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </Button>
                </div>

                <!-- Video Player -->
                <div class="mb-4 overflow-hidden rounded-xl bg-black shadow-2xl sm:mb-6 sm:rounded-2xl">
                    <video
                        ref="videoElement"
                        class="aspect-video w-full"
                        controls
                        @ended="handleVideoEnd"
                        :src="currentVideoSection ? getSectionVideoUrl(currentVideoSection.name) : ''"
                    >
                        Your browser does not support the video tag.
                    </video>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-2 sm:flex-row sm:gap-3">
                    <Button
                        @click="closeVideoModal"
                        variant="outline"
                        class="order-2 flex-1 border-gray-300 py-2.5 hover:bg-gray-50 sm:order-1 sm:py-3"
                    >
                        Close
                    </Button>
                    <Button
                        @click="
                            () => {
                                const section = currentVideoSection;
                                closeVideoModal();
                                if (section) {
                                    watchedVideos.add(section.name.toLowerCase());
                                    startSection(section);
                                }
                            }
                        "
                        class="order-1 flex-1 bg-black py-2.5 font-semibold text-white hover:bg-gray-800 sm:order-2 sm:py-3"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <PlayCircle class="h-4 w-4" />
                            Start Section Now
                        </span>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
