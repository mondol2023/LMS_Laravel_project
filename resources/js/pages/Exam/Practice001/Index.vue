<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, BookOpen, CheckCircle, Clock, PlayCircle, X } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

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

interface Props {
    exam: Exam;
    slug: string;
}

const props = defineProps<Props>();

const userPhone = ref(localStorage.getItem('userPhone'));
const showDialog = ref(false);
const selectedSection = ref<Section | null>(null);
const submittedSections = ref<Set<string>>(new Set());
const showExitConfirmModal = ref(false);

// Fetch user results from API
const fetchUserResults = async () => {
    if (!userPhone.value || !props.exam.id) {
        console.log('⚠️ No user phone or exam ID found');
        return;
    }

    try {
        const response = await fetch(`/api/drafts/results?phone=${userPhone.value}&exam_id=${props.exam.id}`);
        const data = await response.json();

        console.log('📊 Fetched user results:', data);

        if (data.success && data.results) {
            const newSubmittedSections = new Set<string>();

            // Helper function to check if section has valid data
            const hasValidData = (sectionData: any) => {
                if (!sectionData) return false;

                // Handle array format (writing section)
                if (Array.isArray(sectionData)) {
                    // Check if array has items and at least one has an answer
                    return sectionData.length > 0 && sectionData.some((item) => item.ans && item.ans.trim() !== '');
                }

                // Handle object format (listening, reading, speaking, or writing with numeric keys)
                if (typeof sectionData === 'object') {
                    if (Object.keys(sectionData).length === 0) return false;

                    // Check if it has completed_at timestamp (indicates real submission)
                    if (sectionData.completed_at) return true;

                    // For speaking or other sections without completed_at, check if status is not "not attempted"
                    if (sectionData.status && sectionData.status !== 'not attempted') return true;

                    // Handle writing section with numeric keys (e.g., {"0": {...}, "1": {...}, "band_score": 5})
                    // Check if object has numeric keys with question/answer data
                    const numericKeys = Object.keys(sectionData).filter((key) => !isNaN(Number(key)));
                    if (numericKeys.length > 0) {
                        // Check if at least one task has an answer
                        return numericKeys.some((key) => {
                            const task = sectionData[key];
                            return task && task.ans && task.ans.trim() !== '';
                        });
                    }
                }

                return false;
            };

            // Check each section's results
            if (hasValidData(data.results.listening)) {
                newSubmittedSections.add('listening');
                console.log('✅ Listening section has results - marking as submitted');
            }
            if (hasValidData(data.results.reading)) {
                newSubmittedSections.add('reading');
                console.log('✅ Reading section has results - marking as submitted');
            }
            if (hasValidData(data.results.writing)) {
                newSubmittedSections.add('writing');
                console.log('✅ Writing section has results - marking as submitted');
            }

            submittedSections.value = newSubmittedSections;
            console.log('📋 Submitted sections:', Array.from(submittedSections.value));
        }
    } catch (error) {
        console.error('❌ Failed to fetch user results:', error);
    }
};

// Check if a section is submitted
const isSectionSubmitted = (sectionName: string) => {
    return submittedSections.value.has(sectionName.toLowerCase());
};

const openStartDialog = (section: Section) => {
    // Prevent opening dialog for already submitted sections
    if (isSectionSubmitted(section.name)) {
        console.log(`⛔ Cannot start ${section.name} - already submitted`);
        return;
    }
    selectedSection.value = section;
    showDialog.value = true;
};

const closeDialog = () => {
    showDialog.value = false;
    selectedSection.value = null;
};

const startSection = () => {
    if (!selectedSection.value) return;

    const sectionName = selectedSection.value.name.toLowerCase();
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

    closeDialog();
};

