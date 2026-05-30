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

// Listening Section 1 - House Rental Call
const answers = ref({
    q1: '',
    q2: '',
    q3: '',
    q4: '',
    q5: '',
    q6: '',
    q7: '',
    q8: '',
    q9: '',
    q10: '',
});

const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

const textSegments = ref([
    // Part header
    { id: 'part1-title', text: 'SECTION 1', offset: 0 },
    { id: 'part1-desc', text: 'Listen and answer questions 1–10.', offset: 10 },
    // Instructions
    { id: 0, text: 'Questions 1–10', offset: 44 },
    { id: 1, text: 'Complete the table below. Write ', offset: 58 },
    { id: 2, text: 'ONE WORD AND/OR A NUMBER', offset: 90 },
    { id: 3, text: ' for each answer.', offset: 114 },
    // Title
    { id: 4, text: 'Rented Properties', offset: 132 },
    // Available date
    { id: 6, text: 'Available date', offset: 152 },
    // Prices
    { id: 7, text: 'Prices:', offset: 168 },
    { id: 8, text: 'Rent:', offset: 176 },
    { id: 9, text: 'per month', offset: 196 },
    { id: 10, text: 'Deposit: $1500', offset: 206 },
    { id: 11, text: '$15', offset: 221 },
    // Facilities
    { id: 12, text: 'Facilities:', offset: 226 },
    { id: 13, text: '3 bedroom and 2 bathrooms', offset: 238 },
    { id: 14, text: 'A remodeled', offset: 264 },
    { id: 15, text: 'No', offset: 284 },
    { id: 16, text: 'A', offset: 296 },
    { id: 17, text: 'with a work area', offset: 298 },
    // Utilities
    { id: 18, text: 'Utilities:', offset: 316 },
    { id: 19, text: 'The landlord will provide landscaping service, but the tenants must', offset: 327 },
    { id: 20, text: 'the grass', offset: 395 },
    { id: 21, text: 'The tenants should pay $15 for tracing and', offset: 406 },
    { id: 22, text: 'service', offset: 449 },
    { id: 23, text: 'The tenants should pay for electricity water and gas bills', offset: 458 },
    // Other Information
    { id: 24, text: 'Other Information:', offset: 518 },
    { id: 25, text: 'There is no central air conditions, but there is a', offset: 538 },
    { id: 26, text: 'conditioning unit.', offset: 589 },
    // Name of landlord
    { id: 27, text: 'Name of landlord: Sam', offset: 610 },
]);

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
    return answers.value;
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

    if (x - halfWidth < padding) x = halfWidth + padding;
    else if (x + halfWidth > viewportWidth - padding) x = viewportWidth - halfWidth - padding;

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
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box -->
            <div class="mb-3 rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part1-title" v-html="getHighlightedSegmentById('part1-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part1-desc" v-html="getHighlightedSegmentById('part1-desc')"></p>
            </div>

            <!-- Instructions -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="0" v-html="getHighlightedSegmentById(0)"></p>
                <p class="text-sm text-gray-700 select-text">
                    <span data-segment-id="1" v-html="getHighlightedSegmentById(1)"></span>
                    <span class="font-bold" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></span>
                    <span data-segment-id="3" v-html="getHighlightedSegmentById(3)"></span>
                </p>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto pb-24">
                <div class="p-2 sm:p-3">
                    <div @mouseup="handleContentTextSelect" class="p-3 select-text sm:p-4">
                        <div class="space-y-4">
                            <div>
                                <h3 class="mb-4 text-lg font-bold text-gray-900" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></h3>

                                <div class="space-y-3 text-base leading-relaxed text-gray-800">

                                    <!-- Available date -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="font-bold text-gray-800" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>:
                                        <span
                                            id="question-1"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 1"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q1"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="1"
                                                @focus="hoveredQuestion = 1"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                                                @click.stop="toggleFlag(1)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(1) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                    </div>

                                    <!-- Prices -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="font-bold text-gray-800" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                        <span class="text-gray-700">$</span>
                                        <span
                                            id="question-2"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 2"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q2"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="2"
                                                @focus="hoveredQuestion = 2"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                                                @click.stop="toggleFlag(2)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(2) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-800" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span
                                            id="question-3"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 3"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q3"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="3"
                                                @focus="hoveredQuestion = 3"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                                                @click.stop="toggleFlag(3)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(3) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-800" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                    </div>

                                    <!-- Facilities -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="font-bold text-gray-800" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                        <span
                                            id="question-4"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 4"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q4"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="4"
                                                @focus="hoveredQuestion = 4"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                                @click.stop="toggleFlag(4)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(4) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                        <span
                                            id="question-5"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 5"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q5"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="5"
                                                @focus="hoveredQuestion = 5"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                                @click.stop="toggleFlag(5)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                        <span
                                            id="question-6"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 6"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q6"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="6"
                                                @focus="hoveredQuestion = 6"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                                @click.stop="toggleFlag(6)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-800" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                    </div>

                                    <!-- Utilities -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="font-bold text-gray-800" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                        <span
                                            id="question-7"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 7"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q7"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="7"
                                                @focus="hoveredQuestion = 7"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                                @click.stop="toggleFlag(7)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-800" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                                        <span
                                            id="question-8"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 8"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q8"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="8"
                                                @focus="hoveredQuestion = 8"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                                @click.stop="toggleFlag(8)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-800" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                    </div>

                                    <!-- Other Information -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="font-bold text-gray-800" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1 pl-4">
                                        <span class="text-gray-800" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                        <span
                                            id="question-9"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 9"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q9"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="9"
                                                @focus="hoveredQuestion = 9"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                                @click.stop="toggleFlag(9)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </span>
                                        <span class="text-gray-800" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                                    </div>

                                    <!-- Name of landlord -->
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="font-bold text-gray-800" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></span>
                                        <span
                                            id="question-10"
                                            class="group relative inline-flex items-center gap-1"
                                            @mouseenter="hoveredQuestion = 10"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <input
                                                v-model="answers.q10"
                                                type="text"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="question-input mx-1 min-w-[80px] border border-gray-900 px-2 py-1 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                placeholder="10"
                                                @focus="hoveredQuestion = 10"
                                                @blur="hoveredQuestion = null"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                                @click.stop="toggleFlag(10)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
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

    <!-- Context Menu for Highlighting -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-9999"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
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
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
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
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
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
                <button
                    @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 p-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
                >
                    Cancel
                </button>
                <button @click="saveNote" class="bg-black px-3 p-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                    Save Note
                </button>
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
