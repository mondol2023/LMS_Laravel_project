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

// Listening Part 3 component

// Single choice answers for questions 21-24
const answers = ref<Record<string, string>>({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
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
const heading = ref([
    { id: 1, text: 'The roof collects rainwater for use in the air-conditioning.', offset: 1435 }, // A. The roof collects rainwater for use in the air-conditioning.
    { id: 2, text: 'B. The position of the rooms keeps them naturally cool.', offset: 1485 },
    { id: 3, text: 'C. The building produces more energy than it consumes.', offset: 1540 },
    { id: 4, text: 'D. The design of the building maximizes natural light.', offset: 1600 },
    { id: 5, text: 'E. The residents are encouraged to check their energy use.', offset: 1660 },
    { id: 6, text: 'F. The building is intended to last for a long time.', offset: 1720 },
    { id: 7, text: 'G. The outside of the building supports wildlife.', offset: 1780 },
    { id: 8, text: 'H. The materials used in construction were all recycled.', offset: 1840 },
]);

// Drag and drop state
const draggedLetter = ref<string | null>(null);
const dropTarget = ref<string | null>(null);

// Computed: which letters are currently used in q25–q30
const usedAnswers = computed(() => {
    const used = new Set<string>();
    ['q25', 'q26', 'q27', 'q28', 'q29', 'q30'].forEach((q) => {
        const val = answers.value[q];
        if (val) used.add(val);
    });
    return used;
});

const onDragStart = (letter: string) => {
    draggedLetter.value = letter;
};

const onDrop = (questionKey: string) => {
    if (!draggedLetter.value) return;

    const incoming = draggedLetter.value;
    const existing = answers.value[questionKey];

    // If this slot already has an answer, swap: put old answer back (it becomes available again)
    // Since usedAnswers is computed, just overwrite — the old letter auto-returns to the pool
    answers.value[questionKey] = incoming;

    // If the incoming letter was already placed elsewhere, clear that slot
    ['q25', 'q26', 'q27', 'q28', 'q29', 'q30'].forEach((q) => {
        if (q !== questionKey && answers.value[q] === incoming) {
            answers.value[q] = '';
        }
    });

    draggedLetter.value = null;
    dropTarget.value = null;
};

const clearAnswer = (questionKey: string) => {
    answers.value[questionKey] = '';
};

// Text segments with their cumulative offsets
// Each offset = previous offset + previous text length + gap (5-10 chars)
const textSegments = ref<TextSegment[]>([
  // Part header
  { id: 'part3-title', text: 'Part 3', offset: 0 },                                            // len=6, ends=6
  { id: 'part3-desc', text: 'Listen and answer questions 21–30.', offset: 15 },                // len=35, ends=50
  // Instruction section
  { id: 0, text: 'Questions 21–24', offset: 60 },                                              // len=15, ends=75
  { id: 1, text: 'Choose the correct letter, A, B, or C.', offset: 85 },                       // len=39, ends=124
  { id: 2, text: 'Sustainable building', offset: 135 },                                        // len=20, ends=155
  // Question 21
  { id: 'q21', text: '21.', offset: 165 },                                                     // len=3, ends=168
  { id: 3, text: 'Before starting their project, both Yasmeen and Ben wanted to learn more about', offset: 180 }, // len=78, ends=258
  { id: 4, text: 'A. integration of buildings into the landscape.', offset: 270 },             // len=48, ends=318
  { id: 5, text: 'B. green heating and cooling systems.', offset: 330 },                       // len=38, ends=368
  { id: 6, text: 'C. alternative construction materials.', offset: 380 },                      // len=39, ends=419
  // Question 22
  { id: 'q22', text: '22.', offset: 430 },                                                     // len=3, ends=433
  { id: 7, text: 'According to Yasmeen, the first Earth Day in 1970', offset: 445 },           // len=49, ends=494
  { id: 8, text: 'A. raised public awareness around the world.', offset: 505 },                // len=44, ends=549
  { id: 9, text: 'B. led to new environmental legislation.', offset: 560 },                    // len=40, ends=600
  { id: 10, text: 'C. inspired a best-selling book on pollution.', offset: 615 },              // len=45, ends=660
  // Question 23
  { id: 'q23', text: '23.', offset: 675 },                                                     // len=3, ends=678
  { id: 11, text: 'How do Yasmeen and Ben feel about a power plant in Denmark?', offset: 690 }, // len=59, ends=749
  { id: 12, text: 'A. impressed by the varied uses of the building', offset: 760 },            // len=47, ends=807
  { id: 13, text: 'B. confused about the reasons for the design', offset: 820 },               // len=44, ends=864
  { id: 14, text: 'C. disappointed by the appearance of the building', offset: 875 },          // len=49, ends=924
  // Question 24
  { id: 'q24', text: '24.', offset: 935 },                                                     // len=3, ends=938
  { id: 15, text: 'Yasmeen and Ben agree that an architecture firm they researched', offset: 950 }, // len=63, ends=1013
  { id: 16, text: 'A. has influenced the designs of sustainable buildings.', offset: 1025 },   // len=55, ends=1080
  { id: 17, text: 'B. has exaggerated its claims of sustainable practices.', offset: 1095 },   // len=55, ends=1150
  { id: 18, text: 'C. has benefited from its reputation as a sustainable company.', offset: 1165 }, // len=62, ends=1227
  // Questions 25–30 instruction
  { id: 'q25-30', text: 'Questions 25–30', offset: 1240 },                                     // len=15, ends=1255
  { id: 'q25-30-inst', text: 'What did each of the following studies find?', offset: 1270 },   // len=45, ends=1315
  { id: 19, text: 'Choose SIX answers from the box and drag the letter, A–H, next to Questions 25–30.', offset: 1330 }, // len=92, ends=1422
  // Box title
  { id: 20, text: 'Sustainable features', offset: 1435 },                                      // len=20, ends=1455
  // Options A–H
  { id: 21, text: 'A. The roof collects rainwater for use in the air-conditioning.', offset: 1470 }, // len=64, ends=1534
  { id: 22, text: 'B. The position of the rooms keeps them naturally cool.', offset: 1550 },   // len=55, ends=1605
  { id: 23, text: 'C. The building produces more energy than it consumes.', offset: 1620 },    // len=54, ends=1674
  { id: 24, text: 'D. The design of the building maximizes natural light.', offset: 1690 },    // len=54, ends=1744
  { id: 25, text: 'E. The residents are encouraged to check their energy use.', offset: 1760 }, // len=58, ends=1818
  { id: 26, text: 'F. The building is intended to last for a long time.', offset: 1835 },      // len=52, ends=1887
  { id: 27, text: 'G. The outside of the building supports wildlife.', offset: 1900 },         // len=49, ends=1949
  { id: 28, text: 'H. The materials used in construction were all recycled.', offset: 1965 },  // len=56, ends=2021
  // Buildings section
  { id: 29, text: 'Buildings', offset: 2035 },                                                 // len=9, ends=2044
  { id: 'q25', text: '25.', offset: 2055 },                                                    // len=3, ends=2058
  { id: 30, text: 'The Peace Building', offset: 2070 },                                        // len=18, ends=2088
  { id: 'q26', text: '26.', offset: 2100 },                                                    // len=3, ends=2103
  { id: 31, text: 'Ingel Towers', offset: 2115 },                                              // len=12, ends=2127
  { id: 'q27', text: '27.', offset: 2140 },                                                    // len=3, ends=2143
  { id: 32, text: 'The WNT Block', offset: 2155 },                                             // len=13, ends=2168
  { id: 'q28', text: '28.', offset: 2180 },                                                    // len=3, ends=2183
  { id: 33, text: 'The Polo', offset: 2195 },                                                  // len=8, ends=2203
  { id: 'q29', text: '29.', offset: 2215 },                                                    // len=3, ends=2218
  { id: 34, text: 'Simpson Galleries', offset: 2230 },                                         // len=17, ends=2247
  { id: 'q30', text: '30.', offset: 2260 },                                                    // len=3, ends=2263
  { id: 35, text: 'One Pebble Square', offset: 2275 },                                         // len=17, ends=2292
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

const isSelected = (questionGroup: string, option: string) => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
};

const isDisabled = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    // Disable if 2 already selected AND this option is not one of them
    return currentAnswers.length >= 2 && !currentAnswers.includes(option);
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
    const element = document.getElementById(`question-${questionNumber}`);
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
                    data-segment-id="part3-title"
                    v-html="getHighlightedSegmentById('part3-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part3-desc"
                    v-html="getHighlightedSegmentById('part3-desc')"
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
        class="text-lg font-bold py-2 text-gray-900 select-text"
        data-segment-id="2"
        v-html="getHighlightedSegmentById(2)"
    ></h2>
</div>

<!-- Scrollable Questions Content -->
<div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
    <div class="p-2 sm:p-3">
        <div class="space-y-2">

            <!-- Question 21 -->
            <div
                id="question-21"
                class="relative p-2 sm:p-3"
                @mouseenter="hoveredQuestion = 21"
                @mouseleave="hoveredQuestion = null"
            >
                <div class="flex items-start gap-2">
                    <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q21" v-html="getHighlightedSegmentById('q21')"></span>
                    <div class="min-w-0 flex-1">
                        <button
                            v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                            @click.stop="toggleFlag(21)"
                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                        </button>
                        <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                            <span class="select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></span>
                        </h4>
                        <div class="space-y-1">
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q21" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q21" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q21" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question 22 -->
            <div
                id="question-22"
                class="relative p-2 sm:p-3"
                @mouseenter="hoveredQuestion = 22"
                @mouseleave="hoveredQuestion = null"
            >
                <div class="flex items-start gap-2">
                    <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q22" v-html="getHighlightedSegmentById('q22')"></span>
                    <div class="min-w-0 flex-1">
                        <button
                            v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                            @click.stop="toggleFlag(22)"
                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                            :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                        </button>
                        <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                            <span class="select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                        </h4>
                        <div class="space-y-1">
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q22" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q22" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q22" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question 23 -->
            <div
                id="question-23"
                class="relative p-2 sm:p-3"
                @mouseenter="hoveredQuestion = 23"
                @mouseleave="hoveredQuestion = null"
            >
                <div class="flex items-start gap-2">
                    <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q23" v-html="getHighlightedSegmentById('q23')"></span>
                    <div class="min-w-0 flex-1">
                        <button
                            v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                            @click.stop="toggleFlag(23)"
                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                            :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                        </button>
                        <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                            <span class="select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                        </h4>
                        <div class="space-y-1">
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q23" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q23" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q23" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question 24 -->
            <div
                id="question-24"
                class="relative p-2 sm:p-3"
                @mouseenter="hoveredQuestion = 24"
                @mouseleave="hoveredQuestion = null"
            >
                <div class="flex items-start gap-2">
                    <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q24" v-html="getHighlightedSegmentById('q24')"></span>
                    <div class="min-w-0 flex-1">
                        <button
                            v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                            @click.stop="toggleFlag(24)"
                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                            :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                        </button>
                        <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                            <span class="select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                        </h4>
                        <div class="space-y-1">
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q24" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q24" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="radio" v-model="answers.q24" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions 25–30: Matching (dropdown A–H per building) -->
            <!-- Options box A–H (draggable chips) -->
            <!-- Options box A–H (draggable full rows) -->
             <div>
        <h3
            class="text-base font-bold text-gray-900 select-text"
            data-segment-id="q25-30"
            v-html="getHighlightedSegmentById('q25-30')"
        ></h3>

        <p class="mb-3 text-base font-bold text-gray-900 select-text" data-segment-id="q25-30-inst" v-html="getHighlightedSegmentById('q25-30-inst')"></p>
        <p class="mb-2 text-base text-gray-900 select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></p>
    </div>
            <div class="mb-4 rounded border border-gray-200 bg-gray-50 p-3">

                <p class="mb-2 text-sm font-bold text-gray-900 select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></p>
                <div class="space-y-1 text-sm text-gray-800">
                    <template v-for="(segId, idx) in [21,22,23,24,25,26,27,28]" :key="segId">
                        <div
                            v-if="!usedAnswers.has('ABCDEFGH'[idx])"
                            draggable="true"
                            @dragstart="onDragStart('ABCDEFGH'[idx])"
                            @dragend="draggedLetter = null"
                            class="select-text cursor-grab active:cursor-grabbing rounded px-1 py-0.5 transition-colors hover:bg-blue-50 hover:text-blue-700 border border-transparent hover:border-blue-200"
                            :class="draggedLetter === 'ABCDEFGH'[idx] ? 'opacity-50 bg-blue-50' : ''"
                            :data-segment-id="segId"
                            v-html="getHighlightedSegmentById(segId)"
                        ></div>
                        <div
                            v-else
                            class="select-text rounded px-1 py-0.5 text-gray-300 line-through"
                            :data-segment-id="segId"
                            v-html="getHighlightedSegmentById(segId)"
                        ></div>
                    </template>
                </div>


    <!-- Draggable letter chips -->

</div>

<!-- Buildings section header -->
<p class="mb-3 text-base font-bold text-gray-900 select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></p>

<!-- Matching rows Q25–Q30 -->
<div class="space-y-3">


    <!-- Q25 -->
    <div
        id="question-25"
        class="flex flex-wrap items-center gap-2 "
        @mouseenter="hoveredQuestion = 25"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="font-bold text-gray-900 select-text w-8 shrink-0" data-segment-id="q25" v-html="getHighlightedSegmentById('q25')"></span>
        <span class="flex-1 text-sm text-gray-900 select-text" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></span>

        <!-- Drop zone -->
        <div
            class="flex h-8 w-69 mr-20 items-center justify-center rounded border-dashed border-2 text-sm font-bold select-none transition-colors"
            :class="dropTarget === 'q25'
                ? 'border-blue-400 bg-blue-50 text-blue-600'
                : answers.q25
                    ? 'border-gray-800 bg-white text-gray-900 cursor-pointer hover:border-red-400 hover:text-red-400'
                    : 'border-dashed border-gray-300 bg-white text-gray-300'"
            @dragover.prevent="dropTarget = 'q25'"
            @dragleave="dropTarget = null"
            @drop.prevent="onDrop('q25')"
            @click="answers.q25 && clearAnswer('q25')"
            :title="answers.q25 ? 'Click to remove' : 'Drop answer here'"
        >
            {{ answers.q25 || '–' }}
        </div>

        <button
            v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
            @click.stop="toggleFlag(25)"
            class="absolute right-2 ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
        </button>
    </div>

    <!-- Q26 -->
    <div
        id="question-26"
        class="flex flex-wrap items-center gap-2"
        @mouseenter="hoveredQuestion = 26"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="font-bold text-gray-900 select-text w-8 shrink-0" data-segment-id="q26" v-html="getHighlightedSegmentById('q26')"></span>
        <span class="flex-1 text-sm text-gray-900 select-text" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></span>
        <div
            class="flex h-8 w-70 mr-20 items-center justify-center rounded border-dashed border-2 text-sm font-bold select-none transition-colors"
            :class="dropTarget === 'q26'
                ? 'border-blue-400 bg-blue-50 text-blue-600'
                : answers.q26
                    ? 'border-gray-800 bg-white text-gray-900 cursor-pointer hover:border-grey-400 '
                    : 'border-dashed border-gray-300 bg-white text-gray-300'"
            @dragover.prevent="dropTarget = 'q26'"
            @dragleave="dropTarget = null"
            @drop.prevent="onDrop('q26')"
            @click="answers.q26 && clearAnswer('q26')"
            :title="answers.q26 ? 'Click to remove' : 'Drop answer here'"
        >
            {{ answers.q26 || '–' }}
        </div>
        <button
            v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
            @click.stop="toggleFlag(26)"
            class="absolute right-2 ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
        </button>
    </div>

    <!-- Q27 -->
    <div
        id="question-27"
        class="flex flex-wrap items-center gap-2"
        @mouseenter="hoveredQuestion = 27"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="font-bold text-gray-900 select-text w-8 shrink-0" data-segment-id="q27" v-html="getHighlightedSegmentById('q27')"></span>
        <span class="flex-1 text-sm text-gray-900 select-text" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></span>
        <div
            class="flex h-8 w-70 mr-20 items-center justify-center rounded border-dashed border-2 text-sm font-bold select-none transition-colors"
            :class="dropTarget === 'q27'
                ? 'border-blue-400 bg-blue-50 text-blue-600'
                : answers.q27
                    ? 'border-gray-800 bg-white text-gray-900 cursor-pointer hover:border-red-400 hover:text-red-400'
                    : 'border-dashed border-gray-300 bg-white text-gray-300'"
            @dragover.prevent="dropTarget = 'q27'"
            @dragleave="dropTarget = null"
            @drop.prevent="onDrop('q27')"
            @click="answers.q27 && clearAnswer('q27')"
            :title="answers.q27 ? 'Click to remove' : 'Drop answer here'"
        >
            {{ answers.q27 || '–' }}
        </div>
        <button
            v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
            @click.stop="toggleFlag(27)"
            class="absolute right-2 ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
        </button>
    </div>

    <!-- Q28 -->
    <div
        id="question-28"
        class="flex flex-wrap items-center gap-2"
        @mouseenter="hoveredQuestion = 28"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="font-bold text-gray-900 select-text w-8 shrink-0" data-segment-id="q28" v-html="getHighlightedSegmentById('q28')"></span>
        <span class="flex-1 text-sm text-gray-900 select-text" data-segment-id="33" v-html="getHighlightedSegmentById(33)"></span>
        <div
            class="flex h-8 w-70 mr-20 items-center justify-center rounded border-dashed border-2 text-sm font-bold select-none transition-colors"
            :class="dropTarget === 'q28'
                ? 'border-blue-400 bg-blue-50 text-blue-600'
                : answers.q28
                    ? 'border-gray-800 bg-white text-gray-900 cursor-pointer hover:border-red-400 hover:text-red-400'
                    : 'border-dashed border-gray-300 bg-white text-gray-300'"
            @dragover.prevent="dropTarget = 'q28'"
            @dragleave="dropTarget = null"
            @drop.prevent="onDrop('q28')"
            @click="answers.q28 && clearAnswer('q28')"
            :title="answers.q28 ? 'Click to remove' : 'Drop answer here'"
        >
            {{ answers.q28 || '–' }}
        </div>
        <button
            v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
            @click.stop="toggleFlag(28)"
            class=" absolute right-2 ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
        </button>
    </div>

    <!-- Q29 -->
    <div
        id="question-29"
        class="flex flex-wrap items-center gap-2"
        @mouseenter="hoveredQuestion = 29"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="font-bold text-gray-900 select-text w-8 shrink-0" data-segment-id="q29" v-html="getHighlightedSegmentById('q29')"></span>
        <span class="flex-1 text-sm text-gray-900 select-text" data-segment-id="34" v-html="getHighlightedSegmentById(34)"></span>
        <div
            class="flex h-8 w-70 mr-20 items-center justify-center rounded border-dashed border-2 text-sm font-bold select-none transition-colors"
            :class="dropTarget === 'q29'
                ? 'border-blue-400 bg-blue-50 text-blue-600'
                : answers.q29
                    ? 'border-gray-800 bg-white text-gray-900 cursor-pointer hover:border-red-400 hover:text-red-400'
                    : 'border-dashed border-gray-300 bg-white text-gray-300'"
            @dragover.prevent="dropTarget = 'q29'"
            @dragleave="dropTarget = null"
            @drop.prevent="onDrop('q29')"
            @click="answers.q29 && clearAnswer('q29')"
            :title="answers.q29 ? 'Click to remove' : 'Drop answer here'"
        >
            {{ answers.q29 || '–' }}
        </div>
        <button
            v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
            @click.stop="toggleFlag(29)"
            class="absolute right-2 ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
        </button>
    </div>

    <!-- Q30 -->
    <div
        id="question-30"
        class="flex flex-wrap items-center gap-2"
        @mouseenter="hoveredQuestion = 30"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="font-bold text-gray-900 select-text w-8 shrink-0" data-segment-id="q30" v-html="getHighlightedSegmentById('q30')"></span>
        <span class="flex-1 text-sm text-gray-900 select-text" data-segment-id="35" v-html="getHighlightedSegmentById(35)"></span>
        <div
            class="flex h-8 w-70 mr-20 items-center justify-center rounded border-dashed border-2 text-sm font-bold select-none transition-colors"
            :class="dropTarget === 'q30'
                ? 'border-blue-400 bg-blue-50 text-blue-600'
                : answers.q30
                    ? 'border-gray-800 bg-white text-gray-900 cursor-pointer hover:border-red-400 hover:text-red-400'
                    : 'border-dashed border-gray-300 bg-white text-gray-300'"
            @dragover.prevent="dropTarget = 'q30'"
            @dragleave="dropTarget = null"
            @drop.prevent="onDrop('q30')"
            @click="answers.q30 && clearAnswer('q30')"
            :title="answers.q30 ? 'Click to remove' : 'Drop answer here'"
        >
            {{ answers.q30 || '–' }}
        </div>
        <button
            v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
            @click.stop="toggleFlag(30)"
            class="absolute right-2 ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
        </button>
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