const handleExamListClick = () => {
    showExitConfirmModal.value = true;
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
onMounted(async () => {
    // Fetch user results to check submitted sections
    await fetchUserResults();

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
    <Head :title="`${exam.name} - Nextive Solution`" />
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur-sm">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="text-3xl">📝</div>
                        <h1 class="text-xl font-bold text-gray-900">Nextive Solution</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge variant="outline" class="border-blue-200 bg-blue-50 text-sm font-medium text-blue-700"> Exam ID: {{ exam.id }} </Badge>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-12">
            <div class="mx-auto max-w-4xl space-y-8">
                <!-- Header -->
                <div class="space-y-6 text-center">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700"
                    >
                        <PlayCircle class="h-4 w-4" />
                        Go to exam list
                    </div>
                    <h1 class="text-5xl font-bold text-gray-900 md:text-6xl">
                        {{ exam.name }}
                    </h1>
                    <p class="mx-auto max-w-2xl text-xl leading-relaxed text-gray-600">
                        Complete {{ exam.type }} IELTS practice test with real exam conditions and instant feedback.
                    </p>

                    <!-- Exam Stats -->
                    <div class="flex items-center justify-center gap-8 pt-4">
                        <div class="flex items-center gap-2 text-gray-600">
                            <Clock class="h-5 w-5" />
                            <span class="font-medium">{{ exam.duration }} minutes</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <BookOpen class="h-5 w-5" />
                            <span class="font-medium">{{ exam.sections.length }} sections</span>
                        </div>
                    </div>
                </div>

                <!-- Test Sections -->
                <Card class="border-2 border-blue-100 bg-gradient-to-r from-blue-50/50 to-indigo-50/50">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-2xl text-black">
                            <BookOpen class="h-6 w-6 text-blue-600" />
                            Test Sections
                        </CardTitle>
                        <CardDescription class="text-lg">
                            Complete all {{ exam.sections.length }} sections to finish your IELTS practice test
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6">
                            <div
                                v-for="(section, index) in exam.sections"
                                :key="section.name"
                                :class="[
                                    'flex items-center justify-between rounded-2xl border p-6 shadow-sm transition-all duration-300',
                                    isSectionSubmitted(section.name)
                                        ? 'border-gray-200 bg-gray-50 opacity-75'
                                        : 'border-gray-100 bg-white hover:border-blue-200 hover:shadow-md',
                                ]"
                            >
                                <div class="flex items-center gap-4">
                                    <div
                                        :class="[
                                            'flex h-12 w-12 items-center justify-center rounded-full shadow-lg',
                                            isSectionSubmitted(section.name)
                                                ? 'bg-gradient-to-br from-green-500 to-emerald-600'
                                                : 'bg-gradient-to-br from-blue-500 to-indigo-600',
                                        ]"
                                    >
                                        <CheckCircle v-if="isSectionSubmitted(section.name)" class="h-6 w-6 text-white" />
                                        <span v-else class="text-lg font-bold text-white">{{ index + 1 }}</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 :class="['text-xl font-bold', isSectionSubmitted(section.name) ? 'text-gray-500' : 'text-gray-900']">
                                                {{ section.name }}
                                            </h3>
                                            <Badge v-if="isSectionSubmitted(section.name)" class="border-green-200 bg-green-100 text-green-700">
                                                Completed
                                            </Badge>
                                        </div>
                                        <p :class="['mt-1', isSectionSubmitted(section.name) ? 'text-gray-400' : 'text-gray-600']">
                                            {{ section.description }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6">
                                    <div class="text-right">
                                        <div :class="['text-lg font-semibold', isSectionSubmitted(section.name) ? 'text-gray-500' : 'text-gray-900']">
                                            {{ section.duration }} min
                                        </div>
                                        <div class="text-sm text-gray-500">{{ section.questions }} questions</div>
                                    </div>
                                    <Button
                                        v-if="!isSectionSubmitted(section.name)"
                                        @click="openStartDialog(section)"
                                        class="rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-2 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-green-700 hover:to-emerald-700"
                                    >
                                        <span class="flex items-center gap-2">
                                            <PlayCircle class="h-4 w-4" />
                                            Start
                                        </span>
                                    </Button>
                                    <div v-else class="cursor-not-allowed rounded-xl bg-gray-200 px-6 py-2 font-semibold text-gray-500">
                                        <span class="flex items-center gap-2">
                                            <CheckCircle class="h-4 w-4" />
                                            Submitted
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Important Notice -->
                <div class="space-y-6 pt-8 text-center">
                    <div class="mb-4 rounded-2xl border border-red-200 bg-gradient-to-r from-red-50 to-pink-50 p-6">
                        <h3 class="mb-2 text-lg font-semibold text-red-800">🚫 Navigation Restricted</h3>
                        <p class="text-red-700">
                            Browser back button and page refresh are disabled during the exam for security purposes. You will be warned before
                            accidentally leaving the exam.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50 p-6">
                        <h3 class="mb-2 text-lg font-semibold text-yellow-800">⚠️ Important Notice</h3>
                        <p class="text-yellow-700">
                            Once you start a section, the timer will begin and you cannot pause. Complete sections in order.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exit Confirmation Modal -->
        <div
            v-if="showExitConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            @click="handleCancelExit"
        >
            <div class="mx-4 w-full max-w-md transform rounded-3xl bg-white p-8 shadow-2xl transition-all duration-300" @click.stop>
                <!-- Dialog Header -->
                <div class="mb-6 flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                            <AlertTriangle class="h-6 w-6 text-red-600" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Leave Exam?</h3>
                            <p class="text-sm text-gray-500">Confirm Exit</p>
                        </div>
                    </div>
                    <Button @click="handleCancelExit" variant="ghost" size="sm" class="p-1 text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </Button>
                </div>

                <!-- Dialog Content -->
                <div class="mb-8 space-y-4">
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <h4 class="mb-2 font-semibold text-red-800">⚠️ Warning</h4>
                        <ul class="space-y-1 text-sm text-red-700">
                            <li>• All incomplete sections will be submitted automatically</li>
                            <li>• Your current progress will be saved</li>
                            <li>• You cannot resume this exam once you leave</li>
                            <li>• Make sure you have completed all sections</li>
                        </ul>
                    </div>

                    <div class="p-4 text-center">
                        <p class="font-medium text-gray-700">Are you sure you want to leave this exam and return to the exam list?</p>
                        <p class="mt-2 text-sm text-gray-500">This action will submit all your answers and cannot be undone.</p>
                    </div>
                </div>

                <!-- Dialog Actions -->
                <div class="flex gap-3">
                    <Button @click="handleCancelExit" variant="outline" class="flex-1 border-gray-300 py-3 hover:bg-gray-50"> Stay Here </Button>
                    <Button
                        @click="handleConfirmExit"
                        class="flex-1 bg-gradient-to-r from-red-600 to-red-700 py-3 font-semibold text-white hover:from-red-700 hover:to-red-800"
                    >
                        <span class="flex items-center gap-2">
                            <AlertTriangle class="h-4 w-4" />
                            Leave Exam
                        </span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Confirmation Dialog -->
        <div v-if="showDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click="closeDialog">
            <div class="mx-4 w-full max-w-md transform rounded-3xl bg-white p-8 shadow-2xl transition-all duration-300" @click.stop>
                <!-- Dialog Header -->
                <div class="mb-6 flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100">
                            <AlertTriangle class="h-6 w-6 text-orange-600" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Start Section Confirmation</h3>
                            <p class="text-sm text-gray-500">{{ selectedSection?.name }} Section</p>
                        </div>
                    </div>
                    <Button @click="closeDialog" variant="ghost" size="sm" class="p-1 text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </Button>
                </div>

                <!-- Dialog Content -->
                <div class="mb-8 space-y-4">
                    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                        <h4 class="mb-2 font-semibold text-yellow-800">⚠️ Important Notice</h4>
                        <ul class="space-y-1 text-sm text-yellow-700">
                            <li>• Once started, the timer cannot be paused</li>
                            <li>
                                • You have {{ selectedSection?.duration }} {{ selectedSection?.name === 'Listening' ? '+ 2' : '' }} minutes to
                                complete
                            </li>
                            <li>• {{ selectedSection?.questions }} questions must be answered</li>
                            <li>• You cannot go back once this section is finished</li>
                        </ul>
                    </div>

                    <div class="p-4 text-center">
                        <p class="font-medium text-gray-700">
                            Are you ready to start the
                            <strong>{{ selectedSection?.name }}</strong> section?
                        </p>
                        <p class="mt-2 text-sm text-gray-500">
                            {{ selectedSection?.description }}
                        </p>
                    </div>
                </div>

                <!-- Dialog Actions -->
                <div class="flex gap-3">
                    <Button @click="closeDialog" variant="outline" class="flex-1 border-gray-300 py-3 hover:bg-gray-50"> Cancel </Button>
                    <Button
                        @click="startSection"
                        class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 py-3 font-semibold text-white hover:from-green-700 hover:to-emerald-700"
                    >
                        <span class="flex items-center gap-2">
                            <PlayCircle class="h-4 w-4" />
                            Start Section
                        </span>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
