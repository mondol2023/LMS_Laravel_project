<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { useTooltip, type TextSegment } from '@/composables/useTooltip';
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

// Listening Part 4 component

// Answers for questions 31-40
const answers = ref({
    q31: '',
    q32: '',
    q33: '',
    q34: '',
    q35: '',
    q36: '',
    q37: '',
    q38: '',
    q39: '',
    q40: '',
});

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// Text segments with their cumulative offsets
const textSegments = ref([
    { id: 'part4-title', text: 'Part 4: Topic of sustainability',          offset: 0    },
    { id: 'part4-desc', text: 'Listen and answer questions 31–40.',         offset: 32   },

    { id: 0,  text: 'SECTION 4 — Questions 31–40',                         offset: 67   },
    { id: 1,  text: 'Complete the notes below.',                            offset: 95   },
    { id: 2,  text: 'Write ONE WORD ONLY for each answer.',                 offset: 121  },

    { id: 3,  text: 'Sustainability',                                       offset: 158  },

    { id: 4,  text: 'Sustainability: Term first used in 1987 by writers of United Nations report', offset: 173 },
    { id: 5,  text: 'Lecture aim: Analysis of the',                         offset: 249  },
    { id: 6,  text: 'surrounding sustainability',                           offset: 278  },

    { id: 7,  text: 'Sustainable development: Development that will meet the needs of both present and future', offset: 305 },
    { id: 8,  text: 'Myth 1:',                                              offset: 394  },

    { id: 9,  text: 'No mention of the',                                    offset: 402  },
    { id: 10, text: 'in original definition',                               offset: 420  },

    { id: 11, text: 'Original focus: poorer nations should have the same',  offset: 443  },
    { id: 12, text: 'to natural resources as richer nations',               offset: 495  },

    { id: 13, text: 'This would help them achieve better',                  offset: 534  },
    { id: 14, text: 'conditions',                                           offset: 570  },

    { id: 15, text: 'Myth 2: \u2018Green\u2019 vs Sustainable',            offset: 581  },
    { id: 16, text: 'Key difference: unlike \u2018green\u2019, \u2018sustainable\u2019 is not always associated with things that are natural', offset: 612 },

    { id: 17, text: 'For the sustainability lobby, the key problem is finding', offset: 712 },
    { id: 18, text: 'to develop technology',                                offset: 769  },

    { id: 19, text: 'Sustainability lobby is prepared to',                  offset: 791  },
    { id: 20, text: 'nuclear energy',                                       offset: 827  },

    { id: 21, text: 'Myth 3: Role of technology',                           offset: 842  },

    { id: 22, text: 'Introduction of battery stations on',                  offset: 869  },
    { id: 23, text: 'is an example of new business thinking, not new technology', offset: 905 },

    { id: 24, text: 'Agriculture: some farmers plant crops like sunflowers which don\u2019t use much water, unlike', offset: 964 },
    { id: 25, text: 'Greater use of water is a result of changes in our',   offset: 1053 },
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
                // Just render the mark with data attributes - tooltip will be rendered via HighlightTooltips component
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
    return answers.value;
};

// Focus handling for better UX
const focusedInput = ref<string | null>(null);

const handleFocus = (questionKey: string) => {
    focusedInput.value = questionKey;
};

const handleBlur = () => {
    focusedInput.value = null;
};

// Input validation - limit to 2 words
const handleInput = (questionKey: string, event: Event) => {
    const target = event.target as HTMLInputElement;
    const words = target.value.trim().split(/\s+/);

    if (words.length > 2 && target.value.trim() !== '') {
        // Limit to first 2 words
        const limitedValue = words.slice(0, 2).join(' ');
        answers.value[questionKey as keyof typeof answers.value] = limitedValue;
        target.value = limitedValue;
    } else {
        answers.value[questionKey as keyof typeof answers.value] = target.value;
    }
};

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

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
    }
};

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="tooltipHandleContentClick" class="px-4 py-2 pb-32 select-text sm:px-2 sm:py-4 md:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="mb-3 rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part4-title"
                    v-html="getHighlightedSegmentById('part4-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part4-desc"
                    v-html="getHighlightedSegmentById('part4-desc')"
                ></p>
            </div>

            <!-- Instructions -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="0" v-html="getHighlightedSegmentById(0)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
                <p class="text-sm font-bold text-gray-700 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
            </div>

            <!-- Scrollable Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-32 select-text">
                <div class="p-2 sm:p-3">
                    <div class="p-2 select-text sm:p-3 space-y-4 text-base leading-relaxed text-gray-900">

                        <!-- Section Title -->
                        <h2 class="text-lg font-bold text-gray-900 select-text border-b pb-1"
                            data-segment-id="3"
                            v-html="getHighlightedSegmentById(3)"
                        ></h2>

                        <!-- Intro paragraph 1: plain text (seg 4) -->
                        <p class="select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></p>

                        <!-- Intro paragraph 2: Lecture aim ... Q31 ... surrounding sustainability -->
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                            <span
                                id="question-31"
                                class="relative inline-flex items-center gap-1"
                                @mouseenter="hoveredQuestion = 31"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <input
                                    v-model="answers.q31"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    @input="handleInput('q31', $event)"
                                    @focus="hoveredQuestion = 31; handleFocus('q31');"
                                    @blur="hoveredQuestion = null; handleBlur();"
                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                    placeholder="31"
                                    maxlength="20"
                                />
                                <button
                                    v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                    @click.stop="toggleFlag(31)"
                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                </button>
                            </span>
                            <span class="select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                        </div>

                        <!-- Intro paragraph 3: Sustainable development ... Q32 -->
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                            <span
                                id="question-32"
                                class="relative inline-flex items-center gap-1"
                                @mouseenter="hoveredQuestion = 32"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <input
                                    v-model="answers.q32"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    @input="handleInput('q32', $event)"
                                    @focus="hoveredQuestion = 32; handleFocus('q32');"
                                    @blur="hoveredQuestion = null; handleBlur();"
                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                    placeholder="32"
                                    maxlength="20"
                                />
                                <button
                                    v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                    @click.stop="toggleFlag(32)"
                                    class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                </button>
                            </span>
                        </div>

                        <!-- ── Myth 1 ── -->
                        <div class="border-t pt-3">
                            <h4 class="font-bold text-gray-900 mb-2 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></h4>
                            <ul class="space-y-2 list-none pl-2">

                                <!-- Bullet 1: No mention of the Q33 in original definition -->
                                <li class="flex flex-wrap items-center gap-1 before:content-['•'] before:mr-1 before:text-gray-600">
                                    <span class="select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                    <span
                                        id="question-33"
                                        class="relative inline-flex items-center gap-1"
                                        @mouseenter="hoveredQuestion = 33"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q33"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            @input="handleInput('q33', $event)"
                                            @focus="hoveredQuestion = 33; handleFocus('q33');"
                                            @blur="hoveredQuestion = null; handleBlur();"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="33"
                                            maxlength="20"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                            @click.stop="toggleFlag(33)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                    <span class="select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                </li>

                                <!-- Bullet 2: Original focus: poorer nations ... Q34 ... to natural resources ... Q35 ... conditions -->
                                <li class="before:content-['•'] before:mr-1 before:text-gray-600">
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                        <span
                                            id="question-34"
                                            class="relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 34"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q34"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                @input="handleInput('q34', $event)"
                                                @focus="hoveredQuestion = 34; handleFocus('q34');"
                                                @blur="hoveredQuestion = null; handleBlur();"
                                                class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                                placeholder="34"
                                                maxlength="20"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                                @click.stop="toggleFlag(34)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                        <span class="select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                    </div>
                                    <!-- sub-line: This would help them achieve better Q35 conditions -->
                                    <div class="flex flex-wrap items-center gap-1 mt-1 pl-2">
                                        <span class="select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                        <span
                                            id="question-35"
                                            class="relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 35"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q35"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                @input="handleInput('q35', $event)"
                                                @focus="hoveredQuestion = 35; handleFocus('q35');"
                                                @blur="hoveredQuestion = null; handleBlur();"
                                                class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                                placeholder="35"
                                                maxlength="20"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                                @click.stop="toggleFlag(35)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                        <span class="select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- ── Myth 2 ── -->
                        <div class="border-t pt-3">
                            <h4 class="font-bold text-gray-900 mb-2 select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></h4>
                            <ul class="space-y-2 list-none pl-2">

                                <!-- Bullet 1: Key difference (plain text, seg 16) -->
                                <li class="before:content-['•'] before:mr-1 before:text-gray-600 select-text">
                                    <span data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                </li>

                                <!-- Bullet 2: For the sustainability lobby ... Q36 ... to develop technology -->
                                <li class="flex flex-wrap items-center gap-1 before:content-['•'] before:mr-1 before:text-gray-600">
                                    <span class="select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                    <span
                                        id="question-36"
                                        class="relative inline-flex items-center gap-1"
                                        @mouseenter="hoveredQuestion = 36"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q36"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            @input="handleInput('q36', $event)"
                                            @focus="hoveredQuestion = 36; handleFocus('q36');"
                                            @blur="hoveredQuestion = null; handleBlur();"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="36"
                                            maxlength="20"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                            @click.stop="toggleFlag(36)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                    <span class="select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                </li>

                                <!-- Bullet 3: Sustainability lobby is prepared to Q37 nuclear energy -->
                                <li class="flex flex-wrap items-center gap-1 before:content-['•'] before:mr-1 before:text-gray-600">
                                    <span class="select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                    <span
                                        id="question-37"
                                        class="relative inline-flex items-center gap-1"
                                        @mouseenter="hoveredQuestion = 37"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q37"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            @input="handleInput('q37', $event)"
                                            @focus="hoveredQuestion = 37; handleFocus('q37');"
                                            @blur="hoveredQuestion = null; handleBlur();"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="37"
                                            maxlength="20"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                            @click.stop="toggleFlag(37)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                    <span class="select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                </li>
                            </ul>
                        </div>

                        <!-- ── Myth 3 ── -->
                        <div class="border-t pt-3">
                            <h4 class="font-bold text-gray-900 mb-2 select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></h4>
                            <ul class="space-y-2 list-none pl-2">

                                <!-- Bullet 1: Introduction of battery stations on Q38 is an example... -->
                                <li class="flex flex-wrap items-center gap-1 before:content-['•'] before:mr-1 before:text-gray-600">
                                    <span class="select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                    <span
                                        id="question-38"
                                        class="relative inline-flex items-center gap-1"
                                        @mouseenter="hoveredQuestion = 38"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q38"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            @input="handleInput('q38', $event)"
                                            @focus="hoveredQuestion = 38; handleFocus('q38');"
                                            @blur="hoveredQuestion = null; handleBlur();"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="38"
                                            maxlength="20"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                            @click.stop="toggleFlag(38)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                    <span class="select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                </li>

                                <!-- Bullet 2: Agriculture: ... unlike Q39 -->
                                <li class="flex flex-wrap items-center gap-1 before:content-['•'] before:mr-1 before:text-gray-600">
                                    <span class="select-text" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                    <span
                                        id="question-39"
                                        class="relative inline-flex items-center gap-1"
                                        @mouseenter="hoveredQuestion = 39"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q39"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            @input="handleInput('q39', $event)"
                                            @focus="hoveredQuestion = 39; handleFocus('q39');"
                                            @blur="hoveredQuestion = null; handleBlur();"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="39"
                                            maxlength="20"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                            @click.stop="toggleFlag(39)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                </li>

                                <!-- Bullet 3: Greater use of water is a result of changes in our Q40 -->
                                <li class="flex flex-wrap items-center gap-1 before:content-['•'] before:mr-1 before:text-gray-600">
                                    <span class="select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                    <span
                                        id="question-40"
                                        class="relative inline-flex items-center gap-1"
                                        @mouseenter="hoveredQuestion = 40"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q40"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            @input="handleInput('q40', $event)"
                                            @focus="hoveredQuestion = 40; handleFocus('q40');"
                                            @blur="hoveredQuestion = null; handleBlur();"
                                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                            placeholder="40"
                                            maxlength="20"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                            @click.stop="toggleFlag(40)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>
                                </li>

                            </ul>
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
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #000000;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #1f2937;
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

/* Bold placeholder styling for question inputs */
.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>

<!-- Non-scoped styles for v-html generated note indicators -->
<style>
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer;
}
</style>
