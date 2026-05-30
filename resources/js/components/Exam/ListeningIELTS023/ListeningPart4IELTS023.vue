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

// Listening Part 4 component - Driverless Vehicle Project

// Answers for questions 31-40
const answers = ref<Record<string, string>>({
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
const notes = ref<Note[]>([]);

// Text segments with their cumulative offsets
const textSegments = ref<TextSegment[]>([
    // Part header box text segments
    { id: 'part4-title', text: 'Part 4', offset: 0 },
    { id: 'part4-desc', text: 'Listen and answer questions 31–40.', offset: 7 },
    // Instruction section text segments
    { id: 0, text: 'Questions 31–40', offset: 43 },
    { id: 1, text: 'Complete the notes below.', offset: 59 },
    { id: 2, text: 'Write NO MORE THAN TWO WORDS for each answer.', offset: 85 },
    { id: 3, text: 'Driverless vehicle competition', offset: 130 },
    // Question 31
    { id: 'q31', text: '31.', offset: 161 },
    { id: 4, text: 'Undergraduates from both the engineering school and the', offset: 165 },
    { id: 5, text: 'department can definitely take part in the project.', offset: 224 },
    // Question 32
    { id: 'q32', text: '32.', offset: 273 },
    { id: 6, text: 'The automated vehicles will have to avoid obstacles such as', offset: 277 },
    // Question 33
    { id: 'q33', text: '33.', offset: 340 },
    { id: 7, text: 'The tutor mentions one vehicle which used technology such as', offset: 344 },
    { id: 8, text: 'lasers, and laptops to measure its surroundings.', offset: 404 },
    // Questions 34-36: Multiple Choice MCQ
    { id: 9, text: 'Questions 34–36', offset: 452 },
    { id: 10, text: 'Choose the correct letter, A, B or C.', offset: 471 },
    // Question 34
    { id: 'q34', text: '34.', offset: 509 },
    { id: 11, text: 'The purpose of holding the race is to', offset: 513 },
    { id: 12, text: 'Interest students in careers in industry.', offset: 553 },
    { id: 13, text: 'Help provide finance for universities.', offset: 598 },
    { id: 14, text: 'Find useful new design features.', offset: 637 },
    // Question 35
    { id: 'q35', text: '35.', offset: 671 },
    { id: 15, text: 'The tutor says success will depend on', offset: 675 },
    { id: 16, text: 'The software design.', offset: 714 },
    { id: 17, text: 'Good, solid construction.', offset: 737 },
    { id: 18, text: 'Sophisticated mechanisms.', offset: 765 },
    // Question 36
    { id: 'q36', text: '36.', offset: 793 },
    { id: 19, text: 'This year\'s competitors were surprised that the vehicles', offset: 797 },
    { id: 20, text: 'Were so easy to design.', offset: 861 },
    { id: 21, text: 'Were as successful as they were.', offset: 886 },
    { id: 22, text: 'Took such a short time to construct.', offset: 921 },
    // Questions 37-40: Table completion
    { id: 'q37-40', text: 'Questions 37–40', offset: 961 },
    { id: 23, text: 'Complete the table below.', offset: 980 },
    { id: 24, text: 'Write ONE WORD ONLY for each answer.', offset: 1006 },
    { id: 25, text: 'Schedule', offset: 1049 },
    { id: 26, text: 'Date', offset: 1060 },
    { id: 27, text: 'Event', offset: 1068 },
    { id: 28, text: 'Early May', offset: 1077 },
    { id: 29, text: 'Introducing', offset: 1090 },
    { id: 30, text: 'about the project', offset: 1104 },
    { id: 31, text: 'Late May', offset: 1125 },
    { id: 32, text: 'Essay giving information about relevant', offset: 1137 },
    { id: 33, text: 'and experience', offset: 1180 },
    { id: 34, text: 'June', offset: 1199 },
    { id: 35, text: 'Visit a factory', offset: 1207 },
    { id: 36, text: 'September', offset: 1226 },
    { id: 37, text: 'Workshop', offset: 1239 },
    { id: 38, text: '● discuss', offset: 1251 },
    { id: 39, text: '● learn to use design package', offset: 1263 },
    { id: 40, text: 'December', offset: 1296 },
    { id: 41, text: 'Submission of initial designs', offset: 1308 },
    { id: 42, text: 'January', offset: 1340 },
    { id: 43, text: 'Selection of the', offset: 1351 },
    { id: 44, text: '', offset: 1371 },
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
    return { ...answers.value };
};

// Input validation for word limits
const handleInput = (questionKey: string, event: Event, maxWords: number = 1) => {
    const target = event.target as HTMLInputElement;
    const words = target.value.trim().split(/\s+/);

    if (words.length > maxWords && target.value.trim() !== '') {
        // Limit to maxWords
        const limitedValue = words.slice(0, maxWords).join(' ');
        answers.value[questionKey as keyof typeof answers.value] = limitedValue;
        target.value = limitedValue;
    } else {
        answers.value[questionKey as keyof typeof answers.value] = target.value;
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
    let element;

    if (questionNumber === 37 || questionNumber === 38 || questionNumber === 39 || questionNumber === 40) {
        element = document.getElementById(`question-${questionNumber}`);
    } else {
        element = document.getElementById(`question-${questionNumber}`);
    }

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <!-- Questions Section - Full Width -->
        <div class="flex w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part4-title"
                    v-html="getHighlightedSegmentById('part4-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part4-desc"
                    v-html="getHighlightedSegmentById('part4-desc')"></p>
            </div>

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)">
                </p>
                <p class="text-sm font-bold text-gray-700 select-text" data-segment-id="2"
                    v-html="getHighlightedSegmentById(2)"></p>
            </div>

            <!-- Section Title -->
            <div class="px-2 sm:px-3">
                <h2 class="text-lg font-bold text-gray-900 select-text" data-segment-id="3"
                    v-html="getHighlightedSegmentById(3)"></h2>
            </div>

            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <!-- Questions 31-33: Sentence Completion (NO MORE THAN TWO WORDS) -->
                    <div class="space-y-4">
                        <!-- Question 31 -->
                        <div id="question-31" class="relative flex items-start gap-2 p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 31" @mouseleave="hoveredQuestion = null">
                            <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q31"
                                v-html="getHighlightedSegmentById('q31')"></span>
                            <div class="min-w-0 flex-1">
                                <!-- Bookmark Button -->
                                <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                    @click.stop="toggleFlag(31)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(31)
                                            ? 'border-gray-400 bg-transparent text-red-500'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        " :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="4" v-html="getHighlightedSegmentById(4)"></span>
                                    <input v-model="answers.q31" type="text" spellcheck="false" autocomplete="off"
                                        @input="(e) => handleInput('q31', e, 2)"
                                        class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                        placeholder="31" maxlength="20" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Question 32 -->
                        <div id="question-32" class="relative flex items-start gap-2 p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 32" @mouseleave="hoveredQuestion = null">
                            <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q32"
                                v-html="getHighlightedSegmentById('q32')"></span>
                            <div class="min-w-0 flex-1">
                                <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                    @click.stop="toggleFlag(32)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(32)
                                            ? 'border-gray-400 bg-transparent text-red-500'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        " :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                    <input v-model="answers.q32" type="text" spellcheck="false" autocomplete="off"
                                        @input="(e) => handleInput('q32', e, 2)"
                                        class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                        placeholder="32" maxlength="20" />
                                </div>
                            </div>
                        </div>

                        <!-- Question 33 -->
                        <div id="question-33" class="relative flex items-start gap-2 p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 33" @mouseleave="hoveredQuestion = null">
                            <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q33"
                                v-html="getHighlightedSegmentById('q33')"></span>
                            <div class="min-w-0 flex-1">
                                <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                    @click.stop="toggleFlag(33)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(33)
                                            ? 'border-gray-400 bg-transparent text-red-500'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        " :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                    <input v-model="answers.q33" type="text" spellcheck="false" autocomplete="off"
                                        @input="(e) => handleInput('q33', e, 2)"
                                        class="question-input mx-1 min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                        placeholder="33" maxlength="20" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 34-36: MCQ -->
                        <div class="border-t pt-2">
                            <div class="px-2">
                                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="9"
                                    v-html="getHighlightedSegmentById(9)"></p>
                                <p class="text-sm text-gray-700 select-text" data-segment-id="10"
                                    v-html="getHighlightedSegmentById(10)"></p>
                            </div>

                            <!-- Question 34 -->
                            <div id="question-34" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 34"
                                @mouseleave="hoveredQuestion = null">
                                <div class="flex items-start gap-2">
                                    <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q34"
                                        v-html="getHighlightedSegmentById('q34')"></span>
                                    <div class="min-w-0 flex-1">
                                        <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                            @click.stop="toggleFlag(34)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(34)
                                                    ? 'border-gray-400 bg-transparent text-red-500'
                                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                "
                                            :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="11"
                                                v-html="getHighlightedSegmentById(11)"></span>
                                        </h4>
                                        <div class="space-y-1">
                                            <label
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q34" value="A"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q34" value="B"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q34" value="C"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 35 -->
                            <div id="question-35" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 35"
                                @mouseleave="hoveredQuestion = null">
                                <div class="flex items-start gap-2">
                                    <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q35"
                                        v-html="getHighlightedSegmentById('q35')"></span>
                                    <div class="min-w-0 flex-1">
                                        <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                            @click.stop="toggleFlag(35)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(35)
                                                    ? 'border-gray-400 bg-transparent text-red-500'
                                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                "
                                            :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="15"
                                                v-html="getHighlightedSegmentById(15)"></span>
                                        </h4>
                                        <div class="space-y-1">
                                            <label
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q35" value="A"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q35" value="B"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q35" value="C"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 36 -->
                            <div id="question-36" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 36"
                                @mouseleave="hoveredQuestion = null">
                                <div class="flex items-start gap-2">
                                    <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q36"
                                        v-html="getHighlightedSegmentById('q36')"></span>
                                    <div class="min-w-0 flex-1">
                                        <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                            @click.stop="toggleFlag(36)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(36)
                                                    ? 'border-gray-400 bg-transparent text-red-500'
                                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                "
                                            :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="19"
                                                v-html="getHighlightedSegmentById(19)"></span>
                                        </h4>
                                        <div class="space-y-1">
                                            <label
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q36" value="A"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q36" value="B"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q36" value="C"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 37-40: Table Completion (ONE WORD ONLY) - Fixed UI to match image -->
                        <div class="border-t pt-2">
                            <div class="px-2">
                                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q37-40"
                                    v-html="getHighlightedSegmentById('q37-40')"></p>
                                <p class="text-sm text-gray-700 select-text" data-segment-id="23"
                                    v-html="getHighlightedSegmentById(23)"></p>
                                <p class="text-sm font-bold text-gray-700 select-text" data-segment-id="24"
                                    v-html="getHighlightedSegmentById(24)"></p>
                                <h3 class="mt-2 text-lg font-bold text-gray-900 select-text" data-segment-id="25"
                                    v-html="getHighlightedSegmentById(25)"></h3>
                            </div>

                            <!-- Table with border -->
                            <div class="mt-2 overflow-x-auto border border-gray-300">
                                <table class="w-full border-collapse">
                                    <!-- Table Header -->
                                    <thead>
                                        <tr class="border-b border-gray-300 bg-gray-50">
                                            <th class="border-r border-gray-300 px-4 py-2 text-left text-sm font-bold text-gray-900 select-text"
                                                data-segment-id="26" v-html="getHighlightedSegmentById(26)"></th>
                                            <th class="px-4 py-2 text-left text-sm font-bold text-gray-900 select-text"
                                                data-segment-id="27" v-html="getHighlightedSegmentById(27)"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Row 1: Early May -->
                                        <tr class="border-b border-gray-300">
                                            <td class="border-r border-gray-300 px-4 py-2 align-top text-sm text-gray-900 select-text"
                                                data-segment-id="28" v-html="getHighlightedSegmentById(28)"></td>
                                            <td class="px-4 py-2 text-sm text-gray-900"
                                                @mouseenter="hoveredQuestion = 37"
                                                @mouseleave="hoveredQuestion = null">
                                                <div class="flex flex-wrap items-center gap-0.5">
                                                    <span class="select-text" data-segment-id="29"
                                                        v-html="getHighlightedSegmentById(29)"></span>
                                                    <span id="question-37" class="inline-flex items-center gap-0.5">
                                                        <input v-model="answers.q37" type="text" spellcheck="false"
                                                            autocomplete="off" @input="(e) => handleInput('q37', e, 1)"
                                                            class="question-input mx-1 min-w-[60px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                            placeholder="37" maxlength="20" />
                                                    </span>
                                                    <span class="select-text" data-segment-id="30"
                                                        v-html="getHighlightedSegmentById(30)"></span>
                                                    <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                        @click.stop="toggleFlag(37)"
                                                        class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(37)
                                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                            "
                                                        :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Row 2: Late May -->
                                        <tr class="border-b border-gray-300">
                                            <td class="border-r border-gray-300 px-4 py-2 align-top text-sm text-gray-900 select-text"
                                                data-segment-id="31" v-html="getHighlightedSegmentById(31)"></td>
                                            <td class="px-4 py-2 text-sm text-gray-900"
                                                @mouseenter="hoveredQuestion = 38"
                                                @mouseleave="hoveredQuestion = null">
                                                <div class="flex flex-wrap items-center gap-0.5">
                                                    <span class="select-text" data-segment-id="32"
                                                        v-html="getHighlightedSegmentById(32)"></span>
                                                    <span id="question-38" class="inline-flex items-center gap-0.5">
                                                        <input v-model="answers.q38" type="text" spellcheck="false"
                                                            autocomplete="off" @input="(e) => handleInput('q38', e, 1)"
                                                            class="question-input mx-1 min-w-[60px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                            placeholder="38" maxlength="20" />
                                                    </span>
                                                    <span class="select-text" data-segment-id="33"
                                                        v-html="getHighlightedSegmentById(33)"></span>
                                                    <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                        @click.stop="toggleFlag(38)"
                                                        class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(38)
                                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                            "
                                                        :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Row 3: June -->
                                        <tr class="border-b border-gray-300">
                                            <td class="border-r border-gray-300 px-4 py-2 align-top text-sm text-gray-900 select-text"
                                                data-segment-id="34" v-html="getHighlightedSegmentById(34)"></td>
                                            <td class="px-4 py-2 text-sm text-gray-900 select-text" data-segment-id="35"
                                                v-html="getHighlightedSegmentById(35)"></td>
                                        </tr>
                                        <!-- Row 4: September -->
                                        <tr class="border-b border-gray-300">
                                            <td class="border-r border-gray-300 px-4 py-2 align-top text-sm text-gray-900 select-text"
                                                data-segment-id="36" v-html="getHighlightedSegmentById(36)"></td>
                                            <td class="px-4 py-2 text-sm text-gray-900"
                                                @mouseenter="hoveredQuestion = 39"
                                                @mouseleave="hoveredQuestion = null">
                                                <div class="select-text" data-segment-id="37"
                                                    v-html="getHighlightedSegmentById(37)"></div>
                                                <div class="mt-1 flex flex-wrap items-center gap-0.5">
                                                    <span class="select-text" data-segment-id="38"
                                                        v-html="getHighlightedSegmentById(38)"></span>
                                                    <span id="question-39" class="inline-flex items-center gap-0.5">
                                                        <input v-model="answers.q39" type="text" spellcheck="false"
                                                            autocomplete="off" @input="(e) => handleInput('q39', e, 1)"
                                                            class="question-input mx-1 min-w-[60px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                            placeholder="39" maxlength="20" />
                                                    </span>
                                                    <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                                        @click.stop="toggleFlag(39)"
                                                        class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(39)
                                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                            "
                                                        :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-4 w-4" fill="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                <div class="mt-1 select-text" data-segment-id="39"
                                                    v-html="getHighlightedSegmentById(39)"></div>
                                            </td>
                                        </tr>
                                        <!-- Row 5: December -->
                                        <tr class="border-b border-gray-300">
                                            <td class="border-r border-gray-300 px-4 py-2 align-top text-sm text-gray-900 select-text"
                                                data-segment-id="40" v-html="getHighlightedSegmentById(40)"></td>
                                            <td class="px-4 py-2 text-sm text-gray-900 select-text" data-segment-id="41"
                                                v-html="getHighlightedSegmentById(41)"></td>
                                        </tr>
                                        <!-- Row 6: January -->
                                        <tr>
                                            <td class="border-r border-gray-300 px-4 py-2 align-top text-sm text-gray-900 select-text"
                                                data-segment-id="42" v-html="getHighlightedSegmentById(42)"></td>
                                            <td class="px-4 py-2 text-sm text-gray-900"
                                                @mouseenter="hoveredQuestion = 40"
                                                @mouseleave="hoveredQuestion = null">
                                                <div class="flex flex-wrap items-center gap-0.5">
                                                    <span class="select-text" data-segment-id="43"
                                                        v-html="getHighlightedSegmentById(43)"></span>
                                                    <span id="question-40" class="inline-flex items-center gap-0.5">
                                                        <input v-model="answers.q40" type="text" spellcheck="false"
                                                            autocomplete="off" @input="(e) => handleInput('q40', e, 1)"
                                                            class="question-input mx-1 min-w-[60px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                            placeholder="40" maxlength="20" />
                                                    </span>
                                                    <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                                        @click.stop="toggleFlag(40)"
                                                        class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(40)
                                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                            "
                                                        :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Highlight Tooltips Component -->
    <HighlightTooltips :show-context-menu="showContextMenu" :context-menu-position="contextMenuPosition"
        :show-delete-tooltip="showDeleteTooltip" :delete-tooltip-position="deleteTooltipPosition"
        :show-note-tooltip="showNoteTooltip" :note-tooltip-position="noteTooltipPosition"
        :hovered-note-text="hoveredNoteText" :show-note-input="showNoteInput" :note-input-position="noteInputPosition"
        :selected-text="selectedText" :note-input-text="noteInputText" @open-note-input="openNoteInput"
        @apply-highlight="applyHighlight" @confirm-delete-highlight="confirmDeleteHighlight"
        @close-delete-tooltip="closeDeleteTooltip" @delete-note-from-tooltip="deleteNoteFromTooltip"
        @handle-tooltip-mouse-leave="handleTooltipMouseLeave" @save-note="saveNote" @cancel-note="cancelNote"
        @update:note-input-text="noteInputText = $event" />
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
