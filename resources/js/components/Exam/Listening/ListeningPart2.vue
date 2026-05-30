<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { useTooltip, type Note, type TextSegment } from '@/composables/useTooltip';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import HighlightTooltips from './HighlightTooltips.vue';

interface Props {
    fontSize?: number;
    flaggedQuestions?: Set<number>;
}

const props = withDefaults(defineProps<Props>(), {
    fontSize: 16,
    flaggedQuestions: () => new Set<number>(),
});

const emit = defineEmits<{
    toggleFlag: [questionNumber: number];
}>();

// Track hovered question for showing flag icon
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

// Toggle flag for a question
const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Listening to Part 2 component

// Single choice answers for questions 11-16
const answers = ref<Record<string, string>>({
    q11: '',
    q12: '',
    q13: '',
    q14: '',
    q15: '',
    q16: '',
});

// Multiple choice answers for questions 17-20
const multipleAnswers = ref<{ q17_18: string[]; q19_20: string[] }>({
    q17_18: [],
    q19_20: [],
});

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Note[]>([]);

// Text segments with their cumulative offsets (each offset = previous offset + previous text length + 1)
const textSegments = ref<TextSegment[]>([
    // Part header box text segments
    { id: 'part2-title', text: 'Part 2', offset: 0 },                                    // 0-6
    { id: 'part2-desc', text: 'Listen and answer questions 11–20.', offset: 7 },        // 7-42
    // Instruction section text segments
    { id: 0, text: 'Questions 11–20', offset: 43 },                                      // 43-58
    { id: 1, text: 'Choose the correct letter, A, B or C.', offset: 59 },               // 59-96
    { id: 2, text: 'Adbourne Film Festival', offset: 97 },                               // 97-119
    // Question 11
    { id: 'q11', text: '11.', offset: 120 },
    { id: 3, text: 'Why was the Film Festival started?', offset: 124 },
    { id: 4, text: 'To encourage local people to make films.', offset: 159 },
    { id: 5, text: 'To bring more tourists to the town.', offset: 202 },
    { id: 6, text: 'To use money released from another project.', offset: 240 },
    // Question 12
    { id: 'q12', text: '12.', offset: 286 },
    { id: 7, text: 'What is the price range for tickets?', offset: 290 },
    { id: 8, text: '£1.00-£2.50', offset: 327 },
    { id: 9, text: '50p – £2.00', offset: 341 },
    { id: 10, text: '£1.50-£2.50', offset: 355 },
    // Question 13
    { id: 'q13', text: '13.', offset: 369 },
    { id: 11, text: 'As well as online, tickets for the films can be obtained', offset: 373 },
    { id: 12, text: 'from the local library.', offset: 430 },
    { id: 13, text: 'from several different shops.', offset: 456 },
    { id: 14, text: 'from the two festival cinemas.', offset: 488 },
    // Question 14
    { id: 'q14', text: '14.', offset: 521 },
    { id: 15, text: "Last year's winning film was about", offset: 525 },
    { id: 16, text: 'farms of the future.', offset: 561 },
    { id: 17, text: 'schools and the environment.', offset: 584 },
    { id: 18, text: 'green transport options.', offset: 615 },
    // Question 15
    { id: 'q15', text: '15.', offset: 642 },
    { id: 19, text: 'This year the competition prize is', offset: 646 },
    { id: 20, text: 'a stay in a hotel.', offset: 682 },
    { id: 21, text: 'film-making equipment.', offset: 703 },
    { id: 22, text: 'a sum of money.', offset: 728 },
    // Question 16
    { id: 'q16', text: '16.', offset: 746 },
    { id: 23, text: 'The deadline for entering a film in the competition is the end of', offset: 750 },
    { id: 24, text: 'May.', offset: 816 },
    { id: 25, text: 'June.', offset: 823 },
    { id: 26, text: 'July.', offset: 831 },
    // Questions 17-18
    { id: 'q17-18', text: 'Questions 17 and 18', offset: 839 },
    { id: 'q17-18-inst', text: 'Choose TWO letters, A-E.', offset: 866 },
    { id: 27, text: 'What TWO main criteria are used to judge the film competition?', offset: 891 },
    { id: 28, text: 'Ability to persuade.', offset: 954 },
    { id: 29, text: 'Quality of the story.', offset: 977 },
    { id: 30, text: 'Memorable characters.', offset: 1001 },
    { id: 31, text: 'Quality of photography.', offset: 1025 },
    { id: 32, text: 'Originality.', offset: 1051 },
    // Questions 19-20
    { id: 'q19-20', text: 'Questions 19 and 20', offset: 1066 },
    { id: 'q19-20-inst', text: 'Choose TWO letters, A-E.', offset: 1093 },
    { id: 33, text: 'What TWO changes will be made to the competition next year?', offset: 1118 },
    { id: 34, text: 'A new way of judging', offset: 1178 },
    { id: 35, text: 'A different length of time', offset: 1201 },
    { id: 36, text: 'An additional age category', offset: 1230 },
    { id: 37, text: 'Different performance times', offset: 1259 },
    { id: 38, text: 'New locations for performances', offset: 1289 },
]);

