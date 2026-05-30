<script setup lang="ts">
import C19Test4ReadingPart1 from '@/components/Exam/Practice/C19/Test4/Reading/C19Test4ReadingPart1.vue';
import C19Test4ReadingPart2 from '@/components/Exam/Practice/C19/Test4/Reading/C19Test4ReadingPart2.vue';
import C19Test4ReadingPart3 from '@/components/Exam/Practice/C19/Test4/Reading/C19Test4ReadingPart3.vue';
import ReadingReviewModal from '@/components/Reading/ReadingReviewModal.vue';
import ReadingTestFooter from '@/components/Reading/ReadingTestFooter.vue';
import ReadingTestHeader from '@/components/Reading/ReadingTestHeader.vue';
import { useReadingTestPage } from '@/composables/useReadingTestPage';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    slug: string;
    section: string;
    userPhone?: string;
    examId?: string;
}

const props = defineProps<Props>();

const readingPart1Ref = ref<any>();
const readingPart2Ref = ref<any>();
const readingPart3Ref = ref<any>();

const userPhone = ref(props.userPhone || localStorage.getItem('userPhone'));
const examId = computed(() => props.examId || props.slug);

const correctAnswers: Record<number, string> = {
    1: 'FALSE',
    2: 'TRUE',
    3: 'FALSE',
    4: 'NOT GIVEN',
    5: 'FALSE',
    6: 'TRUE',
    7: 'colonies',
    8: 'spring',
    9: 'endangered',
    10: 'habitats',
    11: 'Europe',
    12: 'southern',
    13: 'diet',
    14: 'C',
    15: 'F',
    16: 'E',
    17: 'D',
    18: 'D',
    19: 'B',
    20: 'A',
    21: 'E',
    22: 'B',
    23: 'C',
    24: 'waste',
    25: 'machinery',
    26: 'caution',
    27: 'C',
    28: 'C',
    29: 'B',
    30: 'A',
    31: 'egalitarianism',
    32: 'status',
    33: 'hunting',
    34: 'domineering',
    35: 'autonomy',
    36: 'NOT GIVEN',
    37: 'NO',
    38: 'YES',
    39: 'NOT GIVEN',
    40: 'NO',
};

const {
    activePart,
    showSubmitModal,
    showReviewModal,
    answeredQuestions,
    isTimeUp,
    isLeavingPage,
    part1Notes,
    part2Notes,
    part3Notes,
    handleNavigate,
    handleScrollToQuestion,
    handleShowSubmitModal,
    handleCancelSubmit,
    handleShowReviewModal,
    handleCloseReviewModal,
    handleTimeUp,
    handleConfirmSubmit,
    handleForceSubmit,
    getAllUserAnswers,
    handleDeleteNote,
    handleFocusNote,
} = useReadingTestPage({
    slug: props.slug,
    examId: examId.value,
    testNumber: 8,
    correctAnswers,
    readingPart1Ref,
    readingPart2Ref,
    readingPart3Ref,
    userPhone: userPhone.value,
});
</script>

<template>
    <Head title="Reading Section - Nextive Solution" />
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 text-black">
        <ReadingTestHeader
            :part1-notes="part1Notes"
            :part2-notes="part2Notes"
            :part3-notes="part3Notes"
            @show-submit-modal="handleShowSubmitModal"
            @show-review-modal="handleShowReviewModal"
            @time-up="handleTimeUp"
            @delete-note="handleDeleteNote"
            @focus-note="handleFocusNote"
        />

        <div :class="{ hidden: activePart !== 'part1' }">
            <C19Test4ReadingPart1 ref="readingPart1Ref" :phone="userPhone" :exam-id="examId" />
        </div>
        <div :class="{ hidden: activePart !== 'part2' }">
            <C19Test4ReadingPart2 ref="readingPart2Ref" :phone="userPhone" :exam-id="examId" />
        </div>
        <div :class="{ hidden: activePart !== 'part3' }">
            <C19Test4ReadingPart3 ref="readingPart3Ref" :phone="userPhone" :exam-id="examId" />
        </div>

        <ReadingTestFooter
            :active-part="activePart"
            :answered-questions="answeredQuestions"
            @navigate="handleNavigate"
            @scroll-to-question="handleScrollToQuestion"
        />

        <ReadingReviewModal
            :is-visible="showReviewModal"
            :user-answers="getAllUserAnswers()"
            :answered-questions="answeredQuestions"
            @close="handleCloseReviewModal"
        />

        <!-- Submit Modal -->
        <div
            v-if="showSubmitModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4"
            @click="isTimeUp ? null : handleCancelSubmit"
        >
            <div class="relative z-[10000] w-full max-w-md rounded-lg bg-white p-4 shadow-xl sm:p-6" @click.stop>
                <div class="mb-4 flex items-center gap-3">
                    <div
                        :class="['flex h-8 w-8 items-center justify-center rounded-full sm:h-10 sm:w-10', isTimeUp ? 'bg-orange-100' : 'bg-red-100']"
                    >
                        <svg v-if="isTimeUp" class="h-4 w-4 text-orange-600 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <svg v-else class="h-4 w-4 text-red-600 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 sm:text-lg">
                        {{ isTimeUp ? 'Time is Up!' : 'Submit Test' }}
                    </h3>
                </div>
                <p class="mb-6 text-sm text-gray-600 sm:text-base">
                    <span v-if="isTimeUp" class="font-semibold text-orange-600"> Your time has expired. You must submit your test now. </span>
                    <span v-else> Are you sure you want to submit your reading test? This action cannot be undone. </span>
                </p>
                <div class="flex gap-3">
                    <button
                        v-if="!isTimeUp"
                        @click="handleCancelSubmit"
                        class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 sm:px-4 sm:text-sm"
                    >
                        Continue Test
                    </button>
                    <button
                        @click="handleConfirmSubmit"
                        :class="[
                            'rounded-lg px-3 py-2 text-xs font-medium text-white sm:px-4 sm:text-sm',
                            isTimeUp ? 'w-full bg-orange-600 hover:bg-orange-700' : 'flex-1 bg-red-600 hover:bg-red-700',
                        ]"
                    >
                        Submit Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
