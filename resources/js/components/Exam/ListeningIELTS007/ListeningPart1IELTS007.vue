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
const isQuestionFlagged = (n: number) => props.flaggedQuestions.has(n);
const toggleFlag = (n: number) => emit('toggleFlag', n);

const contentZoom = computed(() => ({ zoom: props.fontSize / 16 }));

const answers = ref({
    q1: '', q2: '', q3: '',
    q4: '', q5: '', q6: '', q7: '', q8: '', q9: '', q10: '',
});

// ── Highlight / Note state ─────────────────────────────────────────────────
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
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// ── Text segments (matching the screenshot exactly) ────────────────────────
const textSegments = ref([
    { id: 'part1-title',            text: 'Part 1 ',                                              offset: 0 },
    { id: 'part1-instruction',      text: 'Listen and answer Questions 1-10.',                    offset: 7 },

    { id: 'q1-question',            text: '1. What is Martin\'s occupation?',                     offset: 40 },
    { id: 'q1-opt-a',               text: 'A he works in a car factory',                          offset: 71 },
    { id: 'q1-opt-b',               text: 'B he works in a bank',                                 offset: 98 },
    { id: 'q1-opt-c',               text: 'C he is a college student',                            offset: 118 },

    { id: 'q2-question',            text: '2. The friends would prefer somewhere with',           offset: 143 },
    { id: 'q2-opt-a',               text: 'A four bedrooms',                                      offset: 185 },
    { id: 'q2-opt-b',               text: 'B three bedrooms',                                     offset: 200 },
    { id: 'q2-opt-c',               text: 'C two bathrooms',                                      offset: 216 },

    { id: 'q3-question',            text: '3. Phil would rather live in',                         offset: 231 },
    { id: 'q3-opt-a',               text: 'A the east suburbs',                                   offset: 259 },
    { id: 'q3-opt-b',               text: 'B the city centre',                                    offset: 277 },
    { id: 'q3-opt-c',               text: 'C the west suburbs',                                   offset: 294 },

    { id: 'q4-10-title',            text: 'Questions 4-10',                                       offset: 312 },
    { id: 'q4-10-instruction1',     text: 'Complete the table below.',                            offset: 326 },
    { id: 'q4-10-instruction2',     text: 'Write NO MORE THAN THREE WORDS AND/ OR A NUMBER for each answer.', offset: 351 },

    { id: 'table-title',            text: 'Details of Flats Available',                           offset: 415 },

    { id: 'col-location',           text: 'Location',                                             offset: 441 },
    { id: 'col-features',           text: 'Features',                                             offset: 449 },
    { id: 'col-pros-cons',          text: 'Goods and bad points',                                 offset: 457 },

    // Row 1
    { id: 'row1-location-prefix',   text: 'Bridge Street, near the',                              offset: 477 },
    { id: 'row1-feature1',          text: '3 bedrooms',                                           offset: 500 },
    { id: 'row1-feature2',          text: 'very big living room',                                 offset: 510 },
    { id: 'row1-rent-prefix',       text: '£',                                                    offset: 530 },
    { id: 'row1-rent-suffix',       text: 'a month',                                              offset: 531 },
    { id: 'row1-good-point',        text: 'transport links',                                      offset: 538 },
    { id: 'row1-bad-point1',        text: 'no shower',                                            offset: 553 },
    { id: 'row1-bad-point2-prefix', text: 'could be',                                             offset: 562 },

    // Row 2
    { id: 'row2-location',          text: '',                                                     offset: 570 },
    { id: 'row2-feature1',          text: '4 bedrooms',                                           offset: 570 },
    { id: 'row2-feature2',          text: 'living room',                                          offset: 580 },
    { id: 'row2-feature3',          text: '',                                                     offset: 591 },
    { id: 'row2-good-point-prefix', text: '',                                                     offset: 591 },
    { id: 'row2-good-point-suffix', text: 'and well equipped',                                    offset: 591 },
    { id: 'row2-bad-point1',        text: 'shower',                                               offset: 608 },
    { id: 'row2-bad-point2-prefix', text: 'will be',                                              offset: 614 },
    { id: 'row2-bad-point2-suffix', text: '£ 800 a month',                                        offset: 621 },
]);