// Use the tooltip composable
const {
    contextMenuPosition,
    showContextMenu,
    showDeleteTooltip,
    deleteTooltipPosition,
    highlightToDelete,
    closeDeleteTooltip,
    showNoteTooltip,
    noteTooltipPosition,
    hoveredNoteId,
    hoveredNoteText,
    handleTooltipMouseLeave,
    showNoteInput,
    noteInputText,
    noteInputPosition,
    openNoteInput,
    cancelNote,
    selectedText,
    selectionRange,
    handleContentTextSelect: tooltipHandleContentTextSelect,
    handleContentClick: tooltipHandleContentClick,
    setupEventListeners,
    cleanupEventListeners,
} = useTooltip(textSegments, notes);

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: number | string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    // Check if any highlights overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    // Also check notes that overlap with this segment
    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return plainText;
    }

    // Combine highlights and notes into a single list of annotations
    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset,
            end: h.end_offset,
            type: 'highlight' as const,
            color: h.color,
            id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start,
            end: n.end,
            type: 'note' as const,
            color: 'blue',
            id: n.id,
            noteText: n.note,
        })),
    ];

    // Sort by start offset descending so we can apply from end to start
    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = plainText;
    for (const annotation of sorted) {
        const annotationStart = Math.max(0, annotation.start - segmentOffset);
        const annotationEnd = Math.min(plainText.length, annotation.end - segmentOffset);

        if (annotationStart < annotationEnd) {
            const before = result.substring(0, annotationStart);
            const annotated = result.substring(annotationStart, annotationEnd);
            const after = result.substring(annotationEnd);

            if (annotation.type === 'note') {
                // Just render the mark with data attributes - tooltip will be rendered via Teleport
                result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
            } else {
                result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
            }
        }
    }

    return result;
};

// Expose methods for parent component
const getAnswers = () => {
    const allAnswers = { ...answers.value };

    // Handle multiple choice questions
    if (multipleAnswers.value.q17_18?.length > 0) {
        allAnswers.q17 = multipleAnswers.value.q17_18[0] || '';
        allAnswers.q18 = multipleAnswers.value.q17_18[1] || '';
    } else {
        allAnswers.q17 = '';
        allAnswers.q18 = '';
    }

    if (multipleAnswers.value.q19_20?.length > 0) {
        allAnswers.q19 = multipleAnswers.value.q19_20[0] || '';
        allAnswers.q20 = multipleAnswers.value.q19_20[1] || '';
    } else {
        allAnswers.q19 = '';
        allAnswers.q20 = '';
    }

    return allAnswers;
};

const handleMultipleChoice = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        // Remove if already selected
        currentAnswers.splice(index, 1);
    } else if (currentAnswers.length < 2) {
        // Add if less than 2 selected
        currentAnswers.push(option);
    }
};

const isSelected = (questionGroup: string, option: string) => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
};

