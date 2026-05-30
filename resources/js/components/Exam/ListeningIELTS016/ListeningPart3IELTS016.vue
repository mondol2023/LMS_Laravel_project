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

// Listening Part 3 component

// Single choice answers for questions 21-24
const answers = ref<Record<string, string>>({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q26: '',
    q27: '',
    q28: '',
    q29: '',
    q30: '',
});

// Multiple choice answers for questions 25-30


// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Note[]>([]);

// Text segments with their cumulative offsets (each offset = previous offset + previous text length + 1)
const textSegments = ref<TextSegment[]>([
    // Part header box text segments
    { id: 'section3-title', text: 'Part 3', offset: 0 },                                          // 0-9
    // Questions 21-25 Header
    { id: 'q21-25-header', text: 'Questions 21–30', offset: 10 },                                    // 10-25
    { id: 'q21-25-inst1', text: 'Complete the notes below.', offset: 26 },                           // 26-51
    { id: 'q21-25-inst2', text: 'Write NO MORE THAN TWO WORDS for each answer.', offset: 52 },       // 52-99

    // Part One - Checklist
    { id: 'part1-title', text: 'Part One – Checklist:', offset: 100 },                               // 100-121

    // Question 21
    { id: 'q21', text: '21', offset: 122 },
    { id: 0, text: 'Write an', offset: 125 },
    { id: 1, text: 'keep it brief.', offset: 134 },

    // Question 22
    { id: 'q22', text: '22', offset: 149 },
    { id: 2, text: 'List relevant', offset: 152 },

    // Question 23
    { id: 'q23', text: '23', offset: 166 },
    { id: 3, text: 'Have two academic advisors read over your', offset: 169 },

    // Question 24
    { id: 'q24', text: '24', offset: 210 },
    { id: 4, text: 'Choose the journal you want to submit to.', offset: 213 },
    { id: 5, text: "Apply the journal's", offset: 255 },
    { id: 6, text: 'to your article.', offset: 275 },

    // Question 25
    { id: 'q25', text: '25', offset: 292 },
    { id: 7, text: 'Sign the', offset: 295 },

    // Questions 26-30 Header
    { id: 'q26-30-header', text: 'Questions 26–30', offset: 320 },                                   // 320-335
    { id: 'q26-30-inst1', text: 'Complete the flow-chart below.', offset: 336 },                     // 336-366
    { id: 'q26-30-inst2', text: 'Write NO MORE THAN TWO WORDS for each answer.', offset: 367 },      // 367-414

    // Part Two - Process
    { id: 'part2-title', text: 'Part Two – Process', offset: 415 },                                  // 415-433

    // Flow-chart: Submit box
    { id: 8, text: 'Submit', offset: 434 },

    // Question 26
    { id: 'q26', text: '26', offset: 441 },
    { id: 9, text: '–', offset: 444 },

    // Check e-mail for box
    { id: 10, text: 'Check e-mail for', offset: 446 },

    // Question 27
    { id: 'q27', text: '27', offset: 463 },
    { id: 11, text: 'of submission', offset: 466 },

    // Question 28
    { id: 'q28', text: '28', offset: 480 },

    // Acceptance or box
    { id: 12, text: 'Acceptance or', offset: 510 },

    // Question 29
    { id: 'q29', text: '29', offset: 524 },

    // Conditional acceptance box
    { id: 13, text: 'Conditional acceptance of', offset: 540 },
    { id: 14, text: 'Revise & resubmit', offset: 566 },

    // Revise & send back box
    { id: 15, text: 'Revise &', offset: 590 },
    { id: 16, text: 'send back with a', offset: 599 },

    // Question 30
    { id: 'q30', text: '30', offset: 616 },
]);

// Initialize tooltip composable
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
    handleContentTextSelect,
    handleContentClick,
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

