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


// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Note[]>([]);
// Listening Part 3 component

// Single choice answers for questions 21-24
const answers = ref<Record<string, string>>({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

// Multiple choice answers for questions 25-30
const multipleAnswers = ref<{  q27_28: string[]; q29_30: string[] }>({
    
    q27_28: [],
    q29_30: [],
});



// Text segments with their cumulative offsets (each offset = previous offset + previous text length + 1)
const textSegments = ref([
    { id: 'part3-title', text: 'Part 3: Picture books',                   offset: 0    },
    { id: 'part3-desc', text: 'Listen and answer questions 21–30.',        offset: 22   },

    { id: 0,  text: 'PART 3 — Questions 21–30',                           offset: 57   },
    { id: 1,  text: 'Questions 21–26',                                     offset: 82   },
    { id: 2,  text: 'What reason do teachers give for using each of the following picture books with children?', offset: 98 },
    { id: 3,  text: 'Choose SIX answers from the box and write the correct letter, A–I, next to questions 21–26.', offset: 188 },

    { id: 4,  text: 'Reasons',                                             offset: 280  },
    { id: 5,  text: 'The pictures show changing times of day.',             offset: 288  },
    { id: 6,  text: 'The words are fun to say out loud.',                   offset: 329  },
    { id: 7,  text: 'It has an uncommon plot structure.',                   offset: 364  },
    { id: 8,  text: 'The text can easily be turned into song.',             offset: 399  },
    { id: 9,  text: 'The pictures were done by a famous illustrator.',      offset: 440  },
    { id: 10, text: 'There is a surprise at the end of the story.',         offset: 488  },
    { id: 11, text: 'The pictures are simple line drawings.',               offset: 533  },
    { id: 12, text: 'It was published very recently.',                      offset: 572  },
    { id: 13, text: 'It features well-known characters.',                   offset: 604  },

    { id: 14, text: 'Picture books',                                        offset: 639  },
    { id: 15, text: 'Spy Friends',                                          offset: 653  },
    { id: 16, text: 'Goodnight Little Rabbit',                              offset: 665  },
    { id: 17, text: 'The Boat Party',                                       offset: 689  },
    { id: 18, text: 'Puppy and Friends',                                    offset: 704  },
    { id: 19, text: 'Hats and Socks',                                       offset: 722  },
    { id: 20, text: 'A Family Adventure',                                   offset: 737  },

    { id: 21, text: 'Questions 27 and 28',                                  offset: 756  },
    { id: 22, text: 'Choose TWO letters, A–E.',                             offset: 776  },
    { id: 23, text: 'Which TWO points from Smith\'s article about picture books did both speakers find surprising?', offset: 801 },
    { id: 24, text: 'their standard length',                                offset: 894  },
    { id: 25, text: 'how recent the genre is',                              offset: 916  },
    { id: 26, text: 'the argument that they are not literature',            offset: 940  },
    { id: 27, text: 'the lack of research about this age group',            offset: 982  },
    { id: 28, text: 'the popularity of books from overseas',                offset: 1024 },

    { id: 29, text: 'Questions 29 and 30',                                  offset: 1062 },
    { id: 30, text: 'Choose TWO letters, A–E.',                             offset: 1082 },
    { id: 31, text: 'Which TWO aspects of picture books do the speakers agree to talk about in their presentation?', offset: 1107 },
    { id: 32, text: 'the material they are made from',                      offset: 1201 },
    { id: 33, text: 'common characters in them',                            offset: 1233 },
    { id: 34, text: 'the variety of roles in them',                         offset: 1259 },
    { id: 35, text: 'cultural elements in them',                            offset: 1288 },
    { id: 36, text: 'the future of the genre',                              offset: 1314 },
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

    // Handle multiple choice questions
    if (multipleAnswers.value.q27_28?.length > 0) {
        allAnswers.q27 = multipleAnswers.value.q27_28[0] || '';
        allAnswers.q28 = multipleAnswers.value.q27_28[1] || '';
    } else {
        allAnswers.q27 = '';
        allAnswers.q28 = '';
    }

    if (multipleAnswers.value.q29_30?.length > 0) {
        allAnswers.q29 = multipleAnswers.value.q29_30[0] || '';
        allAnswers.q30 = multipleAnswers.value.q29_30[1] || '';
    } else {
        allAnswers.q29 = '';
        allAnswers.q30 = '';
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
   
    if (questionNumber === 27 || questionNumber === 28) {
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

// ── Drag-and-drop state (add to your existing script setup) ──────────────────

const draggedLetter = ref(null);
const dragOverTarget = ref(null);

const reasons = [
    { letter: 'A', segmentId: 5 },
    { letter: 'B', segmentId: 6 },
    { letter: 'C', segmentId: 7 },
    { letter: 'D', segmentId: 8 },
    { letter: 'E', segmentId: 9 },
    { letter: 'F', segmentId: 10 },
    { letter: 'G', segmentId: 11 },
    { letter: 'H', segmentId: 12 },
    { letter: 'I', segmentId: 13 },
];

const pictureBooks = [
    { qNum: 21, segmentId: 15 },
    { qNum: 22, segmentId: 16 },
    { qNum: 23, segmentId: 17 },
    { qNum: 24, segmentId: 18 },
    { qNum: 25, segmentId: 19 },
    { qNum: 26, segmentId: 20 },
];

const q2728Options = [
    { value: 'A' },
    { value: 'B' },
    { value: 'C' },
    { value: 'D' },
    { value: 'E' },
];

const q2930Options = [
    { value: 'A' },
    { value: 'B' },
    { value: 'C' },
    { value: 'D' },
    { value: 'E' },
];

// Returns true if a reason letter is already assigned to any book
function isReasonUsed(letter) {
    return pictureBooks.some(b => answers[`q${b.qNum}`] === letter);
}

function onDragStart(event, letter) {
    draggedLetter.value = letter;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', letter);
}

function onDragEnd() {
    draggedLetter.value = null;
    dragOverTarget.value = null;
}

function onDragOver(qNum) {
    dragOverTarget.value = qNum;
}

function onDragLeave() {
    dragOverTarget.value = null;
}

function onDrop(qNum) {
    if (!draggedLetter.value) return;
    // If the letter was previously on another book, clear it there first
    pictureBooks.forEach(b => {
        if (answers[`q${b.qNum}`] === draggedLetter.value) {
            answers[`q${b.qNum}`] = null;
        }
    });
    answers[`q${qNum}`] = draggedLetter.value;
    draggedLetter.value = null;
    dragOverTarget.value = null;
}

function clearBookAnswer(qNum) {
    answers[`q${qNum}`] = null;
}

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
        <div class="flex w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="mb-3 rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
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

            <!-- Section Header -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="0" v-html="getHighlightedSegmentById(0)"></p>
            </div>

            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3 space-y-6">

                    <!-- ─── Q21–26: Drag-and-Drop Matching ─── -->
                    <div>
                        <!-- Instructions -->
                        <p class="text-base font-bold text-gray-900 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
                        <p class="text-sm text-gray-700 select-text mt-1" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                        <p class="text-sm text-gray-700 select-text mt-0.5" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></p>

                        <!-- Reasons Box -->
                        <div class="mt-3 rounded border border-gray-300 bg-gray-50 p-3">
                            <p class="mb-2 text-sm font-bold text-gray-900 select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></p>
                            <div class="flex flex-wrap gap-2">
                                <div
                                    v-for="(reason, idx) in reasons"
                                    :key="reason.letter"
                                    :draggable="!isReasonUsed(reason.letter)"
                                    @dragstart="onDragStart($event, reason.letter)"
                                    @dragend="onDragEnd"
                                    class="flex items-start gap-1 cursor-grab active:cursor-grabbing select-none rounded border px-2 py-1 text-sm transition-all"
                                    :class="isReasonUsed(reason.letter)
                                        ? 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed opacity-50'
                                        : 'border-gray-400 bg-white text-gray-800 hover:border-gray-600 hover:bg-gray-50'"
                                    :title="isReasonUsed(reason.letter) ? 'Already placed' : 'Drag to a picture book'"
                                >
                                    <span class="font-bold shrink-0">{{ reason.letter }}.</span>
                                    <span
                                        class="select-text"
                                        :data-segment-id="5 + idx"
                                        v-html="getHighlightedSegmentById(5 + idx)"
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <!-- Picture Books Drop Targets -->
                        <div class="mt-4">
                            <p class="mb-2 text-sm font-bold text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></p>
                            <div class="space-y-2">
                                <div
                                    v-for="(book, idx) in pictureBooks"
                                    :key="book.qNum"
                                    :id="`question-${book.qNum}`"
                                    class="relative flex items-center gap-3 rounded border p-2 transition-colors"
                                    :class="dragOverTarget === book.qNum
                                        ? 'border-gray-600 bg-gray-100'
                                        : 'border-gray-200 bg-white hover:bg-gray-50'"
                                    @mouseenter="hoveredQuestion = book.qNum"
                                    @mouseleave="hoveredQuestion = null"
                                    @dragover.prevent="onDragOver(book.qNum)"
                                    @dragleave="onDragLeave"
                                    @drop.prevent="onDrop(book.qNum)"
                                >
                                    <!-- Question number -->
                                    <span class="text-sm font-bold text-gray-900 w-5 shrink-0">{{ book.qNum }}.</span>

                                    <!-- Book title -->
                                    <span
                                        class="flex-1 text-sm text-gray-800 select-text"
                                        :data-segment-id="15 + idx"
                                        v-html="getHighlightedSegmentById(15 + idx)"
                                    ></span>

                                    <!-- Drop zone / answer badge -->
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded border-2 border-dashed text-sm font-bold transition-all"
                                        :class="answers[`q${book.qNum}`]
                                            ? 'border-gray-700 bg-gray-800 text-white cursor-pointer'
                                            : 'border-gray-300 text-gray-400'"
                                        @click.stop="answers[`q${book.qNum}`] && clearBookAnswer(book.qNum)"
                                        :title="answers[`q${book.qNum}`] ? 'Click to remove' : 'Drop a letter here'"
                                    >
                                        {{ answers[`q${book.qNum}`] || '?' }}
                                    </div>

                                    <!-- Bookmark button -->
                                    <button
                                        v-show="hoveredQuestion === book.qNum || isQuestionFlagged(book.qNum)"
                                        @click.stop="toggleFlag(book.qNum)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(book.qNum)
                                            ? 'border-gray-400 bg-transparent text-red-500'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(book.qNum) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-200" />

                    <!-- ─── Questions 27–28: Choose TWO ─── -->
                    <div
                        id="question-27-28"
                        class="relative"
                        @mouseenter="hoveredQuestion = 27"
                        @mouseleave="hoveredQuestion = null"
                    >
                        <button
                            v-show="hoveredQuestion === 27 || isQuestionFlagged(27) || isQuestionFlagged(28)"
                            @click.stop="toggleFlag(27); toggleFlag(28);"
                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="isQuestionFlagged(27) || isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                            :title="isQuestionFlagged(27) || isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                        </button>

                        <p class="text-base font-bold text-gray-900 select-text pr-8" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></p>
                        <p class="text-sm text-gray-700 select-text mt-0.5" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></p>
                        <p class="text-sm text-gray-800 select-text mt-0.5" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></p>

                        <div class="mt-2 space-y-1">
                            <label v-for="(opt, i) in q2728Options" :key="opt.value"
                                class="flex cursor-pointer items-start gap-2 rounded p-1 transition-colors hover:bg-gray-50"
                                :class="isDisabled('q27_28', opt.value) && !isSelected('q27_28', opt.value) ? 'opacity-50 cursor-not-allowed' : ''"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isSelected('q27_28', opt.value)"
                                    :disabled="isDisabled('q27_28', opt.value)"
                                    @change="handleMultipleChoice('q27_28', opt.value)"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed"
                                />
                                <span class="text-sm font-bold text-gray-900 shrink-0">{{ opt.value }}.</span>
                                <span
                                    class="text-base leading-relaxed text-gray-900 select-text"
                                    :data-segment-id="24 + i"
                                    v-html="getHighlightedSegmentById(24 + i)"
                                ></span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Selected: {{ multipleAnswers.q27_28.length }}/2</p>
                    </div>

                    <hr class="border-gray-200" />

                    <!-- ─── Questions 29–30: Choose TWO ─── -->
                    <div
                        id="question-29-30"
                        class="relative"
                        @mouseenter="hoveredQuestion = 29"
                        @mouseleave="hoveredQuestion = null"
                    >
                        <button
                            v-show="hoveredQuestion === 29 || isQuestionFlagged(29) || isQuestionFlagged(30)"
                            @click.stop="toggleFlag(29); toggleFlag(30);"
                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="isQuestionFlagged(29) || isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                            :title="isQuestionFlagged(29) || isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                        </button>

                        <p class="text-base font-bold text-gray-900 select-text pr-8" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></p>
                        <p class="text-sm text-gray-700 select-text mt-0.5" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></p>
                        <p class="text-sm text-gray-800 select-text mt-0.5" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></p>

                        <div class="mt-2 space-y-1">
                            <label v-for="(opt, i) in q2930Options" :key="opt.value"
                                class="flex cursor-pointer items-start gap-2 rounded p-1 transition-colors hover:bg-gray-50"
                                :class="isDisabled('q29_30', opt.value) && !isSelected('q29_30', opt.value) ? 'opacity-50 cursor-not-allowed' : ''"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isSelected('q29_30', opt.value)"
                                    :disabled="isDisabled('q29_30', opt.value)"
                                    @change="handleMultipleChoice('q29_30', opt.value)"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed"
                                />
                                <span class="text-sm font-bold text-gray-900 shrink-0">{{ opt.value }}.</span>
                                <span
                                    class="text-base leading-relaxed text-gray-900 select-text"
                                    :data-segment-id="32 + i"
                                    v-html="getHighlightedSegmentById(32 + i)"
                                ></span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Selected: {{ multipleAnswers.q29_30.length }}/2</p>
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
