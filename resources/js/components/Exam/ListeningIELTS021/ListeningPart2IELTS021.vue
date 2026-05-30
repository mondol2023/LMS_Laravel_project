<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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

// Section 2 answers
// Q11–Q15: single letter A/B/C
const mcAnswers = ref<Record<string, string>>({
    q11: '',
    q12: '',
    q13: '',
    q14: '',
    q15: '',
});

// Q16–Q20: text fill-in
const answers = ref({
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
});

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Delete highlight tooltip
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note tooltip (hover)
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// Text segments
const textSegments = ref([
    // Part header
    { id: 'part2-title', text: 'Part 2', offset: 0 },
    { id: 'part2-desc', text: 'Listen and answer questions 11–20.', offset: 7 },

    // Section 2 MCQ heading
    { id: 'sec2-heading', text: 'Questions 11–15', offset: 43 },
    { id: 'sec2-inst', text: 'Choose the correct letter A, B or C.', offset: 59 },

    // Q11
    { id: 'q11-label', text: '11.  The guided bushwalk is suitable for', offset: 96 },
    { id: 'q11-A', text: 'adults only', offset: 137 },
    { id: 'q11-B', text: 'children over 12 and adults', offset: 149 },
    { id: 'q11-C', text: 'children over 8 accompanied by a parent', offset: 177 },

    // Q12
    { id: 'q12-label', text: '12.  On the bird observation outing, it is recommended that you have', offset: 217 },
    { id: 'q12-A', text: 'waterproof footwear', offset: 285 },
    { id: 'q12-B', text: 'a bird identification book', offset: 305 },
    { id: 'q12-C', text: 'binoculars', offset: 332 },

    // Q13
    { id: 'q13-label', text: '13.  For the trip to the sand dunes, a company will donate', offset: 343 },
    { id: 'q13-A', text: 'water', offset: 401 },
    { id: 'q13-B', text: 'tools', offset: 407 },
    { id: 'q13-C', text: 'gloves', offset: 413 },

    // Q14
    { id: 'q14-label', text: '14.  The bush tucker excursion will cost (per person)', offset: 420 },
    { id: 'q14-A', text: '$15', offset: 473 },
    { id: 'q14-B', text: '$12', offset: 477 },
    { id: 'q14-C', text: '$7', offset: 481 },

    // Q15
    { id: 'q15-label', text: '15.  The deadline to register for the bush tucker outing is', offset: 484 },
    { id: 'q15-A', text: '25 November', offset: 543 },
    { id: 'q15-B', text: '15 November', offset: 555 },
    { id: 'q15-C', text: '10 November', offset: 567 },

    // Section table heading
    { id: 'sec3-heading', text: 'Questions 16–20', offset: 579 },
    { id: 'sec3-inst1', text: 'Complete the table below.', offset: 595 },
    { id: 'sec3-inst2', text: 'Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer', offset: 621 },

    // Table headers
    { id: 'th-activity', text: 'Activity', offset: 682 },
    { id: 'th-leader', text: 'Leader', offset: 691 },
    { id: 'th-date', text: 'Date', offset: 698 },
    { id: 'th-venue', text: 'Venue', offset: 703 },
    { id: 'th-time', text: 'Time', offset: 709 },

    // Row 1 - Bush walk
    { id: 'r1-activity', text: 'Bush walk', offset: 714 },
    { id: 'r1-leader', text: 'Glenn Ford', offset: 724 },
    { id: 'r1-venue', text: 'Springvale', offset: 749 },
    { id: 'r1-time-suffix', text: '–1pm', offset: 772 },

    // Row 2 - Bird watching
    { id: 'r2-activity', text: 'Bird watching', offset: 777 },
    { id: 'r2-leader', text: 'Joy Black, club', offset: 791 },
    { id: 'r2-date', text: '10 September', offset: 821 },
    { id: 'r2-venue', text: 'Camford', offset: 834 },
    { id: 'r2-time', text: '4.30–6.30pm', offset: 842 },

    // Row 3 - Sand dunes
    { id: 'r3-activity', text: 'Sand dunes', offset: 854 },
    { id: 'r3-leader', text: 'Rex Rose', offset: 865 },
    { id: 'r3-date', text: '26 November', offset: 874 },
    { id: 'r3-time-suffix', text: '8.30–10.30am', offset: 899 },

    // Row 4 - Bush tucker
    { id: 'r4-activity', text: 'Bush tucker', offset: 912 },
    { id: 'r4-leader', text: 'Jim Kerr, ranger', offset: 924 },
    { id: 'r4-date', text: '3 December', offset: 941 },
    { id: 'r4-venue', text: 'Carson Hills', offset: 952 },
    { id: 'r4-time-prefix', text: '10am–', offset: 965 },
]);

