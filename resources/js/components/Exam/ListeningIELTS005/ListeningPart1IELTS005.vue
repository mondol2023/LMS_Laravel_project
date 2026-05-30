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
    q1: '', q2: '', q3: '', q4: '', q5: '',
    q6: '', q7: '', q8: '', q9: '', q10: '',
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

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'A', label: 'only Jacinta' },
    { value: 'B', label: 'only Lewis' },
    { value: 'C', label: 'both Jacinta and Lewis' },
];

// Options always available (A/B/C can be reused across questions)
const availableOptions = computed(() => headingOptions);

const getOptionLabel = (value: string): string => {
    const opt = headingOptions.find((o) => o.value === value);
    return opt ? `${opt.value}. ${opt.label}` : '';
};

const handleDragStart = (e: DragEvent, option: string) => {
    draggedOption.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleDragEnd = () => {
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const handleDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy';
    dragOverQuestion.value = questionNum;
};

const handleDragLeave = () => { dragOverQuestion.value = null; };

const handleDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedOption.value;
    if (option) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

// ✅ FIX 1: Every segment now has a unique string `id`
// ✅ FIX 2: Offsets are strictly sequential (no overlaps)
// Rule: next.offset >= prev.offset + prev.text.length
const textSegments = ref([
    { id: 'part1-title',        text: 'Part 1',                                                              offset: 0   },
    { id: 'part1-desc',         text: 'Listen and answer questions 1–10.',                                   offset: 10  },
    { id: 'q1-6-heading',       text: 'Questions 1-6',                                                       offset: 50  },
    { id: 'complete-form',      text: 'Complete the form below.',                                             offset: 70  },
    { id: 'word-limit',         text: 'Write NO MORE THAN THREE WORDS OR A NUMBER for each answer.',         offset: 100 },
    { id: 'table-title',        text: 'Budget Accommodation in Queenstown, New Zealand',                      offset: 165 },
    { id: 'th-accommodation',   text: 'Accommodation',                                                       offset: 215 },
    { id: 'th-price',           text: 'Price (dormitory)',                                                    offset: 230 },
    { id: 'th-comments',        text: 'Comments',                                                             offset: 250 },
    { id: 'travellers-name',    text: "Travellers' Lodge",                                                    offset: 265 },
    { id: 'fully-booked',       text: 'Fully booked',                                                        offset: 285 },
    { id: 'bingleys-name',      text: "Bingley's",                                                           offset: 300 },
    { id: 'bingleys-pre-q2',    text: '– In town centre Cafe with regular ',                                 offset: 315 },
    { id: 'bingleys-post-q2',   text: ' nights– Sundeck',                                                    offset: 355 },
    { id: 'chalet-name',        text: 'Chalet Lodge',                                                        offset: 380 },
    { id: 'chalet-price',       text: '$18.00',                                                              offset: 395 },
    { id: 'chalet-pre-q3',      text: '– Located in a ',                                                     offset: 405 },
    { id: 'chalet-mid',         text: ' alpine setting 10 mins from town centre ',                           offset: 425 },
    { id: 'chalet-post-q4',     text: ' are welcome',                                                        offset: 470 },
    { id: 'globe-name',         text: 'Globetrotters',                                                       offset: 490 },
    { id: 'globe-price',        text: '$18.50',                                                              offset: 505 },
    { id: 'globe-pre-q5',       text: '– In town centre ',                                                   offset: 515 },
    { id: 'globe-pre-q6',       text: '– Chance to win a ',                                                  offset: 540 },
    { id: 'q7-10-heading',      text: 'Questions 7-10',                                                      offset: 565 },
    { id: 'q7-10-intro',        text: 'Who wants to do each of the activities below?',                       offset: 582 },
    { id: 'option-a',           text: 'A. only Jacinta',                                                     offset: 630 },
    { id: 'option-b',           text: 'B. only Lewis',                                                       offset: 650 },
    { id: 'option-c',           text: 'C. both Jacinta and Lewis',                                           offset: 668 },
    { id: 'write-instruction',  text: 'Write the correct letter A B or C next to questions 7-10.',           offset: 698 },
    { id: 'q7-text',            text: '7. bungee jump',                                                      offset: 760 },
    { id: 'q8-text',            text: '8. white-water rafting',                                              offset: 778 },
    { id: 'q9-text',            text: '9. jet-boat ride',                                                    offset: 803 },
    { id: 'q10-text',           text: '10. trekking on wilderness trail',                                    offset: 822 },
]);

// ✅ FIX 3: Correct lookup by id (was `s.text === segment` — comparing string to object)
const getHighlightedSegmentById = (segmentId: string): string => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset
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

const getAnswers = () => answers.value;

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// ✅ FIX 4: handleContentTextSelect now reads `data-segment-id` (matching the template)
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) { showContextMenu.value = false; return; }

        const selected = selection.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container: Node = node;
            if (container.nodeType !== Node.ELEMENT_NODE) container = container.parentNode as Node;
            const segmentEl = (container as Element).closest('[data-segment-id]');
            if (!segmentEl) return null;

            const segmentId = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) return null;

            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let offsetInSegment = 0;
            let currentNode: Node | null;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) { offsetInSegment += offsetInNode; break; }
                offsetInSegment += currentNode.textContent?.length || 0;
            }
            return segment.offset + offsetInSegment;
        };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset   = getAbsoluteOffset(range.endContainer,   range.endOffset);

        if (startAbsOffset === null || endAbsOffset === null) {
            showContextMenu.value = false;
            window.getSelection()?.removeAllRanges();
            return;
        }
        if (startAbsOffset > endAbsOffset) [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 58;
            const vw = window.innerWidth;
            contextMenuPosition.value = { x: Math.min(Math.max(menuX, 80), vw - 80), y: Math.max(menuY, 10) };
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
    const vw = window.innerWidth; const vh = window.innerHeight; const hw = modalWidth / 2;
    if (x - hw < padding) x = hw + padding;
    else if (x + hw > vw - padding) x = vw - hw - padding;
    if (y + modalHeight > vh - padding) {
        if (selection && selection.rangeCount > 0) y = selection.getRangeAt(0).getBoundingClientRect().top - modalHeight - 10;
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
    noteInputText.value = ''; showNoteInput.value = false;
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
            showDeleteTooltip.value = true; showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        showDeleteTooltip.value = false; highlightToDelete.value = null;
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
                hoveredNoteId.value = noteIdNum; hoveredNoteText.value = note.note; showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    if ((event.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) return;
    if ((event.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; };
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
    <!-- ✅ FIX 5: All data-segment-text changed to data-segment-id matching the segment id field -->
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="mx-auto px-2 py-4 pb-32 sm:px-4">

        <div class="flex min-h-screen w-full flex-col bg-white px-6" :style="contentZoom">

            <!-- Header -->
            <div class="flex-shrink-0 bg-white p-3 sm:p-4 lg:p-6">
                <div class="mb-3 rounded border border-gray-200 part-header-box px-4 py-3">
                    <h3 class="text-base font-bold text-gray-900 select-text"
                        data-segment-id="part1-title"
                        v-html="getHighlightedSegmentById('part1-title')"></h3>
                    <p class="text-sm text-gray-700 select-text"
                        data-segment-id="part1-desc"
                        v-html="getHighlightedSegmentById('part1-desc')"></p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <p class="text-xs font-semibold tracking-wide text-black uppercase sm:text-sm">
                            <span data-segment-id="q1-6-heading"
                                v-html="getHighlightedSegmentById('q1-6-heading')"></span>
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-md text-gray-900">
                        <span data-segment-id="word-limit"
                            v-html="getHighlightedSegmentById('word-limit')"></span>
                    </p>
                    <p class="mt-4 text-[24px] font-extrabold text-black">
                        <span data-segment-id="table-title"
                            v-html="getHighlightedSegmentById('table-title')"></span>
                    </p>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto pb-4">
                <div class="p-3 sm:p-4 lg:p-6">

                    <!-- Table Q1–6 -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-900 text-left ">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border border-gray-900 px-4 py-2 font-semibold text-black">
                                        <span data-segment-id="th-accommodation"
                                            v-html="getHighlightedSegmentById('th-accommodation')"></span>
                                    </th>
                                    <th class="border border-gray-900 px-4 py-2 font-semibold text-black">
                                        <span data-segment-id="th-price"
                                            v-html="getHighlightedSegmentById('th-price')"></span>
                                    </th>
                                    <th class="border border-gray-900 px-4 py-2 font-semibold text-black">
                                        <span data-segment-id="th-comments"
                                            v-html="getHighlightedSegmentById('th-comments')"></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Row 1: Travellers' Lodge -->
                                <tr>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="travellers-name"
                                            v-html="getHighlightedSegmentById('travellers-name')"></span>
                                    </td>
                                    <td class="border border-gray-900 px-4 py-2"></td>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="fully-booked"
                                            v-html="getHighlightedSegmentById('fully-booked')"></span>
                                    </td>
                                </tr>

                                <!-- Row 2: Bingley's — Q1, Q2 -->
                                <tr>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="bingleys-name"
                                            v-html="getHighlightedSegmentById('bingleys-name')"></span>
                                    </td>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <!-- Q1 -->
                                        <span id="question-1" class="group/q1 inline-flex items-center gap-1">
                                            $
                                            <input v-model="answers.q1" type="text"
                                                class="w-40 border border-gray-900 bg-transparent text-center focus:outline-none"
                                                placeholder="1" />
                                            <button
                                                @click.stop="toggleFlag(1)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q1:opacity-100"
                                                :class="isQuestionFlagged(1) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </td>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="bingleys-pre-q2"
                                            v-html="getHighlightedSegmentById('bingleys-pre-q2')"></span>
                                        <!-- Q2 -->
                                        <span id="question-2" class="group/q2 inline-flex items-center gap-1">
                                            <input v-model="answers.q2" type="text"
                                                class="w-40 border border-gray-900 bg-transparent text-center focus:outline-none"
                                                placeholder="2" />
                                            <span data-segment-id="bingleys-post-q2"
                                                v-html="getHighlightedSegmentById('bingleys-post-q2')"></span>
                                            <button
                                                @click.stop="toggleFlag(2)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q2:opacity-100"
                                                :class="isQuestionFlagged(2) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </td>
                                </tr>

                                <!-- Row 3: Chalet Lodge — Q3, Q4 -->
                                <tr>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="chalet-name"
                                            v-html="getHighlightedSegmentById('chalet-name')"></span>
                                    </td>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="chalet-price"
                                            v-html="getHighlightedSegmentById('chalet-price')"></span>
                                    </td>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="chalet-pre-q3"
                                            v-html="getHighlightedSegmentById('chalet-pre-q3')"></span>
                                        <!-- Q3 -->
                                        <span id="question-3" class="group/q3 inline-flex items-center gap-1">
                                            <input v-model="answers.q3" type="text"
                                                class="w-36 border border-gray-900 bg-transparent text-center focus:outline-none"
                                                placeholder="3" />
                                            <button
                                                @click.stop="toggleFlag(3)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q3:opacity-100"
                                                :class="isQuestionFlagged(3) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span data-segment-id="chalet-mid"
                                            v-html="getHighlightedSegmentById('chalet-mid')"></span>
                                        <!-- Q4 -->
                                        <span id="question-4" class="group/q4 inline-flex items-center gap-1">
                                            <input v-model="answers.q4" type="text"
                                                class="w-36 border border-gray-900 bg-transparent text-center focus:outline-none"
                                                placeholder="4" />
                                            <span data-segment-id="chalet-post-q4"
                                                v-html="getHighlightedSegmentById('chalet-post-q4')"></span>
                                            <button
                                                @click.stop="toggleFlag(4)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q4:opacity-100"
                                                :class="isQuestionFlagged(4) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </td>
                                </tr>

                                <!-- Row 4: Globetrotters — Q5, Q6 -->
                                <tr>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="globe-name"
                                            v-html="getHighlightedSegmentById('globe-name')"></span>
                                    </td>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="globe-price"
                                            v-html="getHighlightedSegmentById('globe-price')"></span>
                                    </td>
                                    <td class="border border-gray-900 px-4 py-2">
                                        <span data-segment-id="globe-pre-q5"
                                            v-html="getHighlightedSegmentById('globe-pre-q5')"></span>
                                        <!-- Q5 -->
                                        <span id="question-5" class="group/q5 inline-flex items-center gap-1">
                                            <input v-model="answers.q5" type="text"
                                                class="w-28 border border-gray-900 bg-transparent text-center focus:outline-none"
                                                placeholder="5" />
                                            <button
                                                @click.stop="toggleFlag(5)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q5:opacity-100"
                                                :class="isQuestionFlagged(5) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span data-segment-id="globe-pre-q6"
                                            v-html="getHighlightedSegmentById('globe-pre-q6')"></span>
                                        <!-- Q6 -->
                                        <span id="question-6" class="group/q6 inline-flex items-center gap-1">
                                            <input v-model="answers.q6" type="text"
                                                class="w-28 border border-gray-900 bg-transparent text-center focus:outline-none"
                                                placeholder="6" />
                                            <button
                                                @click.stop="toggleFlag(6)"
                                                class="ml-1 flex h-7 w-7 items-center justify-center rounded border transition-all duration-150
                                                    opacity-5 group-hover/q6:opacity-100"
                                                :class="isQuestionFlagged(6) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-500'">
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

                    <!-- Q7–10 -->
                    <section class="mt-6 md:mt-12">
                <h2 class="text-[18px] font-semibold text-black">
                    <span data-segment-id="q7-10-heading"
                        v-html="getHighlightedSegmentById('q7-10-heading')"></span>
                </h2>
                <p class="mt-4 text-[18px] text-black">
                    <span data-segment-id="q7-10-intro"
                        v-html="getHighlightedSegmentById('q7-10-intro')"></span>
                </p>

                <!-- Options legend (still selectable / highlightable) -->
                <p class="mt-4 text-black">
                    <strong><span data-segment-id="option-a"
                        v-html="getHighlightedSegmentById('option-a')"></span></strong>
                </p>
                <p class="text-black">
                    <strong><span data-segment-id="option-b"
                        v-html="getHighlightedSegmentById('option-b')"></span></strong>
                </p>
                <p class="text-black">
                    <strong><span data-segment-id="option-c"
                        v-html="getHighlightedSegmentById('option-c')"></span></strong>
                </p>
                <p class="mt-4 text-[18px] text-gray-700">
                    <span data-segment-id="write-instruction"
                        v-html="getHighlightedSegmentById('write-instruction')"></span>
                </p>

                <!-- Side-by-side: questions + draggable panel -->
                <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:gap-16">

                    <!-- Left: Drop zones for Q7-10 -->
                    <div class="space-y-5">
                        <!-- Q7 -->
                        <div id="question-7" class="flex items-center gap-3"
                            @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">
                            <span class="text-[18px] font-medium text-black select-text min-w-[200px]"
                                data-segment-id="q7-text"
                                v-html="getHighlightedSegmentById('q7-text')"></span>

                            <!-- Drop zone -->
                            <span
                                class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                                :class="dragOverQuestion === 7
                                    ? 'border-blue-500 bg-blue-50'
                                    : answers.q7
                                        ? 'border-gray-900 bg-white font-medium text-gray-900'
                                        : 'border-gray-400 bg-white'"
                                @dragover="handleDragOver($event, 7)"
                                @dragleave="handleDragLeave"
                                @drop="handleDrop($event, 7)"
                                @click="clearAnswer(7)"
                                :title="answers.q7 ? 'Click to clear' : 'Drop answer here'">
                                <span v-if="answers.q7">{{ getOptionLabel(answers.q7) }}</span>
                                <span v-else class="font-bold text-gray-500">7</span>
                            </span>

                            <!-- Flag button -->
                            <div class="w-7 h-7 shrink-0">
                                <button v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                    @click.stop="toggleFlag(7)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(7)
                                        ? 'border-red-300 text-red-500'
                                        : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Q8 -->
                        <div id="question-8" class="flex items-center gap-3"
                            @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">
                            <span class="text-[18px] font-medium text-black select-text min-w-[200px]"
                                data-segment-id="q8-text"
                                v-html="getHighlightedSegmentById('q8-text')"></span>
                            <span
                                class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                                :class="dragOverQuestion === 8
                                    ? 'border-blue-500 bg-blue-50'
                                    : answers.q8
                                        ? 'border-gray-900 bg-white font-medium text-gray-900'
                                        : 'border-gray-400 bg-white'"
                                @dragover="handleDragOver($event, 8)"
                                @dragleave="handleDragLeave"
                                @drop="handleDrop($event, 8)"
                                @click="clearAnswer(8)"
                                :title="answers.q8 ? 'Click to clear' : 'Drop answer here'">
                                <span v-if="answers.q8">{{ getOptionLabel(answers.q8) }}</span>
                                <span v-else class="font-bold text-gray-500">8</span>
                            </span>
                            <div class="w-7 h-7 shrink-0">
                                <button v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                    @click.stop="toggleFlag(8)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(8)
                                        ? 'border-red-300 text-red-500'
                                        : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Q9 -->
                        <div id="question-9" class="flex items-center gap-3"
                            @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                            <span class="text-[18px] font-medium text-black select-text min-w-[200px]"
                                data-segment-id="q9-text"
                                v-html="getHighlightedSegmentById('q9-text')"></span>
                            <span
                                class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                                :class="dragOverQuestion === 9
                                    ? 'border-blue-500 bg-blue-50'
                                    : answers.q9
                                        ? 'border-gray-900 bg-white font-medium text-gray-900'
                                        : 'border-gray-400 bg-white'"
                                @dragover="handleDragOver($event, 9)"
                                @dragleave="handleDragLeave"
                                @drop="handleDrop($event, 9)"
                                @click="clearAnswer(9)"
                                :title="answers.q9 ? 'Click to clear' : 'Drop answer here'">
                                <span v-if="answers.q9">{{ getOptionLabel(answers.q9) }}</span>
                                <span v-else class="font-bold text-gray-500">9</span>
                            </span>
                            <div class="w-7 h-7 shrink-0">
                                <button v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                    @click.stop="toggleFlag(9)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(9)
                                        ? 'border-red-300 text-red-500'
                                        : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Q10 -->
                        <div id="question-10" class="flex items-center gap-3"
                            @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                            <span class="text-[18px] font-medium text-black select-text min-w-[200px]"
                                data-segment-id="q10-text"
                                v-html="getHighlightedSegmentById('q10-text')"></span>
                            <span
                                class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                                :class="dragOverQuestion === 10
                                    ? 'border-blue-500 bg-blue-50'
                                    : answers.q10
                                        ? 'border-gray-900 bg-white font-medium text-gray-900'
                                        : 'border-gray-400 bg-white'"
                                @dragover="handleDragOver($event, 10)"
                                @dragleave="handleDragLeave"
                                @drop="handleDrop($event, 10)"
                                @click="clearAnswer(10)"
                                :title="answers.q10 ? 'Click to clear' : 'Drop answer here'">
                                <span v-if="answers.q10">{{ getOptionLabel(answers.q10) }}</span>
                                <span v-else class="font-bold text-gray-500">10</span>
                            </span>
                            <div class="w-7 h-7 shrink-0">
                                <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                    @click.stop="toggleFlag(10)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(10)
                                        ? 'border-red-300 text-red-500'
                                        : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Draggable options panel (A/B/C always visible, reusable) -->
                    <div class="w-64 shrink-0 self-start sticky top-12">
                        <p class="mb-3 text-sm font-semibold text-gray-700">Drag options to fill blanks:</p>
                        <div class="border border-gray-900 bg-white p-3">
                            <div class="space-y-2">
                                <div v-for="option in availableOptions" :key="option.value"
                                    class="flex cursor-grab items-center gap-2 rounded border border-gray-300 px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                    :class="{ 'opacity-50 scale-95': draggedOption === option.value }"
                                    draggable="true"
                                    @dragstart="handleDragStart($event, option.value)"
                                    @dragend="handleDragEnd">
                                    <span class="text-sm font-bold text-gray-900">{{ option.value }}</span>
                                    <span class="text-sm text-gray-700">{{ option.label }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
                </div>
            </div>
        </div>

        <!-- Teleported Tooltips -->
        <Teleport to="body">
            <!-- Context Menu -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop>
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 hover:bg-gray-50">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 hover:bg-gray-50">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                        </button>
                    </div>
                    <div class="tooltip-arrow"></div>
                </div>
            </div>

            <!-- Delete Tooltip -->
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
                <div class="delete-highlight-tooltip fixed z-[9999]"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 hover:bg-gray-50">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                <line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" />
                            </svg>
                            <span class="mt-1.5 text-xs font-medium text-gray-600">Delete Highlight</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Note Hover Tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop>
                    <div class="flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <svg class="h-4 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 hover:bg-red-50">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <div class="tooltip-arrow-down"></div>
                </div>
            </div>

            <!-- Note Input -->
            <div v-if="showNoteInput"
                class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 max-h-16 overflow-y-auto border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">Save Note</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.part-header-box { background-color: #F1F2EC; }

.select-text { user-select: text; -webkit-user-select: text; }

mark[data-highlight-id] { padding: 2px 0; border-radius: 2px; cursor: pointer; }
.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }

.highlight-question { animation: highlightPulse 2s ease-in-out; }
@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); }
    50%  { background-color: rgba(0,0,0,0.2); }
    100% { background-color: rgba(0,0,0,0.1); }
}

/* Tooltip arrows */
.highlight-tooltip { position: fixed; z-index: 9999; }
.tooltip-arrow { position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%); width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1)); }
.tooltip-arrow-up { position: relative; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 8px solid white; filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1)); }
.tooltip-arrow-down { position: relative; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1)); }
</style>

<style>
.note-highlight { border-bottom: 2px solid rgba(0,0,0,0.4); cursor: help; display: inline; }
.note-highlight::after { content: '📝'; display: inline-block; margin-left: 2px; font-size: 0.7em; vertical-align: super; pointer-events: none; }
</style>