// ── Highlight helper ───────────────────────────────────────────────────────
const getHighlightedSegmentById = (segmentId: string): string => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset,
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset,
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return plainText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset, end: h.end_offset,
            type: 'highlight' as const, color: h.color, id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start, end: n.end,
            type: 'note' as const, color: 'blue', id: n.id,
        })),
    ].sort((a, b) => b.start - a.start);

    let result = plainText;
    for (const annotation of annotations) {
        const aStart = Math.max(0, annotation.start - segmentOffset);
        const aEnd = Math.min(plainText.length, annotation.end - segmentOffset);
        if (aStart < aEnd) {
            const before = result.substring(0, aStart);
            const annotated = result.substring(aStart, aEnd);
            const after = result.substring(aEnd);
            result = annotation.type === 'note'
                ? `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`
                : `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
        }
    }
    return result;
};

// ── Expose for parent ──────────────────────────────────────────────────────
const getAnswers = () => ({ ...answers.value });

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// ── Text selection ─────────────────────────────────────────────────────────
const getTextOffsetInElement = (element: Element, targetNode: Node, targetOffset: number): number => {
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null);
    let offset = 0;
    let node: Node | null;
    while ((node = walker.nextNode())) {
        if (node === targetNode) return offset + targetOffset;
        offset += (node.textContent || '').length;
    }
    return offset;
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && contentTextRef.value) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 58;
            const vw = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), vw - 80),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            if (selection && range) {
                let targetElement: Node | null = range.startContainer;
                while (targetElement && targetElement.nodeType !== Node.ELEMENT_NODE)
                    targetElement = targetElement.parentNode;
                while (targetElement && !(targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const parent = targetElement.parentNode;
                    if (!parent || parent === contentTextRef.value) break;
                    targetElement = parent;
                }
                if (targetElement && (targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const attr = (targetElement as Element).getAttribute('data-segment-id') || '';
                    const segment = textSegments.value.find((s) => s.id === attr);
                    if (segment) {
                        const start = segment.offset + getTextOffsetInElement(targetElement as Element, range.startContainer, range.startOffset);
                        selectedText.value = selected;
                        selectionRange.value = { start, end: start + selected.length };
                    }
                }
            }
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    const modalWidth = 320; const modalHeight = 180; const padding = 10;
    const selection = window.getSelection();
    let x = contextMenuPosition.value.x;
    let y = contextMenuPosition.value.y + 70;
    if (selection && selection.rangeCount > 0) {
        const r = selection.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2; y = r.bottom + 10;
    }
    const vw = window.innerWidth; const vh = window.innerHeight;
    const hw = modalWidth / 2;
    if (x - hw < padding) x = hw + padding;
    else if (x + hw > vw - padding) x = vw - hw - padding;
    if (y + modalHeight > vh - padding) {
        if (selection && selection.rangeCount > 0)
            y = selection.getRangeAt(0).getBoundingClientRect().top - modalHeight - 10;
        if (y < padding) y = padding;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start: newStart, end: newEnd } = selectionRange.value;
    findOverlappingHighlights(newStart, newEnd).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const highlightMark = (event.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement;
    if (highlightMark) {
        const id = highlightMark.getAttribute('data-highlight-id');
        if (id) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(id, 10);
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
        deleteHighlight(highlightToDelete.value);
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const noteMark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteId = noteMark.getAttribute('data-note-id');
        if (noteId) {
            const noteIdNum = parseInt(noteId, 10);
            const note = notes.value.find((n) => n.id === noteIdNum);
            if (note) {
                const rect = noteMark.getBoundingClientRect();
                let y = rect.top - 58;
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
    if ((event.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) return;
    if ((event.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mouseover', handleNoteMouseEnter);
    document.removeEventListener('mouseout', handleNoteMouseLeave);
});

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div
        ref="contentTextRef"
        @mouseup="handleContentTextSelect"
        @click="handleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6"
    >
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part1-title"
                    v-html="getHighlightedSegmentById('part1-title')"></h3>
                <p class="text-base text-gray-700 select-text" data-segment-id="part1-instruction"
                    v-html="getHighlightedSegmentById('part1-instruction')"></p>
            </div>

            <!-- ── Questions 1–3 (MCQ) ───────────────────────────────────────── -->
            <div class="mb-6 space-y-6 px-2">

                <!-- Q1 -->
                <div id="question-1" class="relative" @mouseenter="hoveredQuestion = 1" @mouseleave="hoveredQuestion = null">
                    <button
                        v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                        @click.stop="toggleFlag(1)"
                        class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(1) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                    <p class="mb-1 text-base font-medium text-gray-900 select-text pr-8" data-segment-id="q1-question"
                        v-html="getHighlightedSegmentById('q1-question')"></p>
                    <div class="space-y-1 pl-1">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" v-model="answers.q1" value="A" class="h-4 w-4 accent-black" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q1-opt-a"
                                v-html="getHighlightedSegmentById('q1-opt-a')"></span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" v-model="answers.q1" value="B" class="h-4 w-4 accent-black" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q1-opt-b"
                                v-html="getHighlightedSegmentById('q1-opt-b')"></span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" v-model="answers.q1" value="C" class="h-4 w-4 accent-black" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q1-opt-c"
                                v-html="getHighlightedSegmentById('q1-opt-c')"></span>
                        </label>
                    </div>
                </div>

                <!-- Q2 -->
                <div id="question-2" class="relative" @mouseenter="hoveredQuestion = 2" @mouseleave="hoveredQuestion = null">
                    <button
                        v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                        @click.stop="toggleFlag(2)"
                        class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(2) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                    <p class="mb-1 text-base font-medium text-gray-900 select-text pr-8" data-segment-id="q2-question"
                        v-html="getHighlightedSegmentById('q2-question')"></p>
                    <div class="space-y-1 pl-1">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" v-model="answers.q2" value="A" class="h-4 w-4 accent-black" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q2-opt-a"
                                v-html="getHighlightedSegmentById('q2-opt-a')"></span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" v-model="answers.q2" value="B" class="h-4 w-4 accent-black" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q2-opt-b"
                                v-html="getHighlightedSegmentById('q2-opt-b')"></span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" v-model="answers.q2" value="C" class="h-4 w-4 accent-black" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q2-opt-c"
                                v-html="getHighlightedSegmentById('q2-opt-c')"></span>
                        </label>
                    </div>
                </div>

                <!-- Q3 -->
                <div id="question-3" class="relative" @mouseenter="hoveredQuestion = 3" @mouseleave="hoveredQuestion = null">
                    <button
                        v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                        @click.stop="toggleFlag(3)"
                        class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(3) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                        </svg>
                    </button>
                    <p class="mb-1 text-base font-medium text-gray-900 select-text pr-8" data-segment-id="q3-question"
                        v-html="getHighlightedSegmentById('q3-question')"></p>
                    <div class="space-y-1 pl-1">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" v-model="answers.q3" value="A" class="h-4 w-4 accent-black" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q3-opt-a"
                                v-html="getHighlightedSegmentById('q3-opt-a')"></span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" v-model="answers.q3" value="B" class="h-4 w-4 accent-black" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q3-opt-b"
                                v-html="getHighlightedSegmentById('q3-opt-b')"></span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" v-model="answers.q3" value="C" class="h-4 w-4 accent-black" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q3-opt-c"
                                v-html="getHighlightedSegmentById('q3-opt-c')"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- ── Questions 4–10 (Table) ─────────────────────────────────────── -->
            <div class="mb-4 px-2">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q4-10-title"
                    v-html="getHighlightedSegmentById('q4-10-title')"></p>
                <p class="text-base text-gray-700 select-text" data-segment-id="q4-10-instruction1"
                    v-html="getHighlightedSegmentById('q4-10-instruction1')"></p>
                <p class="text-base text-gray-700 select-text mt-1" data-segment-id="q4-10-instruction2">
                    Write <strong>NO MORE THAN THREE WORDS AND/ OR A NUMBER</strong> for each answer.
                </p>
            </div>

            <!-- Table Title -->
            <p class="mb-3 text-center text-base font-bold text-gray-900 select-text" data-segment-id="table-title"
                v-html="getHighlightedSegmentById('table-title')"></p>

            <!-- Table -->
            <div class="overflow-x-auto px-2">
                <table class="w-full border-collapse border border-gray-300 text-base">
                    <thead>
                        <tr>
                            <th class="border border-gray-300 px-4 py-3 text-center font-bold text-gray-900 w-1/3 select-text"
                                data-segment-id="col-location"
                                v-html="getHighlightedSegmentById('col-location')"></th>
                            <th class="border border-gray-300 px-4 py-3 text-center font-bold text-gray-900 w-1/3 select-text"
                                data-segment-id="col-features"
                                v-html="getHighlightedSegmentById('col-features')"></th>
                            <th class="border border-gray-300 px-4 py-3 text-center font-bold text-gray-900 w-1/3 select-text"
                                data-segment-id="col-pros-cons"
                                v-html="getHighlightedSegmentById('col-pros-cons')"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- ── Row 1 ── -->
                        <tr class="align-top">
                            <!-- Location -->
                            <td class="border border-gray-300 px-4 py-5">
                                <div id="question-4" class="flex flex-wrap items-center gap-1"
                                    @mouseenter="hoveredQuestion = 4" @mouseleave="hoveredQuestion = null">
                                    <span class="select-text" data-segment-id="row1-location-prefix"
                                        v-html="getHighlightedSegmentById('row1-location-prefix')"></span>
                                    <span class="relative inline-flex items-center gap-1">
                                        <input
                                            v-model="answers.q4"
                                            type="text"
                                            placeholder="4"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                            @click.stop="toggleFlag(4)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(4) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </span>
                                </div>
                            </td>

                            <!-- Features Row 1 -->
                            <td class="border border-gray-300 px-4 py-5">
                                <ul class="space-y-1 list-none">
                                    <li class="flex items-center gap-1">
                                        <span>–</span>
                                        <span class="select-text" data-segment-id="row1-feature1"
                                            v-html="getHighlightedSegmentById('row1-feature1')"></span>
                                    </li>
                                    <li class="flex items-center gap-1">
                                        <span>–</span>
                                        <span class="select-text" data-segment-id="row1-feature2"
                                            v-html="getHighlightedSegmentById('row1-feature2')"></span>
                                    </li>
                                </ul>
                            </td>

                            <!-- Goods/bad Row 1 -->
                            <td class="border border-gray-300 px-4 py-5">
                                <ul class="space-y-1 list-none">
                                    <!-- (5) £ ... a month -->
                                    <li id="question-5" class="flex flex-wrap items-center gap-1"
                                        @mouseenter="hoveredQuestion = 5" @mouseleave="hoveredQuestion = null">
                                        <span>–</span>
                                        <span class="select-text" data-segment-id="row1-rent-prefix"
                                            v-html="getHighlightedSegmentById('row1-rent-prefix')"></span>
                                        <span class="relative inline-flex items-center gap-1">
                                            <input
                                                v-model="answers.q5"
                                                type="text"
                                                placeholder="5"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="w-24 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                            />
                                            <span class="select-text" data-segment-id="row1-rent-suffix"
                                            v-html="getHighlightedSegmentById('row1-rent-suffix')"></span>

                                            <button
                                                v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                                @click.stop="toggleFlag(5)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(5) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        </li>
                                    <!-- transport links -->
                                    <li class="flex items-center gap-1">
                                        <span>–</span>
                                        <span class="select-text" data-segment-id="row1-good-point"
                                            v-html="getHighlightedSegmentById('row1-good-point')"></span>
                                    </li>
                                    <!-- no shower -->
                                    <li class="flex items-center gap-1">
                                        <span>–</span>
                                        <span class="select-text" data-segment-id="row1-bad-point1"
                                            v-html="getHighlightedSegmentById('row1-bad-point1')"></span>
                                    </li>
                                    <!-- could be (6)... -->
                                    <li id="question-6" class="flex flex-wrap items-center gap-1"
                                        @mouseenter="hoveredQuestion = 6" @mouseleave="hoveredQuestion = null">
                                        <span>–</span>
                                        <span class="select-text" data-segment-id="row1-bad-point2-prefix"
                                            v-html="getHighlightedSegmentById('row1-bad-point2-prefix')"></span>
                                        <span class="relative inline-flex items-center gap-1">
                                            <input
                                                v-model="answers.q6"
                                                type="text"
                                                placeholder="6"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="w-28 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                                @click.stop="toggleFlag(6)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(6) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </li>
                                </ul>
                            </td>
                        </tr>

                        <!-- ── Row 2 ── -->
                        <tr class="align-top">
                            <!-- Location Row 2 — Q7 -->
                            <td class="border border-gray-300 px-4 py-5">
                                <div id="question-7" class="flex flex-wrap items-center gap-1"
                                    @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">
                                    <span class="relative inline-flex items-center gap-1">
                                        <input
                                            v-model="answers.q7"
                                            type="text"
                                            placeholder="7"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="w-36 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                            @click.stop="toggleFlag(7)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(7) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </span>
                                </div>
                            </td>

                            <!-- Features Row 2 -->
                            <td class="border border-gray-300 px-4 py-5">
                                <ul class="space-y-1 list-none">
                                    <li class="flex items-center gap-1">
                                        <span>–</span>
                                        <span class="select-text" data-segment-id="row2-feature1"
                                            v-html="getHighlightedSegmentById('row2-feature1')"></span>
                                    </li>
                                    <li class="flex items-center gap-1">
                                        <span>–</span>
                                        <span class="select-text" data-segment-id="row2-feature2"
                                            v-html="getHighlightedSegmentById('row2-feature2')"></span>
                                    </li>
                                    <!-- (8) -->
                                    <li id="question-8" class="flex flex-wrap items-center gap-1"
                                        @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">
                                        <span>–</span>
                                        <span class="relative inline-flex items-center gap-1">
                                            <input
                                                v-model="answers.q8"
                                                type="text"
                                                placeholder="8"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="w-32 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                            />
                                            <button
                                                v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                                @click.stop="toggleFlag(8)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(8) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </li>
                                </ul>
                            </td>

                            <!-- Goods/bad Row 2 -->
                            <td class="border border-gray-300 px-4 py-5">
                                <ul class="space-y-1 list-none">
                                    <!-- (9)... and well equipped -->
                                    <li id="question-9" class="flex flex-wrap items-center gap-1"
                                        @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                                        <span>–</span>
                                        <span class="relative inline-flex items-center gap-1">
                                            <input
                                                v-model="answers.q9"
                                                type="text"
                                                placeholder="9"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="w-28 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                            />
                                            <span class="select-text" data-segment-id="row2-good-point-suffix"
                                            v-html="getHighlightedSegmentById('row2-good-point-suffix')"></span>

                                            <button
                                                v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                                @click.stop="toggleFlag(9)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(9) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        </li>
                                    <!-- shower -->
                                    <li class="flex items-center gap-1">
                                        <span>–</span>
                                        <span class="select-text" data-segment-id="row2-bad-point1"
                                            v-html="getHighlightedSegmentById('row2-bad-point1')"></span>
                                    </li>
                                    <!-- will be (10)... £ 800 a month -->
                                    <li id="question-10" class="flex flex-wrap items-center gap-1"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                        <span>–</span>

                                        <span class="relative inline-flex items-center gap-1">
                                            <span class="select-text" data-segment-id="row2-bad-point2-prefix"
                                            v-html="getHighlightedSegmentById('row2-bad-point2-prefix')"></span>
                                            <input
                                                v-model="answers.q10"
                                                type="text"
                                                placeholder="10"
                                                spellcheck="false"
                                                autocomplete="off"
                                                class="w-24 border border-gray-400 bg-transparent px-2 py-0.5 text-center placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none"
                                            />

                                            <button
                                                v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                                @click.stop="toggleFlag(10)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(10) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="select-text" data-segment-id="row2-bad-point2-suffix"
                                            v-html="getHighlightedSegmentById('row2-bad-point2-suffix')"></span>

                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div><!-- end zoom wrapper -->
    </div><!-- end contentTextRef -->

    <!-- ── Teleport: Context menu, tooltips, note input ───────────────────── -->
    <Teleport to="body">

        <!-- Highlight context menu -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                        title="Highlight">
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

        <!-- Delete highlight tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
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

        <!-- Note hover tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave"
                @click.stop
            >
                <div class="flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span class="max-w-[180px] text-base break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note input modal -->
        <div
            v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
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
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-base focus:border-black focus:outline-none"
                    @keyup.enter="saveNote"
                    @keyup.escape="cancelNote"
                />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">
                    Save Note
                </button>
            </div>
        </div>

    </Teleport>
</template>

<style scoped>
.part-header-box {
    background-color: #F1F2EC;
}

.select-text {
    user-select: text;
    -webkit-user-select: text;
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
}

.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink    { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange  { background-color: rgba(254, 215, 170, 0.5); }

/* Tooltip arrows */
.tooltip-arrow {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}

.tooltip-arrow-up {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}

.tooltip-arrow-down {
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); }
    50%  { background-color: rgba(0,0,0,0.25); }
    100% { background-color: rgba(0,0,0,0.1); }
}
</style>

<style>
.note-highlight {
    border-bottom: 2px solid rgba(0,0,0,0.4);
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
.note-highlight:hover { border-bottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>
