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
const textSegments = ref<TextSegment[]>([
    { id: 'part4-title', text: 'Part 4', offset: 0 },
    { id: 'part4-desc', text: 'Listen and answer questions 31–40.', offset: 7 },
    { id: 0, text: 'QUESTIONS 31–40', offset: 42 },
    { id: 1, text: 'Questions 31–33', offset: 58 },
    { id: 2, text: 'Complete the summary below.', offset: 74 },
    { id: 3, text: 'Write NO MORE THAN TWO WORDS for each answer.', offset: 102 },
    { id: 4, text: 'If soil is healthy, it is a', offset: 148 },
    { id: 5, text: 'teeming with life such as worms, fungi and bacteria.', offset: 176 },
    { id: 6, text: 'If plants are grown in poor soil, they will lack', offset: 229 },
    { id: 7, text: 'and human health will suffer.', offset: 278 },
    { id: 8, text: 'Plants are nourished by organic matter,', offset: 308 },
    { id: 9, text: 'and other essential elements which are broken down by insects and other organisms in a synergistic relationship.', offset: 348 },
    { id: 10, text: 'Questions 34–36', offset: 461 },
    { id: 11, text: 'Label the diagram below.', offset: 477 },
    { id: 12, text: 'Write NO MORE THAN TWO WORDS for each answer.', offset: 502 },
    { id: 13, text: 'Layers of Soil', offset: 548 },
    { id: 14, text: 'Questions 37–40', offset: 563 },
    { id: 15, text: 'Complete the notes below.', offset: 579 },
    { id: 16, text: 'Write NO MORE THAN TWO WORDS for each answer.', offset: 605 },
    { id: 17, text: 'Problems:', offset: 651 },
    { id: 18, text: 'Conventional farming methods', offset: 661 },
    { id: 19, text: 'monoculture', offset: 690 },
    { id: 20, text: 'synthetic fertiliser & chemicals used for', offset: 702 },
    { id: 21, text: 'genetically-modified seeds', offset: 744 },
    { id: 22, text: 'pesticide & fungicide sprayed on crops after picking', offset: 771 },
    { id: 23, text: 'no need for documentation of', offset: 824 },
    { id: 24, text: 'Organic farming methods', offset: 853 },
    { id: 25, text: 'crop rotation', offset: 877 },
    { id: 26, text: 'covering crops', offset: 891 },
    { id: 27, text: 'use of insects as natural', offset: 906 },
    { id: 28, text: 'addition of manure & green waste', offset: 932 },
    { id: 29, text: 'from various sources, including chemical fertilisers', offset: 965 },
    { id: 30, text: 'Erosion', offset: 1018 },
    { id: 31, text: 'Decomposing organic matter', offset: 1026 },
    { id: 32, text: 'Eluviation', offset: 1053 },
    { id: 33, text: 'Regolith', offset: 1064 },
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
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 rounded border border-gray-200 part-header-box px-4 py-3" @mouseup="handleContentTextSelect">
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

<!-- Main header -->


<!-- Scrollable Questions Content -->
<div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-32 select-text">
    <div class="p-2 sm:p-3">
        <div class="space-y-5 p-2 select-text sm:p-3">

            <!-- ── Q31–33: Summary Completion ── -->
            <div>
                <!-- Sub-instructions -->
                <div class="mb-2 space-y-0.5">
                    <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
                    <p class="text-sm text-gray-700 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                    <p class="text-sm font-bold text-gray-700 select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></p>
                </div>

                <div class="space-y-2 text-base leading-relaxed text-gray-900">

                    <!-- Row 1: ...healthy, it is a [31] teeming with life... -->
                    <div class="group flex flex-wrap items-center gap-1">
                        <span class="select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></span>
                        <span
                            id="question-31"
                            class="relative inline-flex items-center gap-1"
                        >
                            <input
                                v-model="answers.q31"
                                type="text"
                                spellcheck="false"
                                autocomplete="off"
                                @input="handleInput('q31', $event)"
                                @focus="handleFocus('q31')"
                                @blur="handleBlur()"
                                class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                placeholder="31"
                                maxlength="20"
                            />
                        </span>
                        <span class="select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                        <button
                            @click.stop="toggleFlag(31)"
                            class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                            :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                        </button>
                    </div>

                    <!-- Row 2: Plants grown in poor soil lack [32] and human health... -->
                    <div class="group flex flex-wrap items-center gap-1">
                        <span class="select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                        <span
                            id="question-32"
                            class="relative inline-flex items-center gap-1"
                        >
                            <input
                                v-model="answers.q32"
                                type="text"
                                spellcheck="false"
                                autocomplete="off"
                                @input="handleInput('q32', $event)"
                                @focus="handleFocus('q32')"
                                @blur="handleBlur()"
                                class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                placeholder="32"
                                maxlength="20"
                            />
                        </span>
                        <span class="select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                        <button
                            @click.stop="toggleFlag(32)"
                            class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                            :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                        </button>
                    </div>

                    <!-- Row 3: Plants are nourished by organic matter, [33] and other essential... -->
                    <div class="group flex flex-wrap items-center gap-1">
                        <span class="select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                        <span
                            id="question-33"
                            class="relative inline-flex items-center gap-1"
                        >
                            <input
                                v-model="answers.q33"
                                type="text"
                                spellcheck="false"
                                autocomplete="off"
                                @input="handleInput('q33', $event)"
                                @focus="handleFocus('q33')"
                                @blur="handleBlur()"
                                class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[60px]"
                                placeholder="33"
                                maxlength="20"
                            />
                        </span>
                        <span class="select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                        <button
                            @click.stop="toggleFlag(33)"
                            class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                            :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Q34–36: Diagram Labelling ── -->
            <div class="border-t pt-4">
                <!-- Sub-instructions -->
                <div class="mb-2 space-y-0.5">
                    <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></p>
                    <p class="text-sm text-gray-700 select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></p>
                    <p class="text-sm font-bold text-gray-700 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></p>
                </div>

                <!-- Diagram title -->
                <h4
                    class="mb-3 text-base  font-bold text-gray-900 select-text"
                    data-segment-id="13"
                    v-html="getHighlightedSegmentById(13)"
                ></h4>

                <!-- Diagram + labels side by side -->
                <div class="flex flex-col gap-4 w-full sm:flex-row sm:items-start">
                    <!-- Image placeholder -->
                    <div class="w-full sm:w-1/5 flex items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50">
                        <img src="/images/listening/IELTS016s-4.png" alt="Diagram" class="w-full h-full object-contain" />
                    </div>

                    <!-- Label inputs listed vertically, matching diagram order top→bottom -->
                    <div class="flex-1 w-full sm:w-1/2 space-y-3 text-base leading-relaxed text-gray-900">

                        <!-- Decomposing organic matter → Q36 -->
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="text-gray-700 mt-30 text-sm" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></span>
                        </div>
                        <div
                            id="question-36"
                            class="group flex flex-wrap items-center gap-1"
                        >
                            <span class="font-semibold text-gray-900 text-sm"></span>
                            <span
                                class="relative inline-flex items-center gap-1"
                            >
                                <input
                                    v-model="answers.q36"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    @input="handleInput('q36', $event)"
                                    @focus="handleFocus('q36')"
                                    @blur="handleBlur()"
                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                    placeholder="36"
                                    maxlength="20"
                                />
                            </span>
                            <button
                                @click.stop="toggleFlag(36)"
                                class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                            </button>
                        </div>

                        <!-- Eluviation (static label) -->
                        <div class="text-sm text-gray-700 mt-30 select-text" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></div>

                        <!-- Q35 -->
                        <div
                            id="question-35"
                            class="group flex flex-wrap items-center gap-1"
                        >
                            <span class="font-semibold text-gray-900 text-sm"></span>
                            <span class="relative inline-flex items-center gap-1">
                                <input
                                    v-model="answers.q35"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    @input="handleInput('q35', $event)"
                                    @focus="handleFocus('q35')"
                                    @blur="handleBlur()"
                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                    placeholder="35"
                                    maxlength="20"
                                />
                            </span>
                            <button
                                @click.stop="toggleFlag(35)"
                                class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                            </button>
                        </div>

                        <!-- Regolith (static label) -->
                        <div class="text-sm mt-4 text-gray-700 mt-30 select-text" data-segment-id="33" v-html="getHighlightedSegmentById(33)"></div>

                        <!-- Q34 -->
                        <div
                            id="question-34"
                            class="group flex flex-wrap items-center gap-1"
                        >
                            <span class="font-semibold text-gray-900 text-sm"></span>
                            <span class="relative inline-flex items-center gap-1">
                                <input
                                    v-model="answers.q34"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    @input="handleInput('q34', $event)"
                                    @focus="handleFocus('q34')"
                                    @blur="handleBlur()"
                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                    placeholder="34"
                                    maxlength="20"
                                />
                            </span>
                            <button
                                @click.stop="toggleFlag(34)"
                                class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Q37–40: Two-column table with bullet notes ── -->
            <div class="border-t pt-4">
                <!-- Sub-instructions -->
                <div class="mb-2 space-y-0.5">
                    <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></p>
                    <p class="text-sm text-gray-700 select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></p>
                    <p class="text-sm font-bold text-gray-700 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></p>
                </div>

                <!-- Problems heading -->
                <p
                    class="mb-2 text-sm font-bold text-gray-900 select-text"
                    data-segment-id="17"
                    v-html="getHighlightedSegmentById(17)"
                ></p>

                <!-- Erosion bullet (outside table, from image 1) -->
                <ul class="mb-3 list-disc pl-5 text-sm text-gray-900 select-text">
                    <li data-segment-id="30" v-html="getHighlightedSegmentById(30)"></li>
                </ul>

                <div
                    id="question-37"
                    class="group flex flex-wrap items-center gap-1"
                >
                    <span class="font-semibold text-gray-900 text-sm"></span>
                    <span class="relative inline-flex items-center mb-2 gap-1">
                        <input
                            v-model="answers.q37"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            @input="handleInput('q37', $event)"
                            @focus="handleFocus('q37')"
                            @blur="handleBlur()"
                            class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                            placeholder="37"
                            maxlength="20"
                        />
                    </span>
                    <span class="select-text text-sm text-gray-700" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></span>
                    <button
                        @click.stop="toggleFlag(37)"
                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                        :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                    </button>
                </div>

                <!-- Two-column table -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-300 text-sm">
                        <thead>
                            <tr>
                                <th class="border border-gray-300 bg-gray-50 px-3 py-2 text-left font-bold text-gray-900 select-text w-1/2"
                                    data-segment-id="18" v-html="getHighlightedSegmentById(18)">
                                </th>
                                <th class="border border-gray-300 bg-gray-50 px-3 py-2 text-left font-bold text-gray-900 select-text w-1/2"
                                    data-segment-id="24" v-html="getHighlightedSegmentById(24)">
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="align-top">
                                <!-- Left column: Conventional -->
                                <td class="border border-gray-300 px-3 py-2 align-top">
                                    <ul class="space-y-2 text-gray-900">
                                        <!-- monoculture -->
                                        <li class="flex items-start gap-1">
                                            <span class="mt-1 text-gray-500">•</span>
                                            <span class="select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                        </li>
                                        <!-- synthetic fertiliser & chemicals used for [38] -->
                                        <li class="group flex flex-wrap items-center gap-1">
                                            <span class="mt-1 text-gray-500 self-start">•</span>
                                            <span class="select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                            <span
                                                id="question-38"
                                                class="relative inline-flex items-center gap-1"
                                            >
                                                <input
                                                    v-model="answers.q38"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    @input="handleInput('q38', $event)"
                                                    @focus="handleFocus('q38')"
                                                    @blur="handleBlur()"
                                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                    placeholder="38"
                                                    maxlength="20"
                                                />
                                            </span>
                                            <button
                                                @click.stop="toggleFlag(38)"
                                                class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                                :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </li>
                                        <!-- genetically-modified seeds -->
                                        <li class="flex items-start gap-1">
                                            <span class="mt-1 text-gray-500">•</span>
                                            <span class="select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                                        </li>
                                        <!-- pesticide & fungicide... -->
                                        <li class="flex items-start gap-1">
                                            <span class="mt-1 text-gray-500">•</span>
                                            <span class="select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                        </li>
                                        <!-- no need for documentation of [39] -->
                                        <li class="group flex flex-wrap items-center gap-1">
                                            <span class="mt-1 text-gray-500 self-start">•</span>
                                            <span class="select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                            <span
                                                id="question-39"
                                                class="relative inline-flex items-center gap-1"
                                            >
                                                <input
                                                    v-model="answers.q39"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    @input="handleInput('q39', $event)"
                                                    @focus="handleFocus('q39')"
                                                    @blur="handleBlur()"
                                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                    placeholder="39"
                                                    maxlength="20"
                                                />
                                            </span>
                                            <button
                                                @click.stop="toggleFlag(39)"
                                                class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                                :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </li>
                                    </ul>
                                </td>

                                <!-- Right column: Organic -->
                                <td class="border border-gray-300 px-3 py-2 align-top">
                                    <ul class="space-y-2 text-gray-900">
                                        <!-- crop rotation -->
                                        <li class="flex items-start gap-1">
                                            <span class="mt-1 text-gray-500">•</span>
                                            <span class="select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                        </li>
                                        <!-- covering crops -->
                                        <li class="flex items-start gap-1">
                                            <span class="mt-1 text-gray-500">•</span>
                                            <span class="select-text" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                                        </li>
                                        <!-- use of insects as natural [40] -->
                                        <li class="group flex flex-wrap items-center gap-1">
                                            <span class="mt-1 text-gray-500 self-start">•</span>
                                            <span class="select-text" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></span>
                                            <span
                                                id="question-40"
                                                class="relative inline-flex items-center gap-1"
                                            >
                                                <input
                                                    v-model="answers.q40"
                                                    type="text"
                                                    spellcheck="false"
                                                    autocomplete="off"
                                                    @input="handleInput('q40', $event)"
                                                    @focus="handleFocus('q40')"
                                                    @blur="handleBlur()"
                                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                    placeholder="40"
                                                    maxlength="20"
                                                />
                                            </span>
                                            <button
                                                @click.stop="toggleFlag(40)"
                                                class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all group-hover:opacity-100 group-focus-within:opacity-100"
                                                :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600 opacity-5'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </li>
                                        <!-- addition of manure & green waste -->
                                        <li class="flex items-start gap-1">
                                            <span class="mt-1 text-gray-500">•</span>
                                            <span class="select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Remaining text (segment 29) -->

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
