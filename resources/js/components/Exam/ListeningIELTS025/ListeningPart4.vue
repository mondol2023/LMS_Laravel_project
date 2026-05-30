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
const textSegments = ref<TextSegment[]>([
// Part header
{ id: 'part4-title', text: 'Part 4: Animals called gastropods', offset: 0 },
{ id: 'part4-desc', text: 'Listen and answer questions 31–40.', offset: 6 },

// Instruction section
{ id: 0, text: 'Questions 31–40', offset: 41 },
{ id: 1, text: 'Complete the notes below.', offset: 57 },
{ id: 2, text: 'Write ONE WORD AND/OR A NUMBER for each answer.', offset: 83 },

// Topic title
{ id: 3, text: 'Gastropods (snails and slugs)', offset: 129 },

// Evolution
{ id: 4, text: 'Evolution', offset: 160 },
{ id: 5, text: 'minerals in the bodies of gastropods are like those in the', offset: 180 },
{ id: 'q31', text: '', offset: 240 },
{ id: 6, text: 'fossils date back 500 million years', offset: 260 },

// Physical features
{ id: 7, text: 'Physical features', offset: 290 },
{ id: 8, text: 'single, muscular foot', offset: 310 },
{ id: 9, text: 'radula (used for feeding)', offset: 330 },

{ id: 10, text: 'Shell (snails only)', offset: 350 },
{ id: 11, text: 'size: British shells range from 1.5–50 mm', offset: 370 },

{ id: 12, text: 'form: most shells coil to the', offset: 400 },
{ id: 'q32', text: '', offset: 430 },

{ id: 13, text: 'some shells have ribs, spines or', offset: 450 },
{ id: 'q33', text: '', offset: 480 },

{ id: 14, text: 'they have various colors and patterns', offset: 500 },

// Feeding habits
{ id: 15, text: 'Feeding habits', offset: 530 },
{ id: 16, text: 'mainly feed on rotting plants, fungi or algae', offset: 550 },

{ id: 17, text: 'some eat live animals, e.g. shield slugs eat', offset: 580 },
{ id: 'q34', text: '', offset: 620 },

// Predators
{ id: 18, text: 'Predators', offset: 650 },
{ id: 19, text: 'birds, frogs, flies', offset: 670 },

{ id: 20, text: 'humans – snails were probably introduced to Britain as food in the', offset: 690 },
{ id: 'q35', text: '', offset: 740 },

{ id: 21, text: 'many gastropods have particular types of', offset: 760 },
{ id: 'q36', text: '', offset: 800 },
{ id: 22, text: 'itself slippery… e.g. glutinous snail makes', offset: 830 },

// Habitats
{ id: 23, text: 'Habitats', offset: 860 },
{ id: 24, text: 'gastropods prefer dampness and shade', offset: 880 },

{ id: 'q37', text: '', offset: 910 },
{ id: 25, text: 'conditions are worst', offset: 940 },

{ id: 26, text: 'biggest variety is found in old, natural habitats, e.g.', offset: 960 },
{ id: 'q38', text: '', offset: 1000 },
{ id: 27, text: 'and meadowland', offset: 1030 },

{ id: 28, text: 'highly specialised species live in unusual habitats, e.g. blind snail lives entirely below the', offset: 1050 },
{ id: 'q39', text: '', offset: 1110 },

{ id: 29, text: 'good indicators of the quality of the', offset: 1130 },
{ id: 'q40', text: '', offset: 1160 },

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
            <!-- Part Header -->
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

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"
                ></p>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="1"
                    v-html="getHighlightedSegmentById(1)"
                ></p>
                <p
                    class="text-sm font-bold text-gray-700 select-text"
                    data-segment-id="2"
                    v-html="getHighlightedSegmentById(2)"
                ></p>
            </div>

            <!-- Section Title -->
            <div class="px-2 sm:px-3">
                <h2
                    class="text-lg font-bold text-gray-900 select-text"
                    data-segment-id="3"
                    v-html="getHighlightedSegmentById(3)"
                ></h2>
            </div>

            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-32 select-text">
                <div class="p-2 sm:p-3">
                    <div class="p-2 select-text sm:p-3">
                        <div class="space-y-2 text-base leading-relaxed">

                            <!-- Section 1: Evolution -->
                            <div>
                                <div class="space-y-2 text-gray-900">

                                    <!-- Sub-heading: Evolution -->
                                    <h4
                                        class="mb-1 text-base font-bold text-gray-900 select-text"
                                        data-segment-id="4"
                                        v-html="getHighlightedSegmentById(4)"
                                    ></h4>

                                    <!-- Line: minerals ... like those in the [q31] fossils date back 500 million years -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                    </div>

                                </div>
                            </div>

                            <!-- Section 2: Physical features -->
                            <div class="border-t pt-2">
                                <div class="space-y-2 text-gray-900">

                                    <!-- Sub-heading: Physical features -->
                                    <h4
                                        class="mb-1 text-base font-bold text-gray-900 select-text"
                                        data-segment-id="7"
                                        v-html="getHighlightedSegmentById(7)"
                                    ></h4>

                                    <!-- static line: single, muscular foot -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                    </div>

                                    <!-- static line: radula (used for feeding) -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                    </div>

                                    <!-- Sub-heading: Shell (snails only) -->
                                    <h4
                                        class="mb-1 text-base font-bold text-gray-900 select-text"
                                        data-segment-id="10"
                                        v-html="getHighlightedSegmentById(10)"
                                    ></h4>

                                    <!-- static line: size -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                    </div>

                                    <!-- Line: form: most shells coil to the [q32] -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>

                                    <!-- Line: some shells have ribs, spines or [q33] -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>

                                    <!-- static line: they have various colors and patterns -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                    </div>

                                </div>
                            </div>

                            <!-- Section 3: Feeding habits -->
                            <div class="border-t pt-2">
                                <div class="space-y-2 text-gray-900">

                                    <!-- Sub-heading: Feeding habits -->
                                    <h4
                                        class="mb-1 text-base font-bold text-gray-900 select-text"
                                        data-segment-id="15"
                                        v-html="getHighlightedSegmentById(15)"
                                    ></h4>

                                    <!-- static line: mainly feed on rotting plants, fungi or algae -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                    </div>

                                    <!-- Line: some eat live animals, e.g. shield slugs eat [q34] -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>

                                </div>
                            </div>

                            <!-- Section 4: Predators -->
                            <div class="border-t pt-2">
                                <div class="space-y-2 text-gray-900">

                                    <!-- Sub-heading: Predators -->
                                    <h4
                                        class="mb-1 text-base font-bold text-gray-900 select-text"
                                        data-segment-id="18"
                                        v-html="getHighlightedSegmentById(18)"
                                    ></h4>

                                    <!-- static line: birds, frogs, flies -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                    </div>

                                    <!-- Line: humans – snails were probably introduced to Britain as food in the [q35] -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>

                                    <!-- Line: many gastropods have particular types of [q36] itself slippery... -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-900 select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                    </div>

                                </div>
                            </div>

                            <!-- Section 5: Habitats -->
                            <div class="border-t pt-2">
                                <div class="space-y-2 text-gray-900">

                                    <!-- Sub-heading: Habitats -->
                                    <h4
                                        class="mb-1 text-base font-bold text-gray-900 select-text"
                                        data-segment-id="23"
                                        v-html="getHighlightedSegmentById(23)"
                                    ></h4>

                                    <!-- static line: gastropods prefer dampness and shade -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                    </div>

                                    <!-- Line: [q37] conditions are worst -->
                                    <div class="flex flex-wrap items-center gap-1">
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-900 select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                    </div>

                                    <!-- Line: biggest variety is found in old, natural habitats, e.g. [q38] and meadowland -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-900 select-text" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></span>
                                    </div>

                                    <!-- Line: highly specialised species live in unusual habitats, e.g. blind snail lives entirely below the [q39] -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>

                                    <!-- Line: good indicators of the quality of the [q40] -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="text-gray-900 select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></span>
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
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
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
