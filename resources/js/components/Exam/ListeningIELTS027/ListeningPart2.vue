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
    q17: '',
    q18: '',
    q19: '',
    q20: '',
});

// Multiple choice answers for questions 17-20


// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Note[]>([]);

// Text segments with their cumulative offsets (each offset = previous offset + previous text length + 1)
const textSegments = ref([
  { id: 'part2-title', text: 'Part 2: Health Centre Open Day',          offset: 0    },
  { id: 0,  text: 'SECTION 2 — Questions 11–20',                        offset: 31   },
  { id: 1,  text: 'Questions 11–17',                                    offset: 59   },
  { id: 2,  text: 'Complete the notes below on the Sway Road Health Centre.', offset: 75 },
  { id: 3,  text: 'Write NO MORE THAN THREE WORDS AND/OR A NUMBER from the listening for each answer.', offset: 132 },
  { id: 4,  text: 'Sway Road Health Centre',                            offset: 215  },
  { id: 5,  text: 'Six full-time doctors',                              offset: 239  },
  { id: 6,  text: 'Just over',                                          offset: 261  },
  { id: 7,  text: 'patients',                                           offset: 271  },
  { id: 8,  text: '2 centres – Sway Road and Church Road',              offset: 280  },
  { id: 9,  text: 'Registering – come during opening hours; bring a',   offset: 318  },
  { id: 10, text: 'proof of address (dated within 3 months of application) and medical card', offset: 367 },
  { id: 11, text: 'or fill out a registration form.',                   offset: 440  },
  { id: 12, text: 'Staff will ask for your medical history and organise a', offset: 473 },
  { id: 13, text: 'to be taken.',                                       offset: 528  },
  { id: 14, text: 'Appointments – contact only by phone and not by',    offset: 541  },
  { id: 15, text: 'Call only during opening hours.',                    offset: 589  },
  { id: 16, text: 'Opening Hours',                                      offset: 621  },
  { id: 17, text: 'a.m. – 1 p.m. and 2 p.m. – 7 p.m. Monday to Friday', offset: 635 },
  { id: 18, text: '(call only between 1 p.m. & 2 p.m. in an emergency)', offset: 686 },
  { id: 19, text: '(closed weekends and',                               offset: 738  },
  { id: 20, text: 'The best option for emergencies is to go to hospital or call an ambulance.', offset: 759 },
  { id: 21, text: 'Medical Students – consultations with doctors or nurses may have a student present;', offset: 834 },
  { id: 22, text: 'there is no obligation to have a student present.',  offset: 918  },
  { id: 23, text: 'Travel Service – lots of vaccinations available.',   offset: 968  },
  { id: 24, text: 'Complete a',                                         offset: 1017 },
  { id: 25, text: 'at reception – available from reception or the website.', offset: 1028 },
  { id: 26, text: 'The nurse will make vaccination recommendations.',   offset: 1084 },
  { id: 27, text: 'Get a vaccination card.',                            offset: 1133 },
  { id: 28, text: 'Vaccinations to be paid for on the day of vaccination.', offset: 1157 },
  { id: 29, text: 'Questions 18–20',                                    offset: 1212 },
  { id: 30, text: 'Answer the questions below.',                        offset: 1228 },
  { id: 31, text: 'Use NO MORE THAN THREE WORDS from the listening for each answer.', offset: 1256 },
  { id: 32, text: 'Where is the suggestion box found at the health centre?', offset: 1321 },
  { id: 33, text: 'Who will deal with any complaints made?',            offset: 1377 },
  { id: 34, text: 'What area is closed off during the open morning?',   offset: 1417 },
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

    return allAnswers;
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
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="mb-3 rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"
                ></p>
            </div>

            <!-- Instructions Block: Q11-17 -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                <p class="text-sm text-gray-700 select-text mt-1">
                    <span data-segment-id="3" v-html="getHighlightedSegmentById(3)"></span>
                </p>
            </div>

            <!-- Scrollable Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <div class="p-3 select-text sm:p-4 space-y-4">

                        <!-- Section Title: Sway Road Health Centre -->
                        <h3 class="text-lg font-bold text-gray-900 border-b pb-1"
                            data-segment-id="4"
                            v-html="getHighlightedSegmentById(4)"
                        ></h3>

                        <!-- Six full-time doctors -->
                        <div class="text-base text-gray-800 leading-relaxed space-y-3">

                            <p data-segment-id="5" v-html="getHighlightedSegmentById(5)" class="select-text"></p>

                            <!-- Just over Q11 patients -->
                            <div class="flex flex-wrap items-center gap-0.5">
                                <span data-segment-id="6" v-html="getHighlightedSegmentById(6)" class="text-gray-800"></span>
                                <span
                                    id="question-11"
                                    class="group relative inline-flex items-center gap-0.5"
                                    @mouseenter="hoveredQuestion = 11"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <input
                                        v-model="answers.q11"
                                        type="text"
                                        spellcheck="false"
                                        autocomplete="off"
                                        class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                        placeholder="11"
                                        @focus="hoveredQuestion = 11"
                                        @blur="hoveredQuestion = null"
                                    />
                                    <button
                                        v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                        @click.stop="toggleFlag(11)"
                                        class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </span>
                                <span data-segment-id="7" v-html="getHighlightedSegmentById(7)" class="text-gray-800"></span>
                            </div>

                            <!-- 2 centres -->
                            <p data-segment-id="8" v-html="getHighlightedSegmentById(8)" class="select-text text-gray-800"></p>

                            <!-- Registering – bring a Q12, proof of address... organise a Q13 to be taken -->
                            <div class="leading-relaxed">
                                <div class="flex flex-wrap items-center gap-0.5">
                                    <span data-segment-id="9" v-html="getHighlightedSegmentById(9)" class="text-gray-800"></span>
                                    <span
                                        id="question-12"
                                        class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 12"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q12"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="12"
                                            @focus="hoveredQuestion = 12"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                    <span data-segment-id="10" v-html="getHighlightedSegmentById(10)" class="text-gray-800"></span>
                                    <span data-segment-id="11" v-html="getHighlightedSegmentById(11)" class="text-gray-800"></span>
                                </div>
                                <div class="flex flex-wrap items-center gap-0.5 mt-1">
                                    <span data-segment-id="12" v-html="getHighlightedSegmentById(12)" class="text-gray-800"></span>
                                    <span
                                        id="question-13"
                                        class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 13"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q13"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="13"
                                            @focus="hoveredQuestion = 13"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                    <span data-segment-id="13" v-html="getHighlightedSegmentById(13)" class="text-gray-800"></span>
                                </div>
                            </div>

                            <!-- Appointments – not by Q14 -->
                            <div class="flex flex-wrap items-center gap-0.5">
                                <span data-segment-id="14" v-html="getHighlightedSegmentById(14)" class="text-gray-800"></span>
                                <span
                                    id="question-14"
                                    class="group relative inline-flex items-center gap-0.5"
                                    @mouseenter="hoveredQuestion = 14"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <input
                                        v-model="answers.q14"
                                        type="text"
                                        spellcheck="false"
                                        autocomplete="off"
                                        class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                        placeholder="14"
                                        @focus="hoveredQuestion = 14"
                                        @blur="hoveredQuestion = null"
                                    />
                                    <button
                                        v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                        @click.stop="toggleFlag(14)"
                                        class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </span>
                            </div>
                            <p data-segment-id="15" v-html="getHighlightedSegmentById(15)" class="select-text text-gray-800"></p>

                            <!-- Opening Hours -->
                            <div class="mt-2">
                                <p class="font-bold text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></p>
                                <div class="flex flex-wrap items-center gap-0.5 mt-1">
                                    <span
                                        id="question-15"
                                        class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 15"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q15"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="15"
                                            @focus="hoveredQuestion = 15"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="toggleFlag(15)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                    <span data-segment-id="17" v-html="getHighlightedSegmentById(17)" class="text-gray-800"></span>
                                </div>
                                <p class="text-sm text-gray-600 mt-0.5 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></p>
                                <div class="flex flex-wrap items-center gap-0.5 mt-1">
                                    <span data-segment-id="19" v-html="getHighlightedSegmentById(19)" class="text-gray-800"></span>
                                    <span
                                        id="question-16"
                                        class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 16"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q16"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="16"
                                            @focus="hoveredQuestion = 16"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="toggleFlag(16)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                    <span class="text-gray-800">)</span>
                                </div>
                            </div>

                            <!-- Emergency info -->
                            <p data-segment-id="20" v-html="getHighlightedSegmentById(20)" class="select-text text-gray-800"></p>

                            <!-- Divider -->
                            <hr class="border-gray-200 my-2" />

                            <!-- Medical Students -->
                            <div>
                                <span data-segment-id="21" v-html="getHighlightedSegmentById(21)" class="text-gray-800 select-text"></span>
                                <span data-segment-id="22" v-html="getHighlightedSegmentById(22)" class="text-gray-800 select-text"></span>
                            </div>

                            <!-- Travel Service – Complete a Q17 at reception -->
                            <div class="leading-relaxed">
                                <div class="flex flex-wrap items-center gap-0.5">
                                    <span data-segment-id="23" v-html="getHighlightedSegmentById(23)" class="text-gray-800"></span>
                                    <span data-segment-id="24" v-html="getHighlightedSegmentById(24)" class="text-gray-800"></span>
                                    <span
                                        id="question-17"
                                        class="group relative inline-flex items-center gap-0.5"
                                        @mouseenter="hoveredQuestion = 17"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q17"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="17"
                                            @focus="hoveredQuestion = 17"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="toggleFlag(17)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                    <span data-segment-id="25" v-html="getHighlightedSegmentById(25)" class="text-gray-800"></span>
                                </div>
                                <p class="mt-1 text-gray-800 select-text" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></p>
                                <p class="text-gray-800 select-text" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></p>
                                <p class="text-gray-800 select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></p>
                            </div>
                        </div>

                        <!-- Divider before Q18-20 -->
                        <hr class="border-gray-300 my-4" />

                        <!-- Instructions Block: Q18-20 -->
                        <div class="space-y-1">
                            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></p>
                        </div>

                        <!-- Q18 -->
                        <div
                            id="question-18"
                            class="relative"
                            @mouseenter="hoveredQuestion = 18"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex flex-wrap items-start gap-2">
                                <span class="font-bold text-gray-900 shrink-0">18</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-base text-gray-800 select-text mb-1" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></p>
                                    <div class="flex items-center gap-1">
                                        <input
                                            v-model="answers.q18"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input min-w-[120px] w-full max-w-xs border border-gray-900 px-1.5 py-0.5 text-sm focus:border-black focus:outline-none"
                                            placeholder="Answer 18"
                                            @focus="hoveredQuestion = 18"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                            @click.stop="toggleFlag(18)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Q19 -->
                        <div
                            id="question-19"
                            class="relative"
                            @mouseenter="hoveredQuestion = 19"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex flex-wrap items-start gap-2">
                                <span class="font-bold text-gray-900 shrink-0">19</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-base text-gray-800 select-text mb-1" data-segment-id="33" v-html="getHighlightedSegmentById(33)"></p>
                                    <div class="flex items-center gap-1">
                                        <input
                                            v-model="answers.q19"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input min-w-[120px] w-full max-w-xs border border-gray-900 px-1.5 py-0.5 text-sm focus:border-black focus:outline-none"
                                            placeholder="Answer 19"
                                            @focus="hoveredQuestion = 19"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                            @click.stop="toggleFlag(19)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Q20 -->
                        <div
                            id="question-20"
                            class="relative"
                            @mouseenter="hoveredQuestion = 20"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex flex-wrap items-start gap-2">
                                <span class="font-bold text-gray-900 shrink-0">20</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-base text-gray-800 select-text mb-1" data-segment-id="34" v-html="getHighlightedSegmentById(34)"></p>
                                    <div class="flex items-center gap-1">
                                        <input
                                            v-model="answers.q20"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="question-input min-w-[120px] w-full max-w-xs border border-gray-900 px-1.5 py-0.5 text-sm focus:border-black focus:outline-none"
                                            placeholder="Answer 20"
                                            @focus="hoveredQuestion = 20"
                                            @blur="hoveredQuestion = null"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                            @click.stop="toggleFlag(20)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
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