// Helper to get a highlighted version of a specific text segment by ID
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

// Expose methods for parent component
const getAnswers = () => {
    return {
        ...mcAnswers.value,
        ...answers.value,
    };
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            showContextMenu.value = false;
            return;
        }

        const selected = selection.toString().trim();
        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) {
                container = container.parentNode as Node;
            }
            const segmentEl = (container as Element).closest('[data-segment-id]');

            if (!segmentEl) return null;

            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) return null;

            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let currentNode;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) {
                    offsetInSegment += offsetInNode;
                    break;
                } else {
                    offsetInSegment += currentNode.textContent?.length || 0;
                }
            }

            return segment.offset + offsetInSegment;
        };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset = getAbsoluteOffset(range.endContainer, range.endOffset);

        if (startAbsOffset === null || endAbsOffset === null) {
            showContextMenu.value = false;
            window.getSelection()?.removeAllRanges();
            return;
        }

        if (startAbsOffset > endAbsOffset) {
            [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];
        }

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 50;
            const menuY = rect.top - menuHeight - 8;

            const viewportWidth = window.innerWidth;
            const menuWidth = 140;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            selectedText.value = selection.toString();
            selectionRange.value = { start: startAbsOffset, end: endAbsOffset };
        } else {
            showContextMenu.value = false;
        }
    }, 10);
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

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;

    const modalWidth = 320;
    const modalHeight = 180;
    const padding = 10;

    const selection = window.getSelection();
    let x: number;
    let y: number;

    if (selection && selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const rect = range.getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    } else {
        x = contextMenuPosition.value.x;
        y = contextMenuPosition.value.y + 70;
    }

    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    const halfWidth = modalWidth / 2;
    if (x - halfWidth < padding) {
        x = halfWidth + padding;
    } else if (x + halfWidth > viewportWidth - padding) {
        x = viewportWidth - halfWidth - padding;
    }

    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) y = padding;
    }

    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;

    setTimeout(() => {
        const input = document.querySelector<HTMLInputElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;

    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));

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

const cancelNote = () => {
    noteInputText.value = '';
    showNoteInput.value = false;
};

const deleteNote = (id: number) => {
    notes.value = notes.value.filter((note) => note.id !== id);
};

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
};

