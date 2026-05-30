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

const hoveredQuestion = ref<number | null>(null);

const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Map label answers Q11–17
const answers = ref<Record<string, string>>({
    q11: '',
    q12: '',
    q13: '',
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
});

// Multiple choice answers for questions 19-20
const multipleAnswers = ref<{ q19_20: string[] }>({
    q19_20: [],
});

const mapOptions = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const notes = ref<Note[]>([]);

const textSegments = ref<TextSegment[]>([
    // Part header
    { id: 'part2-title', text: 'Part 2', offset: 0 },
    { id: 'part2-desc', text: 'Questions 11–20', offset: 10 },
    // Instructions Q11-17
    { id: 0, text: 'Questions 11–17', offset: 26 },
    { id: 1, text: 'Label the plan below.', offset: 42 },
    { id: 2, text: 'Write the correct letter, A–J, next to questions 11–17.', offset: 64 },
    // Map title
    { id: 3, text: 'SPORTS SUPER CENTRE', offset: 120 },
    // Question labels
    { id: 'q11-label', text: '11', offset: 140 },
    { id: 'q11-text', text: 'Administration office', offset: 143 },
    { id: 'q12-label', text: '12', offset: 165 },
    { id: 'q12-text', text: 'Sports medicine clinic', offset: 168 },
    { id: 'q13-label', text: '13', offset: 191 },
    { id: 'q13-text', text: 'Bike racks', offset: 194 },
    { id: 'q14-label', text: '14', offset: 205 },
    { id: 'q14-text', text: 'Café', offset: 208 },
    { id: 'q15-label', text: '15', offset: 213 },
    { id: 'q15-text', text: 'Conference room', offset: 216 },
    { id: 'q16-label', text: '16', offset: 232 },
    { id: 'q16-text', text: "Men's locker room", offset: 235 },
    { id: 'q17-label', text: '17', offset: 253 },
    { id: 'q17-text', text: 'Pool shop', offset: 256 },
    // Q18 instruction
    { id: 4, text: 'Question 18', offset: 266 },
    { id: 5, text: 'Choose the correct letter, A, B or C.', offset: 278 },
    { id: 'q18-label', text: '18', offset: 316 },
    { id: 6, text: 'The sports centre is open on public holidays from', offset: 319 },
    { id: 7, text: '7 a.m. to 5 p.m.', offset: 368 },
    { id: 8, text: '5 a.m. to 7 p.m.', offset: 386 },
    { id: 9, text: '5 a.m. to 9 p.m.', offset: 404 },
    // Q19-20
    { id: 'q19-20', text: 'Questions 19 and 20', offset: 422 },
    { id: 'q19-20-inst', text: 'Choose TWO letters, A–E.', offset: 442 },
    { id: 10, text: 'Which TWO services are covered by the membership fee?', offset: 467 },
    { id: 11, text: 'Personal training', offset: 520 },
    { id: 12, text: 'Swim squads', offset: 538 },
    { id: 13, text: 'Childminding', offset: 550 },
    { id: 14, text: 'Programme design', offset: 563 },
    { id: 15, text: 'Tennis lessons', offset: 580 },
]);

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

const getHighlightedSegmentById = (segmentId: number | string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return plainText;
    }

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

const getAnswers = () => {
    const allAnswers = { ...answers.value };

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
        currentAnswers.splice(index, 1);
    } else if (currentAnswers.length < 2) {
        currentAnswers.push(option);
    }
};

const isSelected = (questionGroup: string, option: string) => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
};

const isDisabled = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    return currentAnswers.length >= 2 && !currentAnswers.includes(option);
};