// Confirm delete highlight (called from tooltip)
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
        handleTooltipMouseLeave();
    }
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    // Handle grouped questions for 25-26, 27-28 and 29-30
    let targetId = `question-${questionNumber}`;
    if (questionNumber === 25 || questionNumber === 26) {
        targetId = 'question-25-26';
    } else if (questionNumber === 27 || questionNumber === 28) {
        targetId = 'question-27-28';
    } else if (questionNumber === 29 || questionNumber === 30) {
        targetId = 'question-29-30';
    }

    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

// Setup event listeners when component mounts
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <!-- Questions Section - Full Width -->
        <div class="flex w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
           <div class="mb-3 rounded border border-gray-200 part-header-box px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="section3-title"
                    v-html="getHighlightedSegmentById('section3-title')"
                ></h3>
                 <p class="text-base text-gray-900 text-sm select-text" data-segment-id="q21-25-header" v-html="getHighlightedSegmentById('q21-25-header')"></p>


            </div>

            <!-- Instructions Section Q21–25 -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
               <p class="text-sm text-gray-900 font-semibold select-text" data-segment-id="q21-25-inst2" v-html="getHighlightedSegmentById('q21-25-inst2')"></p>
            </div>

            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <div class="space-y-4 p-3 sm:p-4">

                        <!-- Part One – Checklist (Q21–25) -->
                        <div>
                            <h3
                                class="mb-3 text-lg font-bold text-gray-900 select-text"
                                data-segment-id="part1-title"
                                v-html="getHighlightedSegmentById('part1-title')"
                            ></h3>

                            <div class="space-y-3 text-base leading-relaxed text-gray-800">

                                <!-- Q21: Write an [input] keep it brief. -->
                                <div
                                    id="question-21"
                                    class="group relative flex flex-wrap items-center gap-0.5"
                                >
                                    <span class="text-gray-900 mr-1 select-text" data-segment-id="0" v-html="getHighlightedSegmentById(0)"></span>

                                    <span class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model="answers.q21"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="21"
                                        />
                                    </span>
                                    <span class="text-gray-800 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></span>
                                    <button
                                        @click.stop="toggleFlag(21)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q22: List relevant [input] -->
                                <div
                                    id="question-22"
                                    class="group relative flex flex-wrap items-center gap-0.5"
                                >
                                    <span class="text-gray-800 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></span>
                                    <span class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model="answers.q22"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="22"
                                        />
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(22)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q23: Have two academic advisors read over your [input] -->
                                <div
                                    id="question-23"
                                    class="group relative flex flex-wrap items-center gap-0.5"
                                >
                                    <span class="text-gray-800 select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></span>
                                    <span class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model="answers.q23"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="23"
                                        />
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(23)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Journal instruction (no question) -->
                                <div class=" text-gray-600 select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></div>

                                <!-- Q24: Apply the journal's [input] to your article. -->
                                <div
                                    id="question-24"
                                    class="group relative flex flex-wrap items-center gap-0.5"
                                >
                                    <span class="text-gray-800 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                                    <span class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model="answers.q24"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="24"
                                        />
                                    </span>
                                    <span class="text-gray-800 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                    <button
                                        @click.stop="toggleFlag(24)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q25: Sign the [input] -->
                                <div
                                    id="question-25"
                                    class="group relative flex flex-wrap items-center gap-0.5"
                                >
                                    <span class="text-gray-800 select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                    <span class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model="answers.q25"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="25"
                                        />
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(25)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 26–30: Flow-chart -->
                        <div class="border-t pt-4  ">

                            <!-- Instructions Q26–30 -->
                            <div class="mb-3  space-y-1" @mouseup="handleContentTextSelect">
                                <p
                                    class="text-base font-bold text-gray-900 select-text"
                                    data-segment-id="q26-30-header"
                                    v-html="getHighlightedSegmentById('q26-30-header')"
                                ></p>
                                <p
                                    class="text-sm text-gray-700 select-text"
                                    data-segment-id="q26-30-inst1"
                                    v-html="getHighlightedSegmentById('q26-30-inst1')"
                                ></p>
                                <p
                                    class="text-sm font-bold text-gray-700 select-text"
                                    data-segment-id="q26-30-inst2"
                                    v-html="getHighlightedSegmentById('q26-30-inst2')"
                                ></p>
                            </div>

                            <!-- Part Two heading -->
                            <h3
                                class="mb-3 text-lg font-bold text-gray-900 select-text"
                                data-segment-id="part2-title"
                                v-html="getHighlightedSegmentById('part2-title')"
                            ></h3>

                            <!-- Flow-chart steps -->
                            <div class="space-y-2 text-base w-full  leading-relaxed text-gray-800">

                                <!-- Q26: Submit [input] -->
                                <div
                                    id="question-26"
                                    class="group relative flex flex-wrap justify-center text-center items-center gap-0.5 rounded border border-gray-200 bg-gray-50 px-3 py-2"
                                >
                                    <span class="text-gray-800 flex flex-wrap justify-center select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                    <span class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model="answers.q26"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="26"
                                        />
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(26)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Flow arrow -->
                                <div class="flex justify-center text-gray-400 text-lg">↓</div>

                                <!-- Q27: Check e-mail for [input] of submission -->
                                <div
                                    id="question-27"
                                    class="group relative flex flex-wrap justify-center items-center gap-0.5 rounded border border-gray-200 bg-gray-50 px-3 py-2"
                                >
                                    <span class="text-gray-800 flex flex-wrap justify-center text-center select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                    <span class="inline-flex flex flex-wrap justify-center items-center gap-0.5">
                                        <input
                                            v-model="answers.q27"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="27"
                                        />
                                    </span>
                                    <span class="text-gray-800 flex flex-wrap justify-center text-center select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                    <button
                                        @click.stop="toggleFlag(27)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Flow arrow -->
                                <div class="flex justify-center text-gray-400 text-lg">↓</div>

                                <!-- Q28: Decision [input] -->
                                <div
                                    id="question-28"
                                    class="group relative flex flex-wrap justify-center items-center gap-0.5 rounded border border-gray-200 bg-gray-50 px-3 py-2"
                                >
                                    <span class="inline-flex flex flex-wrap justify-center items-center gap-0.5">
                                        <input
                                            v-model="answers.q28"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="28"
                                        />
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(28)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Flow arrow -->
                                <div class="flex justify-center text-gray-400 text-lg">↓</div>

                                <!-- Q29: Acceptance or [input] -->
                                <div
                                    id="question-29"
                                    class="group relative flex flex-wrap justify-center items-center gap-0.5 rounded border border-gray-200 bg-gray-50 px-3 py-2"
                                >
                                    <span class="text-gray-800 flex flex-wrap justify-center select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                    <span class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model="answers.q29"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="29"
                                        />
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(29)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Flow arrow -->
                                <div class="flex justify-center text-gray-400 text-lg">↓</div>

                                <!-- Conditional acceptance box (no question number) -->
                                <div class="rounded border flex flex-wrap justify-center border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 select-text">
                                    <span data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                    <span data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                </div>

                                <!-- Flow arrow -->
                                <div class="flex justify-center text-gray-400 text-lg">↓</div>

                                <!-- Q30: Revise & send back with a [input] -->
                                <div
                                    id="question-30"
                                    class="group relative flex flex-wrap justify-center items-center gap-0.5 rounded border border-gray-200 bg-gray-50 px-3 py-2"
                                >
                                    <span class=" text-gray-900  select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                    <span class="text-gray-800 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                    <span class="inline-flex items-center gap-0.5">
                                        <input
                                            v-model="answers.q30"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                            placeholder="30"
                                        />
                                    </span>
                                    <button
                                        @click.stop="toggleFlag(30)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                        :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                        :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

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
.part-header-box {
    background-color: #F1F2EC;
}
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