const isDisabled = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    // Disable if 2 already selected AND this option is not one of them
    return currentAnswers.length >= 2 && !currentAnswers.includes(option);
};

// Use the tooltip composable's handleContentTextSelect
const handleContentTextSelect = () => {
    tooltipHandleContentTextSelect();
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

    // Remove overlapping notes (last action wins - highlight overwrites note)
    notes.value = notes.value.filter((n) => {
        return !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start);
    });

    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);

    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;

    // Remove overlapping highlights (last action wins - note overwrites highlight)
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

    // Remove any existing notes that overlap with this range
    notes.value = notes.value.filter((n) => {
        const overlaps = n.start < newEnd && n.end > newStart;
        return !overlaps;
    });

    const noteId = Date.now();
    notes.value.push({
        id: noteId,
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: newStart,
        end: newEnd,
    });

    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const deleteNote = (id: number) => {
    notes.value = notes.value.filter((note) => note.id !== id);
};

// Delete highlight confirmation handler
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
    }
};

// Delete note from hover tooltip
const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    // Handle grouped questions for 17-18 and 19-20
    let targetId = `question-${questionNumber}`;
    if (questionNumber === 17 || questionNumber === 18) {
        targetId = 'question-17-18';
    } else if (questionNumber === 19 || questionNumber === 20) {
        targetId = 'question-19-20';
    }

    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        // Add highlight effect
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

// Load saved answers when component mounts
onMounted(() => {
    setupEventListeners();
});

onBeforeUnmount(() => {
    cleanupEventListeners();
});

