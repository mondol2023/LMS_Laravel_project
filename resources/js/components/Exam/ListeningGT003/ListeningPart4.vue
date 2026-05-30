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
// Text segments with their cumulative offsets calculated later
const rawTextSegments = [
    { id: 'part1-title', text: 'Part 4', offset: 0 },
    { id: 'part1-desc', text: 'Listen and answer questions 31–40.', offset: 6 },
    { id: 0, text: 'SECTION 4' },
    { id: 1, text: 'Questions 31–40' },
    { id: 2, text: 'Complete the notes below.' },
    { id: 3, text: 'Write NO MORE THAN THREE WORDS for each answer.' },
    { id: 4, text: 'GIVING A SPEECH' },
    { id: 5, text: 'Reasons for nervousness' },
    { id: 6, text: '• Lecturers often feel more' },
    { id: 7, text: 'if the speech is important.' },
    { id: 8, text: '• Many think that the ability to make a good public speaking is' },
    { id: 9, text: 'while in fact it is a skill that can be learned by anyone.' },
    { id: 10, text: 'How to prepare a quality speech' },
    { id: 11, text: '• The audience will only remember the' },
    { id: 12, text: 'sentence of a speech.' },
    { id: 13, text: '• Ensure that your speech is' },
    { id: 14, text: "Do's and Don'ts" },
    { id: 15, text: "• Don't start your speech until audience is" },
    { id: 16, text: '• You can make your main ideas or notes on cards or a' },
    { id: 17, text: '• You do not need to write down the' },
    { id: 18, text: 'speech.' },
    { id: 19, text: '• You can just write' },
    { id: 20, text: 'ideas.' },
    { id: 21, text: '• Remember to' },
    { id: 22, text: 'yourself to see how long your speech will be.' },
    { id: 23, text: "• Don't just read from" },
];

const buildTextSegments = () => {
    let offset = 0;

    return rawTextSegments.map((segment) => {
        const result = {
            ...segment,
            offset,
        };

        offset += segment.text.length+1;
        return result;
    });
};

const textSegments = ref(buildTextSegments());

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

    if (words.length > 3 && target.value.trim() !== '') {
        // Limit to first 2 words
        const limitedValue = words.slice(0, 3).join(' ');
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
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part1-title"
                    v-html="getHighlightedSegmentById('part1-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part1-desc"
                    v-html="getHighlightedSegmentById('part1-desc')"
                ></p>
            </div>

            <!-- Instructions -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <div class="flex space-x-2">

                    <div>
                        <p class="text-sm text-gray-700 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                        <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></p>
                    </div>
                </div>
            </div>

            <!-- Scrollable Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-32 select-text">
                <div class="p-2 sm:p-3">
                    <div class="p-2 select-text sm:p-3">

                        <!-- Notes Box -->
                        <div class=" border-gray-300 p-4 select-text sm:p-6">

                            <!-- Speech Title -->
                            <h3 class="mb-4 border-b border-gray-300 pb-2  text-lg font-bold text-gray-900 select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></h3>

                            <!-- ── Section 1: Reasons for nervousness ── -->
                            <div class="mb-6">
                                <h4 class="mb-3 text-base font-bold text-gray-900 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></h4>

                                <!-- Q31 -->
                                <div id="question-31" class="mb-3 flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="31"
                                            maxlength="30"
                                        />
                                        <span class="text-base text-gray-900 select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>

                                        <button
                                            v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                            @click.stop="toggleFlag(31)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                     </div>

                                <!-- Q32 -->
                                <div id="question-32" class="flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="32"
                                            maxlength="30"
                                        />
                                        <span class="text-base text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>

                                        <button
                                            v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                            @click.stop="toggleFlag(32)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                    </div>
                            </div>

                            <!-- ── Section 2: How to prepare a quality speech ── -->
                            <div class="mb-6 border-t border-gray-200 pt-4">
                                <h4 class="mb-3 text-base font-bold text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></h4>

                                <!-- Q33 -->
                                <div id="question-33" class="mb-3 flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="33"
                                            maxlength="30"
                                        />
                                        <span class="text-base text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>

                                        <button
                                            v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                            @click.stop="toggleFlag(33)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                     </div>

                                <!-- Q34 -->
                                <div id="question-34" class="flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="34"
                                            maxlength="30"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                            @click.stop="toggleFlag(34)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                </div>
                            </div>

                            <!-- ── Section 3: Do's and Don'ts ── -->
                            <div class="mb-2 border-t border-gray-200 pt-4">
                                <h4 class="mb-3 text-base font-bold text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></h4>

                                <!-- Q35 -->
                                <div id="question-35" class="mb-3 flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="35"
                                            maxlength="30"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                            @click.stop="toggleFlag(35)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                </div>

                                <!-- Q36 -->
                                <div id="question-36" class="mb-3 flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="36"
                                            maxlength="30"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                            @click.stop="toggleFlag(36)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                </div>

                                <!-- Q37 -->
                                <div id="question-37" class="mb-3 flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="37"
                                            maxlength="30"
                                        />
                                        <span class="text-base text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>

                                        <button
                                            v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                            @click.stop="toggleFlag(37)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                    </div>

                                <!-- Q38 -->
                                <div id="question-38" class="mb-3 flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="38"
                                            maxlength="30"
                                        />
                                        <span class="text-base text-gray-900 select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>

                                        <button
                                            v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                            @click.stop="toggleFlag(38)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                    </div>

                                <!-- Q39 -->
                                <div id="question-39" class="mb-3 flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="39"
                                            maxlength="30"
                                        />
                                        <span class="text-base text-gray-900 select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>

                                        <button
                                            v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                            @click.stop="toggleFlag(39)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                    </div>

                                <!-- Q40 -->
                                <div id="question-40" class="flex flex-wrap items-center gap-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                    <span
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
                                            class="question-input mx-1 min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none sm:min-w-[140px]"
                                            placeholder="40"
                                            maxlength="30"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                            @click.stop="toggleFlag(40)"
                                            class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                        </button>
                                    </span>
                                </div>

                            </div><!-- /Do's and Don'ts -->
                        </div><!-- /Notes Box -->
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
