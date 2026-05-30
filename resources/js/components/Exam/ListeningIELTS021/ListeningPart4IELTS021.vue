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
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

const textSegments = ref([
    // Part header
    { id: 'part4-title', text: 'Part 4', offset: 0 },
    { id: 'part4-desc', text: 'Listen and answer questions 31–40.', offset: 7 },

    // Section header
    { id: 'sec-heading', text: 'Questions 31–40', offset: 42 },
    { id: 'sec-inst1', text: 'Complete the notes below.', offset: 58 },
    { id: 'sec-inst2', text: 'Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.', offset: 84 },

    // Main heading
    { id: 'main-heading', text: 'Climate change', offset: 114 },

    // HUMAN FACTORS section
    { id: 'hf-heading', text: 'HUMAN FACTORS', offset: 129 },
    { id: 'hf-cutting', text: 'Cutting down trees for', offset: 143 },
    { id: 'hf-ind-rev', text: 'Industrial Revolution', offset: 179 },
    { id: 'hf-q32-suffix', text: '', offset: 201 },
    { id: 'hf-pop', text: 'Increase in population', offset: 202 },
    { id: 'hf-defor', text: 'deforestation', offset: 227 },

    // KNOWN EFFECTS section
    { id: 'ke-heading', text: 'KNOWN EFFECTS', offset: 241 },
    { id: 'ke-temp', text: 'Over previous 130 yrs: temp. ↑ by 0.6°C', offset: 255 },
    { id: 'ke-co2', text: 'Since Ind. Rev.: CO2 ↑ by 30% & Methane ↑ by', offset: 296 },
    { id: 'ke-methane-src', text: '(from mining, animals, rice paddies)', offset: 343 },
    { id: 'ke-n2o', text: 'N2O ↑ (from', offset: 380 },
    { id: 'ke-n2o-suffix', text: 'esp. fertiliser; waste management; car exhausts)', offset: 392 },
    { id: 'ke-greenhouse', text: 'Greenhouse Effect: gases form', offset: 441 },
    { id: 'ke-greenhouse-suffix', text: '→ heat trapped → Earth warms up', offset: 471 },

    // FUTURE EFFECTS section
    { id: 'fe-heading', text: 'FUTURE EFFECTS', offset: 503 },
    { id: 'fe-sea', text: 'Rise in sea levels ← ice melting', offset: 518 },
    { id: 'fe-change', text: 'Change in', offset: 550 },
    { id: 'fe-arid', text: '→ more arid areas → population movement to cities', offset: 561 },
    { id: 'fe-pests', text: 'Increase in pests and', offset: 612 },
    { id: 'fe-malaria', text: 'e.g. malaria', offset: 634 },
    { id: 'fe-eco', text: 'Change in ecosystems:', offset: 647 },
    { id: 'fe-shift', text: '★ shift in', offset: 669 },
    { id: 'fe-shift-suffix', text: 'some die, others multiply', offset: 680 },
    { id: 'fe-deserts', text: '★ deserts get hotter & bigger', offset: 706 },
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
    return { ...answers.value };
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
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
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

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto pb-24">
                <div class="p-2 sm:p-3">
                    <div @mouseup="handleContentTextSelect" class="p-3 select-text sm:p-4">
                        <div class="space-y-4">

                            <!-- Section Header -->
                            <div>
                                <p
                                    class="text-lg font-bold text-gray-900"
                                    data-segment-id="sec-heading"
                                    v-html="getHighlightedSegmentById('sec-heading')"
                                ></p>
                                <p class="text-base text-gray-700 mt-1">
                                    <span data-segment-id="sec-inst1" v-html="getHighlightedSegmentById('sec-inst1')"></span>
                                </p>
                                <p class="text-base text-gray-700">
                                    <span data-segment-id="sec-inst2" v-html="getHighlightedSegmentById('sec-inst2')"></span>
                                </p>
                            </div>

                            <!-- Notes Box -->
                            <div class="">

                                <!-- Main Title -->
                                <div class="bg-gray-100  px-4 py-2 text-center">
                                    <span
                                        class="font-bold text-gray-900 text-base"
                                        data-segment-id="main-heading"
                                        v-html="getHighlightedSegmentById('main-heading')"
                                    ></span>
                                </div>

                                <div class="px-5 py-4 space-y-5">

                                    <!-- ===== HUMAN FACTORS ===== -->
                                    <div>
                                        <p
                                            class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3 select-text"
                                            data-segment-id="hf-heading"
                                            v-html="getHighlightedSegmentById('hf-heading')"
                                        ></p>

                                        <ul class="space-y-2 text-sm text-gray-800 list-none pl-2">

                                            <!-- Cutting down trees for Q31 -->
                                            <li class="flex flex-wrap items-center gap-1.5">
                                                <span data-segment-id="hf-cutting" v-html="getHighlightedSegmentById('hf-cutting')"></span>
                                                <span
                                                    id="question-31"
                                                    class="inline-flex items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 31"
                                                    @mouseleave="hoveredQuestion = null"
                                                >
                                                    <input
                                                        v-model="answers.q31"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[150px]"
                                                        placeholder="31"
                                                        @focus="hoveredQuestion = 31"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                    <button
                                                        @click.stop="toggleFlag(31)"
                                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                        :class="[
                                                            isQuestionFlagged(31) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                            (hoveredQuestion === 31 || isQuestionFlagged(31)) ? 'visible' : 'invisible'
                                                        ]"
                                                        :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </li>

                                            <!-- Industrial Revolution -->
                                            <li>
                                                <span data-segment-id="hf-ind-rev" v-html="getHighlightedSegmentById('hf-ind-rev')"></span>
                                            </li>

                                            <!-- Q32 standalone -->
                                            <li
                                                id="question-32"
                                                class="flex flex-wrap items-center gap-1.5"
                                                @mouseenter="hoveredQuestion = 32"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="inline-flex items-center gap-1">
                                                    <input
                                                        v-model="answers.q32"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[150px]"
                                                        placeholder="32"
                                                        @focus="hoveredQuestion = 32"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                    <button
                                                        @click.stop="toggleFlag(32)"
                                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                        :class="[
                                                            isQuestionFlagged(32) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                            (hoveredQuestion === 32 || isQuestionFlagged(32)) ? 'visible' : 'invisible'
                                                        ]"
                                                        :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </li>

                                            <!-- Increase in population → deforestation -->
                                            <li class="flex flex-wrap items-center gap-1.5">
                                                <span data-segment-id="hf-pop" v-html="getHighlightedSegmentById('hf-pop')"></span>
                                                <span class="text-gray-500">→</span>
                                                <span data-segment-id="hf-defor" v-html="getHighlightedSegmentById('hf-defor')"></span>
                                            </li>
                                        </ul>
                                    </div>



                                    <!-- ===== KNOWN EFFECTS ===== -->
                                    <div>
                                        <p
                                            class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3 select-text"
                                            data-segment-id="ke-heading"
                                            v-html="getHighlightedSegmentById('ke-heading')"
                                        ></p>

                                        <ul class="space-y-2 text-sm text-gray-800 list-none pl-2">

                                            <!-- Over prev 130 yrs -->
                                            <li>
                                                <span data-segment-id="ke-temp" v-html="getHighlightedSegmentById('ke-temp')"></span>
                                            </li>

                                            <!-- CO2 + Q33 (Methane % value) -->
                                            <li
                                                class="flex flex-wrap items-center gap-1.5"
                                                @mouseenter="hoveredQuestion = 33"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span data-segment-id="ke-co2" v-html="getHighlightedSegmentById('ke-co2')"></span>
                                                <span id="question-33" class="inline-flex items-center">
                                                    <input
                                                        v-model="answers.q33"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[80px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[100px]"
                                                        placeholder="33"
                                                        @focus="hoveredQuestion = 33"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                </span>
                                                <span data-segment-id="ke-methane-src" v-html="getHighlightedSegmentById('ke-methane-src')"></span>
                                                <button
                                                    @click.stop="toggleFlag(33)"
                                                    class="ml-auto flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                    :class="[
                                                        isQuestionFlagged(33) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                        (hoveredQuestion === 33 || isQuestionFlagged(33)) ? 'visible' : 'invisible'
                                                    ]"
                                                    :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </li>

                                            <!-- N2O + Q34 -->
                                            <li
                                                class="flex flex-wrap items-center gap-1.5"
                                                @mouseenter="hoveredQuestion = 34"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span data-segment-id="ke-n2o" v-html="getHighlightedSegmentById('ke-n2o')"></span>
                                                <span id="question-34" class="inline-flex items-center">
                                                    <input
                                                        v-model="answers.q34"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[100px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[130px]"
                                                        placeholder="34"
                                                        @focus="hoveredQuestion = 34"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                </span>
                                                <span data-segment-id="ke-n2o-suffix" v-html="getHighlightedSegmentById('ke-n2o-suffix')"></span>
                                                <button
                                                    @click.stop="toggleFlag(34)"
                                                    class="ml-auto flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                    :class="[
                                                        isQuestionFlagged(34) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                        (hoveredQuestion === 34 || isQuestionFlagged(34)) ? 'visible' : 'invisible'
                                                    ]"
                                                    :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </li>

                                            <!-- Greenhouse Effect + Q35 -->
                                            <li
                                                class="flex flex-wrap items-center gap-1.5"
                                                @mouseenter="hoveredQuestion = 35"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span data-segment-id="ke-greenhouse" v-html="getHighlightedSegmentById('ke-greenhouse')"></span>
                                                <span id="question-35" class="inline-flex items-center">
                                                    <input
                                                        v-model="answers.q35"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[150px]"
                                                        placeholder="35"
                                                        @focus="hoveredQuestion = 35"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                </span>
                                                <span data-segment-id="ke-greenhouse-suffix" v-html="getHighlightedSegmentById('ke-greenhouse-suffix')"></span>
                                                <button
                                                    @click.stop="toggleFlag(35)"
                                                    class="ml-auto flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                    :class="[
                                                        isQuestionFlagged(35) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                        (hoveredQuestion === 35 || isQuestionFlagged(35)) ? 'visible' : 'invisible'
                                                    ]"
                                                    :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </li>

                                            <!-- Q36 standalone -->
                                            <li
                                                id="question-36"
                                                class="flex flex-wrap items-center gap-1.5"
                                                @mouseenter="hoveredQuestion = 36"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="inline-flex items-center gap-1">
                                                    <input
                                                        v-model="answers.q36"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[150px]"
                                                        placeholder="36"
                                                        @focus="hoveredQuestion = 36"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                    <button
                                                        @click.stop="toggleFlag(36)"
                                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                        :class="[
                                                            isQuestionFlagged(36) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                            (hoveredQuestion === 36 || isQuestionFlagged(36)) ? 'visible' : 'invisible'
                                                        ]"
                                                        :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </li>

                                            <!-- Q37 standalone -->
                                            <li
                                                id="question-37"
                                                class="flex flex-wrap items-center gap-1.5"
                                                @mouseenter="hoveredQuestion = 37"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="inline-flex items-center gap-1">
                                                    <input
                                                        v-model="answers.q37"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[150px]"
                                                        placeholder="37"
                                                        @focus="hoveredQuestion = 37"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                    <button
                                                        @click.stop="toggleFlag(37)"
                                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                        :class="[
                                                            isQuestionFlagged(37) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                            (hoveredQuestion === 37 || isQuestionFlagged(37)) ? 'visible' : 'invisible'
                                                        ]"
                                                        :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>



                                    <!-- ===== FUTURE EFFECTS ===== -->
                                    <div>
                                        <p
                                            class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3 select-text"
                                            data-segment-id="fe-heading"
                                            v-html="getHighlightedSegmentById('fe-heading')"
                                        ></p>

                                        <ul class="space-y-2 text-sm text-gray-800 list-none pl-2">

                                            <!-- Rise in sea levels -->
                                            <li>
                                                <span data-segment-id="fe-sea" v-html="getHighlightedSegmentById('fe-sea')"></span>
                                            </li>

                                            <!-- Change in Q38 → more arid areas -->
                                            <li
                                                class="flex flex-wrap items-center gap-1.5"
                                                @mouseenter="hoveredQuestion = 38"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span data-segment-id="fe-change" v-html="getHighlightedSegmentById('fe-change')"></span>
                                                <span id="question-38" class="inline-flex items-center">
                                                    <input
                                                        v-model="answers.q38"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[150px]"
                                                        placeholder="38"
                                                        @focus="hoveredQuestion = 38"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                </span>
                                                <span data-segment-id="fe-arid" v-html="getHighlightedSegmentById('fe-arid')"></span>
                                                <button
                                                    @click.stop="toggleFlag(38)"
                                                    class="ml-auto flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                    :class="[
                                                        isQuestionFlagged(38) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                        (hoveredQuestion === 38 || isQuestionFlagged(38)) ? 'visible' : 'invisible'
                                                    ]"
                                                    :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </li>

                                            <!-- Increase in pests and Q39 -->
                                            <li
                                                class="flex flex-wrap items-center gap-1.5"
                                                @mouseenter="hoveredQuestion = 39"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span data-segment-id="fe-pests" v-html="getHighlightedSegmentById('fe-pests')"></span>
                                                <span id="question-39" class="inline-flex items-center">
                                                    <input
                                                        v-model="answers.q39"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[150px]"
                                                        placeholder="39"
                                                        @focus="hoveredQuestion = 39"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                </span>
                                                <span data-segment-id="fe-malaria" v-html="getHighlightedSegmentById('fe-malaria')"></span>
                                                <button
                                                    @click.stop="toggleFlag(39)"
                                                    class="ml-auto flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                    :class="[
                                                        isQuestionFlagged(39) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                        (hoveredQuestion === 39 || isQuestionFlagged(39)) ? 'visible' : 'invisible'
                                                    ]"
                                                    :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </li>

                                            <!-- Change in ecosystems -->
                                            <li>
                                                <span data-segment-id="fe-eco" v-html="getHighlightedSegmentById('fe-eco')"></span>
                                            </li>

                                            <!-- ★ shift in Q40 -->
                                            <li
                                                class="flex flex-wrap items-center gap-1.5 pl-4"
                                                @mouseenter="hoveredQuestion = 40"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span data-segment-id="fe-shift" v-html="getHighlightedSegmentById('fe-shift')"></span>
                                                <span id="question-40" class="inline-flex items-center">
                                                    <input
                                                        v-model="answers.q40"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input min-w-[120px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[150px]"
                                                        placeholder="40"
                                                        @focus="hoveredQuestion = 40"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                </span>
                                                <span data-segment-id="fe-shift-suffix" v-html="getHighlightedSegmentById('fe-shift-suffix')"></span>
                                                <button
                                                    @click.stop="toggleFlag(40)"
                                                    class="ml-auto flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                    :class="[
                                                        isQuestionFlagged(40) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                        (hoveredQuestion === 40 || isQuestionFlagged(40)) ? 'visible' : 'invisible'
                                                    ]"
                                                    :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </li>

                                            <!-- ★ deserts get hotter -->
                                            <li class="pl-4">
                                                <span data-segment-id="fe-deserts" v-html="getHighlightedSegmentById('fe-deserts')"></span>
                                            </li>

                                        </ul>
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