const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        const highlightId = highlightMark.getAttribute('data-highlight-id');
        if (highlightId) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = {
                x: rect.left + rect.width / 2,
                y: rect.bottom + 8,
            };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
            showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        deleteHighlight(idToDelete);
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const noteMark = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;

    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note = notes.value.find((n) => n.id === noteIdNum);

            if (note) {
                const rect = noteMark.getBoundingClientRect();
                const tooltipHeight = 50;
                let y = rect.top - tooltipHeight - 8;
                if (y < 10) y = rect.bottom + 8;

                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value = noteIdNum;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const relatedTarget = event.relatedTarget as HTMLElement;

    if (relatedTarget?.closest('.note-hover-tooltip')) return;

    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

onMounted(async () => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mouseover', handleNoteMouseEnter);
    document.removeEventListener('mouseout', handleNoteMouseLeave);
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
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box -->
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part2-desc"
                    v-html="getHighlightedSegmentById('part2-desc')"
                ></p>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto pb-24">
                <div class="p-2 sm:p-3">
                    <div @mouseup="handleContentTextSelect" class="p-3 select-text sm:p-4">
                        <div class="space-y-6">

                            <!-- ===== SECTION 2: MCQ Q11–Q15 ===== -->
                            <div>
                                <p
                                    class="text-lg font-bold text-gray-900"
                                    data-segment-id="sec2-heading"
                                    v-html="getHighlightedSegmentById('sec2-heading')"
                                ></p>
                                <p class="text-base text-gray-700 mt-1">
                                    <span data-segment-id="sec2-inst" v-html="getHighlightedSegmentById('sec2-inst')"></span>
                                </p>

                                <div class="mt-3 space-y-5">

                                    <!-- Q11 -->
                                    <div
                                        id="question-11"
                                        class="relative p-2 sm:p-3"
                                        @mouseenter="hoveredQuestion = 11"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="toggleFlag(11)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <p
                                            class="text-base text-gray-800 font-medium mb-2 select-text"
                                            data-segment-id="q11-label"
                                            v-html="getHighlightedSegmentById('q11-label')"
                                        ></p>
                                        <div class="space-y-1.5">
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q11" value="A" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">A &nbsp;<span data-segment-id="q11-A" v-html="getHighlightedSegmentById('q11-A')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q11" value="B" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">B &nbsp;<span data-segment-id="q11-B" v-html="getHighlightedSegmentById('q11-B')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q11" value="C" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">C &nbsp;<span data-segment-id="q11-C" v-html="getHighlightedSegmentById('q11-C')"></span></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q12 -->
                                    <div
                                        id="question-12"
                                        class="relative p-2 sm:p-3"
                                        @mouseenter="hoveredQuestion = 12"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <p
                                            class="text-base text-gray-800 font-medium mb-2 select-text"
                                            data-segment-id="q12-label"
                                            v-html="getHighlightedSegmentById('q12-label')"
                                        ></p>
                                        <div class="space-y-1.5">
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q12" value="A" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">A &nbsp;<span data-segment-id="q12-A" v-html="getHighlightedSegmentById('q12-A')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q12" value="B" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">B &nbsp;<span data-segment-id="q12-B" v-html="getHighlightedSegmentById('q12-B')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q12" value="C" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">C &nbsp;<span data-segment-id="q12-C" v-html="getHighlightedSegmentById('q12-C')"></span></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q13 -->
                                    <div
                                        id="question-13"
                                        class="relative p-2 sm:p-3"
                                        @mouseenter="hoveredQuestion = 13"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <p
                                            class="text-base text-gray-800 font-medium mb-2 select-text"
                                            data-segment-id="q13-label"
                                            v-html="getHighlightedSegmentById('q13-label')"
                                        ></p>
                                        <div class="space-y-1.5">
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q13" value="A" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">A &nbsp;<span data-segment-id="q13-A" v-html="getHighlightedSegmentById('q13-A')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q13" value="B" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">B &nbsp;<span data-segment-id="q13-B" v-html="getHighlightedSegmentById('q13-B')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q13" value="C" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">C &nbsp;<span data-segment-id="q13-C" v-html="getHighlightedSegmentById('q13-C')"></span></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q14 -->
                                    <div
                                        id="question-14"
                                        class="relative p-2 sm:p-3"
                                        @mouseenter="hoveredQuestion = 14"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <p
                                            class="text-base text-gray-800 font-medium mb-2 select-text"
                                            data-segment-id="q14-label"
                                            v-html="getHighlightedSegmentById('q14-label')"
                                        ></p>
                                        <div class="space-y-1.5">
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q14" value="A" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">A &nbsp;<span data-segment-id="q14-A" v-html="getHighlightedSegmentById('q14-A')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q14" value="B" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">B &nbsp;<span data-segment-id="q14-B" v-html="getHighlightedSegmentById('q14-B')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q14" value="C" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">C &nbsp;<span data-segment-id="q14-C" v-html="getHighlightedSegmentById('q14-C')"></span></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q15 -->
                                    <div
                                        id="question-15"
                                        class="relative p-2 sm:p-3"
                                        @mouseenter="hoveredQuestion = 15"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <button
                                            v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="toggleFlag(15)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <p
                                            class="text-base text-gray-800 font-medium mb-2 select-text"
                                            data-segment-id="q15-label"
                                            v-html="getHighlightedSegmentById('q15-label')"
                                        ></p>
                                        <div class="space-y-1.5">
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q15" value="A" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">A &nbsp;<span data-segment-id="q15-A" v-html="getHighlightedSegmentById('q15-A')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q15" value="B" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">B &nbsp;<span data-segment-id="q15-B" v-html="getHighlightedSegmentById('q15-B')"></span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded p-1.5 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="mcAnswers.q15" value="C" class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text">C &nbsp;<span data-segment-id="q15-C" v-html="getHighlightedSegmentById('q15-C')"></span></span>
                                            </label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- ===== SECTION 3: Table Completion Q16–Q20 ===== -->
                            <div class="border-t pt-4">
                                <p
                                    class="text-lg font-bold text-gray-900"
                                    data-segment-id="sec3-heading"
                                    v-html="getHighlightedSegmentById('sec3-heading')"
                                ></p>
                                <p class="text-base text-gray-700 mt-1">
                                    <span data-segment-id="sec3-inst1" v-html="getHighlightedSegmentById('sec3-inst1')"></span>
                                </p>
                                <p class="text-base text-gray-700">
                                    <span data-segment-id="sec3-inst2" v-html="getHighlightedSegmentById('sec3-inst2')"></span>
                                </p>

                                <!-- Table -->
                                <div class="mt-3 overflow-x-auto">
                                    <table class="w-full border-collapse border border-gray-300 text-base">
                                        <thead>
                                            <tr class="bg-gray-100">
                                                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-900">
                                                    <span data-segment-id="th-activity" v-html="getHighlightedSegmentById('th-activity')"></span>
                                                </th>
                                                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-900">
                                                    <span data-segment-id="th-leader" v-html="getHighlightedSegmentById('th-leader')"></span>
                                                </th>
                                                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-900">
                                                    <span data-segment-id="th-date" v-html="getHighlightedSegmentById('th-date')"></span>
                                                </th>
                                                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-900">
                                                    <span data-segment-id="th-venue" v-html="getHighlightedSegmentById('th-venue')"></span>
                                                </th>
                                                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-900">
                                                    <span data-segment-id="th-time" v-html="getHighlightedSegmentById('th-time')"></span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Row 1: Bush walk — Q16 (Date), Q17 (Time start) -->
                                            <tr class="hover:bg-gray-50">
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r1-activity" v-html="getHighlightedSegmentById('r1-activity')"></span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r1-leader" v-html="getHighlightedSegmentById('r1-leader')"></span>
                                                </td>
                                                <!-- Q16: Date -->
                                                <td class="border border-gray-300 px-3 py-2.5">
                                                    <span
                                                        id="question-16"
                                                        class="inline-flex items-center gap-1"
                                                        @mouseenter="hoveredQuestion = 16"
                                                        @mouseleave="hoveredQuestion = null"
                                                    >
                                                        <input
                                                            v-model="answers.q16"
                                                            type="text"
                                                            spellcheck="false"
                                                            autocomplete="off"
                                                            class="question-input min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-base focus:border-black focus:outline-none"
                                                            placeholder="16"
                                                            @focus="hoveredQuestion = 16"
                                                            @blur="hoveredQuestion = null"
                                                        />
                                                        <button
                                                            @click.stop="toggleFlag(16)"
                                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                            :class="[
                                                                isQuestionFlagged(16) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                (hoveredQuestion === 16 || isQuestionFlagged(16)) ? 'visible' : 'invisible'
                                                            ]"
                                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r1-venue" v-html="getHighlightedSegmentById('r1-venue')"></span>
                                                </td>
                                                <!-- Q17: Time start -->
                                                <td class="border border-gray-300 px-3 py-2.5">
                                                    <span
                                                        id="question-17"
                                                        class="inline-flex items-center gap-1"
                                                        @mouseenter="hoveredQuestion = 17"
                                                        @mouseleave="hoveredQuestion = null"
                                                    >
                                                        <input
                                                            v-model="answers.q17"
                                                            type="text"
                                                            spellcheck="false"
                                                            autocomplete="off"
                                                            class="question-input min-w-[70px] border border-gray-900 px-1.5 py-0.5 text-center text-base focus:border-black focus:outline-none"
                                                            placeholder="17"
                                                            @focus="hoveredQuestion = 17"
                                                            @blur="hoveredQuestion = null"
                                                        />
                                                        <span class="text-base text-gray-800" data-segment-id="r1-time-suffix" v-html="getHighlightedSegmentById('r1-time-suffix')"></span>
                                                        <button
                                                            @click.stop="toggleFlag(17)"
                                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                            :class="[
                                                                isQuestionFlagged(17) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                (hoveredQuestion === 17 || isQuestionFlagged(17)) ? 'visible' : 'invisible'
                                                            ]"
                                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </span>
                                                </td>
                                            </tr>

                                            <!-- Row 2: Bird watching — Q18 (Leader suffix) -->
                                            <tr class="hover:bg-gray-50">
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r2-activity" v-html="getHighlightedSegmentById('r2-activity')"></span>
                                                </td>
                                                <!-- Q18: leader suffix -->
                                                <td class="border border-gray-300 px-3 py-2.5">
                                                    <div
                                                        id="question-18"
                                                        class="flex flex-wrap items-center gap-1"
                                                        @mouseenter="hoveredQuestion = 18"
                                                        @mouseleave="hoveredQuestion = null"
                                                    >
                                                        <span class="text-base text-gray-800" data-segment-id="r2-leader" v-html="getHighlightedSegmentById('r2-leader')"></span>
                                                        <input
                                                            v-model="answers.q18"
                                                            type="text"
                                                            spellcheck="false"
                                                            autocomplete="off"
                                                            class="question-input min-w-[70px] border border-gray-900 px-1.5 py-0.5 text-center text-base focus:border-black focus:outline-none"
                                                            placeholder="18"
                                                            @focus="hoveredQuestion = 18"
                                                            @blur="hoveredQuestion = null"
                                                        />
                                                        <button
                                                            @click.stop="toggleFlag(18)"
                                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                            :class="[
                                                                isQuestionFlagged(18) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                (hoveredQuestion === 18 || isQuestionFlagged(18)) ? 'visible' : 'invisible'
                                                            ]"
                                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r2-date" v-html="getHighlightedSegmentById('r2-date')"></span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r2-venue" v-html="getHighlightedSegmentById('r2-venue')"></span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r2-time" v-html="getHighlightedSegmentById('r2-time')"></span>
                                                </td>
                                            </tr>

                                            <!-- Row 3: Sand dunes — Q19 (Venue) -->
                                            <tr class="hover:bg-gray-50">
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r3-activity" v-html="getHighlightedSegmentById('r3-activity')"></span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r3-leader" v-html="getHighlightedSegmentById('r3-leader')"></span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r3-date" v-html="getHighlightedSegmentById('r3-date')"></span>
                                                </td>
                                                <!-- Q19: Venue -->
                                                <td class="border border-gray-300 px-3 py-2.5">
                                                    <span
                                                        id="question-19"
                                                        class="inline-flex items-center gap-1"
                                                        @mouseenter="hoveredQuestion = 19"
                                                        @mouseleave="hoveredQuestion = null"
                                                    >
                                                        <input
                                                            v-model="answers.q19"
                                                            type="text"
                                                            spellcheck="false"
                                                            autocomplete="off"
                                                            class="question-input min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-base focus:border-black focus:outline-none"
                                                            placeholder="19"
                                                            @focus="hoveredQuestion = 19"
                                                            @blur="hoveredQuestion = null"
                                                        />
                                                        <button
                                                            @click.stop="toggleFlag(19)"
                                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                            :class="[
                                                                isQuestionFlagged(19) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                (hoveredQuestion === 19 || isQuestionFlagged(19)) ? 'visible' : 'invisible'
                                                            ]"
                                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r3-time-suffix" v-html="getHighlightedSegmentById('r3-time-suffix')"></span>
                                                </td>
                                            </tr>

                                            <!-- Row 4: Bush tucker — Q20 (Time end) -->
                                            <tr class="hover:bg-gray-50">
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r4-activity" v-html="getHighlightedSegmentById('r4-activity')"></span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r4-leader" v-html="getHighlightedSegmentById('r4-leader')"></span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r4-date" v-html="getHighlightedSegmentById('r4-date')"></span>
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2.5 text-gray-800">
                                                    <span data-segment-id="r4-venue" v-html="getHighlightedSegmentById('r4-venue')"></span>
                                                </td>
                                                <!-- Q20: Time end -->
                                                <td class="border border-gray-300 px-3 py-2.5">
                                                    <span
                                                        id="question-20"
                                                        class="inline-flex items-center gap-1"
                                                        @mouseenter="hoveredQuestion = 20"
                                                        @mouseleave="hoveredQuestion = null"
                                                    >
                                                        <span class="text-base text-gray-800" data-segment-id="r4-time-prefix" v-html="getHighlightedSegmentById('r4-time-prefix')"></span>
                                                        <input
                                                            v-model="answers.q20"
                                                            type="text"
                                                            spellcheck="false"
                                                            autocomplete="off"
                                                            class="question-input min-w-[70px] border border-gray-900 px-1.5 py-0.5 text-center text-base focus:border-black focus:outline-none"
                                                            placeholder="20"
                                                            @focus="hoveredQuestion = 20"
                                                            @blur="hoveredQuestion = null"
                                                        />
                                                        <button
                                                            @click.stop="toggleFlag(20)"
                                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                            :class="[
                                                                isQuestionFlagged(20) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                                (hoveredQuestion === 20 || isQuestionFlagged(20)) ? 'visible' : 'invisible'
                                                            ]"
                                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </span>
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
    </div>

    <!-- Context Menu for Highlighting - Speech Bubble Style -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-9999"
                :style="{
                    left: `${contextMenuPosition.x}px`,
                    top: `${contextMenuPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button
                        @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note"
                    >
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button
                        @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                        title="Highlight"
                    >
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-9999"
                :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button
                        @click="confirmDeleteHighlight"
                        type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100"
                    >
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                        <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                        <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Note Hover Tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-9998">
            <div
                class="note-hover-tooltip pointer-events-auto fixed z-9999"
                :style="{
                    left: `${noteTooltipPosition.x}px`,
                    top: `${noteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @mouseleave="handleTooltipMouseLeave"
                @click.stop
            >
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm wrap-break-word text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div
            v-if="showNoteInput"
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{
                left: `${noteInputPosition.x}px`,
                top: `${noteInputPosition.y}px`,
                transform: 'translateX(-50%)',
            }"
            @click.stop
        >
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input
                    v-model="noteInputText"
                    type="text"
                    spellcheck="false"
                    autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote"
                    @keyup.escape="cancelNote"
                />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 p-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote" class="bg-black px-3 p-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
            </div>
        </div>
    </Teleport>
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
    0% { background-color: rgba(0, 0, 0, 0.15); }
    100% { background-color: rgba(0, 0, 0, 0.08); }
}

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #000000; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #374151; }

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