const handleContentTextSelect = () => {
    tooltipHandleContentTextSelect();
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

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

    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

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
    let targetId = `question-${questionNumber}`;
    if (questionNumber === 19 || questionNumber === 20) {
        targetId = 'question-19-20';
    }

    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="tooltipHandleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="mb-3 rounded border border-gray-200 part-header-box  px-4 py-3">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part2-desc"
                    v-html="getHighlightedSegmentById('part2-desc')"></p>
            </div>

            <!-- Instructions Q11-17 -->
            <div class="shrink-0 px-2 pb-2 sm:px-3">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)">
                </p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)">
                </p>
            </div>

            <!-- Scrollable Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">

                    <!-- Map Title -->
                    <h2 class="mb-3 text-center text-base font-bold text-gray-900 select-text" data-segment-id="3"
                        v-html="getHighlightedSegmentById(3)"></h2>

                    <!-- Map + Questions 11-17 side by side -->
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:gap-6">

                        <!-- Left: Map Image -->
                        <div
                            class="w-1/2 flex items-start justify-center rounded border border-dashed border-gray-300 bg-gray-50 p-4">
                            <img src="/images/listening/IELTS016.png" alt="Sports Super Centre Plan"
                                class="max-w-full h-auto" />
                        </div>

                        <!-- Questions 11-17 with select dropdowns -->
                        <div class="flex-1 lg:w-1/2">
                            <div class="space-y-2">

                                <!-- Q11 -->
                                <div id="question-11" class="relative flex items-center gap-2 rounded p-2"
                                    @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-2 ">
                                        <span class="w-6 shrink-0 text-base font-bold text-gray-900 select-text"
                                            data-segment-id="q11-label"
                                            v-html="getHighlightedSegmentById('q11-label')"></span>
                                        <span class="flex-1 text-base text-gray-900 select-text"
                                            data-segment-id="q11-text"
                                            v-html="getHighlightedSegmentById('q11-text')"></span>
                                        <select v-model="answers.q11"
                                            class="map-select w-25 ml-4 shrink-0 border border-gray-900 bg-white px-1 py-0.5 text-sm font-bold text-gray-900 focus:border-black focus:outline-none">
                                            <option value="" disabled>select</option>
                                            <option v-for="opt in mapOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </div>
                                    <div class="w-7 h-7 shrink-0">
                                        <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="toggleFlag(11)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Q12 -->
                                <div id="question-12" class="relative flex items-center gap-2 rounded p-2"
                                    @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-2 ">
                                        <span class="w-6 shrink-0 text-base font-bold text-gray-900 select-text"
                                            data-segment-id="q12-label"
                                            v-html="getHighlightedSegmentById('q12-label')"></span>
                                        <span class="flex-1 text-base text-gray-900 select-text"
                                            data-segment-id="q12-text"
                                            v-html="getHighlightedSegmentById('q12-text')"></span>
                                        <select v-model="answers.q12"
                                            class="map-select w-25 ml-4 shrink-0 border border-gray-900 bg-white px-1 py-0.5 text-sm font-bold text-gray-900 focus:border-black focus:outline-none">
                                            <option value="" disabled>select</option>
                                            <option v-for="opt in mapOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </div>
                                    <div class="w-7 h-7 shrink-0">
                                        <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Q13 -->
                                <div id="question-13" class="relative flex items-center gap-2 rounded p-2"
                                    @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-2 ">
                                        <span class="w-6 shrink-0 text-base font-bold text-gray-900 select-text"
                                            data-segment-id="q13-label"
                                            v-html="getHighlightedSegmentById('q13-label')"></span>
                                        <span class="flex-1 text-base text-gray-900 select-text"
                                            data-segment-id="q13-text"
                                            v-html="getHighlightedSegmentById('q13-text')"></span>
                                        <select v-model="answers.q13"
                                            class="map-select w-25 ml-4 shrink-0 border border-gray-900 bg-white px-1 py-0.5 text-sm font-bold text-gray-900 focus:border-black focus:outline-none">
                                            <option value="" disabled>select</option>
                                            <option v-for="opt in mapOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </div>
                                    <div class="w-7 h-7 shrink-0">
                                        <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Q14 -->
                                <div id="question-14" class="relative flex items-center gap-2 rounded p-2"
                                    @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-2 ">
                                        <span class="w-6 shrink-0 text-base font-bold text-gray-900 select-text"
                                            data-segment-id="q14-label"
                                            v-html="getHighlightedSegmentById('q14-label')"></span>
                                        <span class="flex-1 text-base text-gray-900 select-text"
                                            data-segment-id="q14-text"
                                            v-html="getHighlightedSegmentById('q14-text')"></span>
                                        <select v-model="answers.q14"
                                            class="map-select w-25 ml-4 shrink-0 border border-gray-900 bg-white px-1 py-0.5 text-sm font-bold text-gray-900 focus:border-black focus:outline-none">
                                            <option value="" disabled>select</option>
                                            <option v-for="opt in mapOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </div>
                                    <div class="w-7 h-7 shrink-0">
                                        <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Q15 -->
                                <div id="question-15" class="relative flex items-center gap-2 rounded p-2"
                                    @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-2 ">
                                        <span class="w-6 shrink-0 text-base font-bold text-gray-900 select-text"
                                            data-segment-id="q15-label"
                                            v-html="getHighlightedSegmentById('q15-label')"></span>
                                        <span class="flex-1 text-base text-gray-900 select-text"
                                            data-segment-id="q15-text"
                                            v-html="getHighlightedSegmentById('q15-text')"></span>
                                        <select v-model="answers.q15"
                                            class="map-select w-25 ml-4 shrink-0 border border-gray-900 bg-white px-1 py-0.5 text-sm font-bold text-gray-900 focus:border-black focus:outline-none">
                                            <option value="" disabled>select</option>
                                            <option v-for="opt in mapOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </div>
                                    <div class="w-7 h-7 shrink-0">
                                        <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="toggleFlag(15)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Q16 -->
                                <div id="question-16" class="relative flex items-center gap-2 rounded p-2"
                                    @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-2 ">
                                        <span class="w-6 shrink-0 text-base font-bold text-gray-900 select-text"
                                            data-segment-id="q16-label"
                                            v-html="getHighlightedSegmentById('q16-label')"></span>
                                        <span class="flex-1 text-base text-gray-900 select-text"
                                            data-segment-id="q16-text"
                                            v-html="getHighlightedSegmentById('q16-text')"></span>
                                        <select v-model="answers.q16"
                                            class="map-select w-25 ml-4 shrink-0 border border-gray-900 bg-white px-1 py-0.5 text-sm font-bold text-gray-900 focus:border-black focus:outline-none">
                                            <option value="" disabled>select</option>
                                            <option v-for="opt in mapOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </div>
                                    <div class="w-7 h-7 shrink-0">
                                        <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="toggleFlag(16)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Q17 -->
                                <div id="question-17" class="relative flex items-center gap-2 rounded p-2"
                                    @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-center gap-2 ">
                                        <span class="w-6 shrink-0 text-base font-bold text-gray-900 select-text"
                                            data-segment-id="q17-label"
                                            v-html="getHighlightedSegmentById('q17-label')"></span>
                                        <span class="flex-1 text-base text-gray-900 select-text"
                                            data-segment-id="q17-text"
                                            v-html="getHighlightedSegmentById('q17-text')"></span>
                                        <select v-model="answers.q17"
                                            class="map-select w-25 ml-4 shrink-0 border border-gray-900 bg-white px-1 py-0.5 text-sm font-bold text-gray-900 focus:border-black focus:outline-none">
                                            <option value="" disabled>select</option>
                                            <option v-for="opt in mapOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </div>
                                    <div class="w-7 h-7 shrink-0">
                                        <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="toggleFlag(17)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>


                    <!-- Question 18: Single MCQ -->
                    <div class="mb-4 my-5">
                        <div class="mb-2 px-2">
                            <p class="text-base font-bold italic text-gray-900 select-text" data-segment-id="4"
                                v-html="getHighlightedSegmentById(4)"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="5"
                                v-html="getHighlightedSegmentById(5)"></p>
                        </div>

                        <div id="question-18" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 18"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q18-label"
                                    v-html="getHighlightedSegmentById('q18-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                        @click.stop="toggleFlag(18)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="6"
                                            v-html="getHighlightedSegmentById(6)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q18" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                <strong class="font-bold">A. </strong>
                                                <span data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q18" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                <strong class="font-bold">B. </strong>
                                                <span data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q18" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                <strong class="font-bold">C. </strong>
                                                <span data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Questions 19-20: Choose TWO -->
                    <div id="question-19-20" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 19"
                        @mouseleave="hoveredQuestion = null">
                        <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19) || isQuestionFlagged(20)"
                            @click.stop="toggleFlag(19); toggleFlag(20);"
                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="isQuestionFlagged(19) || isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                            :title="isQuestionFlagged(19) || isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                            </svg>
                        </button>
                        <div class="mb-2">
                            <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q19-20"
                                v-html="getHighlightedSegmentById('q19-20')"></h3>
                            <div class="p-2">
                                <p class="mb-1 text-sm text-gray-700 select-text" data-segment-id="q19-20-inst"
                                    v-html="getHighlightedSegmentById('q19-20-inst')"></p>
                                <p class="text-base font-bold leading-tight text-gray-900 select-text"
                                    data-segment-id="10" v-html="getHighlightedSegmentById(10)"></p>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label
                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="checkbox" :checked="isSelected('q19_20', 'A')"
                                    :disabled="isDisabled('q19_20', 'A')" @change="handleMultipleChoice('q19_20', 'A')"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                <span class="text-base leading-relaxed text-gray-900 select-text"><span
                                        class="font-bold">A</span> <span data-segment-id="11"
                                        v-html="getHighlightedSegmentById(11)"></span></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="checkbox" :checked="isSelected('q19_20', 'B')"
                                    :disabled="isDisabled('q19_20', 'B')" @change="handleMultipleChoice('q19_20', 'B')"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                <span class="text-base leading-relaxed text-gray-900 select-text"><span
                                        class="font-bold">B</span> <span data-segment-id="12"
                                        v-html="getHighlightedSegmentById(12)"></span></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="checkbox" :checked="isSelected('q19_20', 'C')"
                                    :disabled="isDisabled('q19_20', 'C')" @change="handleMultipleChoice('q19_20', 'C')"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                <span class="text-base leading-relaxed text-gray-900 select-text"><span
                                        class="font-bold">C</span> <span data-segment-id="13"
                                        v-html="getHighlightedSegmentById(13)"></span></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="checkbox" :checked="isSelected('q19_20', 'D')"
                                    :disabled="isDisabled('q19_20', 'D')" @change="handleMultipleChoice('q19_20', 'D')"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                <span class="text-base leading-relaxed text-gray-900 select-text"><span
                                        class="font-bold">D</span> <span data-segment-id="14"
                                        v-html="getHighlightedSegmentById(14)"></span></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                <input type="checkbox" :checked="isSelected('q19_20', 'E')"
                                    :disabled="isDisabled('q19_20', 'E')" @change="handleMultipleChoice('q19_20', 'E')"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                <span class="text-base leading-relaxed text-gray-900 select-text"><span
                                        class="font-bold">E</span> <span data-segment-id="15"
                                        v-html="getHighlightedSegmentById(15)"></span></span>
                            </label>
                        </div>

                        <div class="mt-2 p-2">
                            <p class="text-sm font-medium text-gray-900">Selected: {{ multipleAnswers.q19_20.length }}/2
                                answers</p>
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

.part-header-box {
    background-color: #F1F2EC;
}

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

.map-select {
    appearance: auto;
    cursor: pointer;
}

.map-container {
    background-color: #fafafa;
}
</style>

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