defineExpose({
    getAnswers,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="tooltipHandleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part2-desc"
                    v-html="getHighlightedSegmentById('part2-desc')"
                ></p>
            </div>

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"
                ></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
            </div>

            <!-- Section Title -->
            <div class="px-2 sm:px-3">
                <h2
                    class="text-lg font-bold text-gray-900 select-text"
                    data-segment-id="2"
                    v-html="getHighlightedSegmentById(2)"
                ></h2>
            </div>

            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <!-- Questions 11-16: Single Choice MCQ -->
                    <div class="space-y-2">
                        <!-- Question 11 -->
                        <div
                            id="question-11"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 11"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q11" v-html="getHighlightedSegmentById('q11')"></span>
                                <div class="min-w-0 flex-1">
                                    <!-- Bookmark Button -->
                                    <button
                                        v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                        @click.stop="toggleFlag(11)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(11)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q11"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="4"
                                                v-html="getHighlightedSegmentById(4)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q11"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="5"
                                                v-html="getHighlightedSegmentById(5)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q11"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="6"
                                                v-html="getHighlightedSegmentById(6)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 12 -->
                        <div
                            id="question-12"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 12"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q12" v-html="getHighlightedSegmentById('q12')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                        @click.stop="toggleFlag(12)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(12)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q12"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="8"
                                                v-html="getHighlightedSegmentById(8)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q12"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="9"
                                                v-html="getHighlightedSegmentById(9)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q12"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="10"
                                                v-html="getHighlightedSegmentById(10)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 13 -->
                        <div
                            id="question-13"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 13"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q13" v-html="getHighlightedSegmentById('q13')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                        @click.stop="toggleFlag(13)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(13)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q13"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="12"
                                                v-html="getHighlightedSegmentById(12)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q13"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="13"
                                                v-html="getHighlightedSegmentById(13)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q13"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="14"
                                                v-html="getHighlightedSegmentById(14)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 14 -->
                        <div
                            id="question-14"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 14"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q14" v-html="getHighlightedSegmentById('q14')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                        @click.stop="toggleFlag(14)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(14)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q14"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="16"
                                                v-html="getHighlightedSegmentById(16)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q14"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="17"
                                                v-html="getHighlightedSegmentById(17)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q14"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="18"
                                                v-html="getHighlightedSegmentById(18)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 15 -->
                        <div
                            id="question-15"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 15"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q15" v-html="getHighlightedSegmentById('q15')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                        @click.stop="toggleFlag(15)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(15)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q15"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="20"
                                                v-html="getHighlightedSegmentById(20)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q15"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="21"
                                                v-html="getHighlightedSegmentById(21)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q15"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="22"
                                                v-html="getHighlightedSegmentById(22)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 16 -->
                        <div
                            id="question-16"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 16"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q16" v-html="getHighlightedSegmentById('q16')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                        @click.stop="toggleFlag(16)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(16)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q16"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="24"
                                                v-html="getHighlightedSegmentById(24)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q16"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="25"
                                                v-html="getHighlightedSegmentById(25)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q16"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="26"
                                                v-html="getHighlightedSegmentById(26)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 17-18: Multiple Choice -->
                        <div
                            id="question-17-18"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 17"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 17 || isQuestionFlagged(17) || isQuestionFlagged(18)"
                                @click.stop="
                                    toggleFlag(17);
                                    toggleFlag(18);
                                "
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="
                                    isQuestionFlagged(17) || isQuestionFlagged(18)
                                        ? 'border-gray-400 bg-transparent text-red-500'
                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                "
                                :title="isQuestionFlagged(17) || isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>
                            <div class="mb-2">
                                <div class="mb-2">
                                    <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q17-18" v-html="getHighlightedSegmentById('q17-18')"></h3>
                                </div>
                                <div class="p-2">
                                    <p class="mb-2 text-base leading-relaxed text-gray-900 select-text" data-segment-id="q17-18-inst" v-html="getHighlightedSegmentById('q17-18-inst')"></p>
                                    <p
                                        class="text-lg leading-tight font-bold text-gray-900 select-text"
                                        data-segment-id="27"
                                        v-html="getHighlightedSegmentById(27)"
                                    ></p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q17_18', 'A')"
                                        :disabled="isDisabled('q17_18', 'A')"
                                        @change="handleMultipleChoice('q17_18', 'A')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="28"
                                        v-html="getHighlightedSegmentById(28)"
                                    ></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q17_18', 'B')"
                                        :disabled="isDisabled('q17_18', 'B')"
                                        @change="handleMultipleChoice('q17_18', 'B')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="29"
                                        v-html="getHighlightedSegmentById(29)"
                                    ></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q17_18', 'C')"
                                        :disabled="isDisabled('q17_18', 'C')"
                                        @change="handleMultipleChoice('q17_18', 'C')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="30"
                                        v-html="getHighlightedSegmentById(30)"
                                    ></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q17_18', 'D')"
                                        :disabled="isDisabled('q17_18', 'D')"
                                        @change="handleMultipleChoice('q17_18', 'D')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="31"
                                        v-html="getHighlightedSegmentById(31)"
                                    ></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q17_18', 'E')"
                                        :disabled="isDisabled('q17_18', 'E')"
                                        @change="handleMultipleChoice('q17_18', 'E')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="32"
                                        v-html="getHighlightedSegmentById(32)"
                                    ></span>
                                </label>
                            </div>

                            <div class="mt-2 p-2">
                                <p class="text-sm font-medium text-gray-900">Selected: {{ multipleAnswers.q17_18.length }}/2 answers</p>
                            </div>
                        </div>

                        <!-- Questions 19-20: Multiple Choice -->
                        <div
                            id="question-19-20"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 19"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 19 || isQuestionFlagged(19) || isQuestionFlagged(20)"
                                @click.stop="
                                    toggleFlag(19);
                                    toggleFlag(20);
                                "
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="
                                    isQuestionFlagged(19) || isQuestionFlagged(20)
                                        ? 'border-gray-400 bg-transparent text-red-500'
                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                "
                                :title="isQuestionFlagged(19) || isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>
                            <div class="mb-2">
                                <div class="mb-2">
                                    <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q19-20" v-html="getHighlightedSegmentById('q19-20')"></h3>
                                </div>
                                <div class="p-2">
                                    <p class="mb-2 text-base leading-relaxed text-gray-900 select-text" data-segment-id="q19-20-inst" v-html="getHighlightedSegmentById('q19-20-inst')"></p>
                                    <p
                                        class="text-lg leading-tight font-bold text-gray-900 select-text"
                                        data-segment-id="33"
                                        v-html="getHighlightedSegmentById(33)"
                                    ></p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q19_20', 'A')"
                                        :disabled="isDisabled('q19_20', 'A')"
                                        @change="handleMultipleChoice('q19_20', 'A')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="34"
                                        v-html="getHighlightedSegmentById(34)"
                                    ></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q19_20', 'B')"
                                        :disabled="isDisabled('q19_20', 'B')"
                                        @change="handleMultipleChoice('q19_20', 'B')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="35"
                                        v-html="getHighlightedSegmentById(35)"
                                    ></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q19_20', 'C')"
                                        :disabled="isDisabled('q19_20', 'C')"
                                        @change="handleMultipleChoice('q19_20', 'C')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="36"
                                        v-html="getHighlightedSegmentById(36)"
                                    ></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q19_20', 'D')"
                                        :disabled="isDisabled('q19_20', 'D')"
                                        @change="handleMultipleChoice('q19_20', 'D')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="37"
                                        v-html="getHighlightedSegmentById(37)"
                                    ></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q19_20', 'E')"
                                        :disabled="isDisabled('q19_20', 'E')"
                                        @change="handleMultipleChoice('q19_20', 'E')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span
                                        class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="38"
                                        v-html="getHighlightedSegmentById(38)"
                                    ></span>
                                </label>
                            </div>

                            <div class="mt-2 p-2">
                                <p class="text-sm font-medium text-gray-900">Selected: {{ multipleAnswers.q19_20.length }}/2 answers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Highlight Tooltips Component -->
    <HighlightTooltips
        :show-context-menu="showContextMenu"
        :context-menu-position="contextMenuPosition"
        :show-delete-tooltip="showDeleteTooltip"
        :delete-tooltip-position="deleteTooltipPosition"
        :show-note-tooltip="showNoteTooltip"
        :note-tooltip-position="noteTooltipPosition"
        :hovered-note-text="hoveredNoteText"
        :show-note-input="showNoteInput"
        :note-input-position="noteInputPosition"
        :selected-text="selectedText"
        :note-input-text="noteInputText"
        @open-note-input="openNoteInput"
        @apply-highlight="applyHighlight"
        @confirm-delete-highlight="confirmDeleteHighlight"
        @close-delete-tooltip="closeDeleteTooltip"
        @delete-note-from-tooltip="deleteNoteFromTooltip"
        @handle-tooltip-mouse-leave="handleTooltipMouseLeave"
        @save-note="saveNote"
        @cancel-note="cancelNote"
        @update:note-input-text="noteInputText = $event"
    />
</template>

<style scoped>
.highlight-question {
    background-color: rgba(0, 0, 0, 0.08);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightFade 1.5s ease-out;
}

@keyframes highlightFade {
    0% {
        background-color: rgba(0, 0, 0, 0.15);
    }
    100% {
        background-color: rgba(0, 0, 0, 0.08);
    }
}

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #000000;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #374151;
}

/* Highlight functionality styles */
.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
}

.highlight-yellow {
    background-color: rgba(254, 240, 138, 0.5);
}

.highlight-green {
    background-color: rgba(187, 247, 208, 0.5);
}

.highlight-blue {
    background-color: rgba(191, 219, 254, 0.5);
}

.highlight-pink {
    background-color: rgba(251, 207, 232, 0.5);
}

.highlight-orange {
    background-color: rgba(254, 215, 170, 0.5);
}
</style>

<!-- Non-scoped styles for v-html generated note indicators -->
<style>
.note-highlight {
    border-bottom: 2px solid rgba(0, 0, 0, 0.4);
    cursor: help;
    position: relative;
    display: inline;
}

.note-highlight::after {
    content: '📝';
    display: inline-block;
    margin-left: 2px;
    font-size: 0.7em;
    vertical-align: super;
    line-height: 0;
    opacity: 0.9;
    pointer-events: none;
}

.note-highlight:hover {
    border-bottom-color: #000;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