.highlight-nocolor { background-color: transparent; }
.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }

.highlight-tooltip .tooltip-arrow {
    position: absolute;
    left: 50%;
    bottom: -8px;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

.highlight-tooltip .tooltip-arrow::before {
    content: '';
    position: absolute;
    left: -9px;
    bottom: 1px;
    width: 0;
    height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}

.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0, 0, 0, 0.1));
}

.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: '';
    position: absolute;
    left: -9px;
    top: 1px;
    width: 0;
    height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db;
    z-index: -1;
}

.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer;
}

.note-hover-tooltip .tooltip-arrow-down {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

.note-hover-tooltip .tooltip-arrow-down::before {
    content: '';
    position: absolute;
    left: -9px;
    bottom: 1px;
    width: 0;
    height: 0;
    border-left: 9px solid transparent;
    border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db;
    z-index: -1;
}

.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
.note-hover-tooltip .note-tooltip-icon { color: #6b7280; }
.note-hover-tooltip .note-tooltip-text { line-height: 1.4; }
.note-hover-tooltip .note-delete-btn { color: #9ca3af; }
.note-hover-tooltip .note-delete-btn:hover { color: #ef4444; }

[data-theme='white-on-black'] .note-hover-tooltip .note-tooltip-content { background: #1f2937 !important; border-color: #4b5563 !important; }
[data-theme='white-on-black'] .note-hover-tooltip .tooltip-arrow-down { border-top-color: #1f2937 !important; }
[data-theme='white-on-black'] .note-hover-tooltip .tooltip-arrow-down::before { border-top-color: #4b5563 !important; }
[data-theme='white-on-black'] .note-hover-tooltip .note-tooltip-icon { color: #9ca3af !important; }
[data-theme='white-on-black'] .note-hover-tooltip .note-tooltip-text { color: white !important; }
[data-theme='white-on-black'] .note-hover-tooltip .note-delete-btn { color: #9ca3af !important; }
[data-theme='white-on-black'] .note-hover-tooltip .note-delete-btn:hover { color: #ef4444 !important; background: #374151 !important; }

[data-theme='yellow-on-black'] .note-hover-tooltip .note-tooltip-content { background: #1f2937 !important; border-color: #4b5563 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .tooltip-arrow-down { border-top-color: #1f2937 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .tooltip-arrow-down::before { border-top-color: #4b5563 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .note-tooltip-icon { color: #facc15 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .note-tooltip-text { color: #facc15 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .note-delete-btn { color: #facc15 !important; }
[data-theme='yellow-on-black'] .note-hover-tooltip .note-delete-btn:hover { color: #ef4444 !important; background: #374151 !important; }

.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>
