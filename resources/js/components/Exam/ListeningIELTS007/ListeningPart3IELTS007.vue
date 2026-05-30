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

const emit = defineEmits<{ toggleFlag: [questionNumber: number] }>();

const hoveredQuestion = ref<number | null>(null);
const isQuestionFlagged = (n: number) => props.flaggedQuestions.has(n);
const toggleFlag = (n: number) => emit('toggleFlag', n);

const contentZoom = computed(() => ({ zoom: props.fontSize / 16 }));

const answers = ref({
    q21: '', q22: '', q23: '', q24: '', q25: '',
    q26: '', q27: '', q28: '', q29: '', q30: '',
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

const textSegments = ref([
    { id: 'part3-title',         text: 'Part 3',                                                      offset: 0 },
    { id: 'part3-instruction',   text: 'Listen and answer Questions 21-30. ',                                 offset: 6 },
    { id: 'section-title',       text: 'Project on work placement',                                  offset: 32 },
    { id: 'q21-question',        text: "21. The main aim of Dave's project is to",                  offset: 57 },
    { id: 'q21-opt-a',           text: 'A describe a policy.',                                       offset: 97 },
    { id: 'q21-opt-b',           text: 'B investigate an assumption.',                               offset: 117 },
    { id: 'q21-opt-c',           text: 'C identify a problem.',                                      offset: 146 },
    { id: 'q22-question',        text: "22. Dave's project is based on schemes in",                 offset: 166 },
    { id: 'q22-opt-a',           text: 'A schools.',                                                 offset: 205 },
    { id: 'q22-opt-b',           text: 'B colleges.',                                                offset: 215 },
    { id: 'q22-opt-c',           text: 'C universities.',                                            offset: 226 },
    { id: 'q23-question',        text: "23. How many academic organisations returned Dave's questionnaire?", offset: 241 },
    { id: 'q23-opt-a',           text: 'A 15',                                                       offset: 306 },
    { id: 'q23-opt-b',           text: 'B 50',                                                       offset: 310 },
    { id: 'q23-opt-c',           text: 'C 150',                                                      offset: 314 },
    { id: 'q24-question',        text: '24. Dave wanted his questionnaires to be completed by company', offset: 319 },
    { id: 'q24-opt-a',           text: 'A Human Resources Managers.',                                offset: 379 },
    { id: 'q24-opt-b',           text: 'B Line Managers.',                                           offset: 406 },
    { id: 'q24-opt-c',           text: 'C owners.',                                                  offset: 422 },
    { id: 'q25-question',        text: '25. Dr Green wants Dave to provide a full list of',         offset: 431 },
    { id: 'q25-opt-a',           text: 'A respondents.',                                             offset: 480 },
    { id: 'q25-opt-b',           text: 'B appendices.',                                              offset: 494 },
    { id: 'q25-opt-c',           text: 'C companies.',                                               offset: 507 },
    { id: 'q26-30-header',       text: 'Questions 26-30',                                            offset: 519 },
    { id: 'q26-30-instruction1', text: 'Complete the notes below.',                                  offset: 534 },
    { id: 'q26-30-instruction2', text: 'Write NO MORE THAN TWO WORDS for each answer.',             offset: 559 },
    { id: 'notes-title',         text: 'Notes on project',                                           offset: 603 },
    { id: 'notes-introduction',  text: 'Introduction',                                               offset: 619 },
    { id: 'q26-prefix',          text: 'improve the',                                                offset: 631 },
    { id: 'q26-suffix',          text: 'of ideas',                                                   offset: 642 },
    { id: 'q27-prefix',          text: 'include a',                                                  offset: 650 },
    { id: 'q27-suffix',          text: "of 'Work Placement'",                                        offset: 659 },
    { id: 'q28-prefix',          text: 'have separate sections for literature survey and research',  offset: 678 },
    { id: 'q28-suffix',          text: 'and methods',                                                offset: 734 },
    { id: 'findings-title',      text: 'Findings',                                                   offset: 745 },
    { id: 'prep-stage',          text: 'Preparation stage – add summary',                            offset: 753 },
    { id: 'q29-suffix',          text: 'development – good',                                         offset: 784 },
    { id: 'q30-prefix',          text: 'Constraints on learning – provide better links to the',     offset: 802 },
    { id: 'q30-suffix',          text: 'from research',                                              offset: 856 },
]);

const getHighlightedSegmentById = (segmentId: string) => {
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

const getAnswers = () => ({ ...answers.value });

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

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part3-title"
                    v-html="getHighlightedSegmentById('part3-title')"></h3>
                <p class="text-base text-gray-700 select-text" data-segment-id="part3-instruction"
                    v-html="getHighlightedSegmentById('part3-instruction')"></p>
            </div>

            <div class="flex-1 pb-32">
                <div class="p-2 sm:p-3 md:p-4">
                    <div class="space-y-1">

                        <!-- Section Title -->
                        <p class="mb-4 text-base font-bold text-gray-900 select-text" data-segment-id="section-title"
                            v-html="getHighlightedSegmentById('section-title')"></p>

                        <!-- ───── Q21 ───── -->
                        <div id="question-21" class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 21" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                @click.stop="toggleFlag(21)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(21) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q21-question"
                                v-html="getHighlightedSegmentById('q21-question')"></p>
                            <div class="space-y-0.5">
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q21" value="A"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q21-opt-a"
                                        v-html="getHighlightedSegmentById('q21-opt-a')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q21" value="B"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q21-opt-b"
                                        v-html="getHighlightedSegmentById('q21-opt-b')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q21" value="C"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q21-opt-c"
                                        v-html="getHighlightedSegmentById('q21-opt-c')"></span>
                                </label>
                            </div>
                        </div>

                        <!-- ───── Q22 ───── -->
                        <div id="question-22" class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 22" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                @click.stop="toggleFlag(22)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(22) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q22-question"
                                v-html="getHighlightedSegmentById('q22-question')"></p>
                            <div class="space-y-0.5">
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q22" value="A"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q22-opt-a"
                                        v-html="getHighlightedSegmentById('q22-opt-a')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q22" value="B"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q22-opt-b"
                                        v-html="getHighlightedSegmentById('q22-opt-b')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q22" value="C"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q22-opt-c"
                                        v-html="getHighlightedSegmentById('q22-opt-c')"></span>
                                </label>
                            </div>
                        </div>

                        <!-- ───── Q23 ───── -->
                        <div id="question-23" class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 23" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                @click.stop="toggleFlag(23)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(23) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q23-question"
                                v-html="getHighlightedSegmentById('q23-question')"></p>
                            <div class="space-y-0.5">
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q23" value="A"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q23-opt-a"
                                        v-html="getHighlightedSegmentById('q23-opt-a')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q23" value="B"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q23-opt-b"
                                        v-html="getHighlightedSegmentById('q23-opt-b')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q23" value="C"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q23-opt-c"
                                        v-html="getHighlightedSegmentById('q23-opt-c')"></span>
                                </label>
                            </div>
                        </div>

                        <!-- ───── Q24 ───── -->
                        <div id="question-24" class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                @click.stop="toggleFlag(24)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(24) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q24-question"
                                v-html="getHighlightedSegmentById('q24-question')"></p>
                            <div class="space-y-0.5">
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q24" value="A"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q24-opt-a"
                                        v-html="getHighlightedSegmentById('q24-opt-a')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q24" value="B"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q24-opt-b"
                                        v-html="getHighlightedSegmentById('q24-opt-b')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q24" value="C"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q24-opt-c"
                                        v-html="getHighlightedSegmentById('q24-opt-c')"></span>
                                </label>
                            </div>
                        </div>

                        <!-- ───── Q25 ───── -->
                        <div id="question-25" class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                @click.stop="toggleFlag(25)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(25) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q25-question"
                                v-html="getHighlightedSegmentById('q25-question')"></p>
                            <div class="space-y-0.5">
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q25" value="A"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q25-opt-a"
                                        v-html="getHighlightedSegmentById('q25-opt-a')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q25" value="B"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q25-opt-b"
                                        v-html="getHighlightedSegmentById('q25-opt-b')"></span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q25" value="C"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" data-segment-id="q25-opt-c"
                                        v-html="getHighlightedSegmentById('q25-opt-c')"></span>
                                </label>
                            </div>
                        </div>

                        <!-- ───── Q26–30 Notes Section ───── -->
                        <div class="mt-6 p-2 sm:p-3">
                            <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q26-30-header"
                                v-html="getHighlightedSegmentById('q26-30-header')"></h3>
                            <p class="text-base text-gray-800 select-text" data-segment-id="q26-30-instruction1"
                                v-html="getHighlightedSegmentById('q26-30-instruction1')"></p>
                            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q26-30-instruction2"
                                v-html="getHighlightedSegmentById('q26-30-instruction2')"></p>
                        </div>

                        <!-- Notes Box -->
                        <div class="mx-2 border border-gray-800 p-4 sm:mx-3">

                            <!-- Notes Title -->
                            <p class="mb-4 text-center text-base font-bold text-gray-900 select-text"
                                data-segment-id="notes-title"
                                v-html="getHighlightedSegmentById('notes-title')"></p>

                            <!-- Introduction sub-heading -->
                            <p class="mb-3 text-base font-bold text-gray-900 select-text"
                                data-segment-id="notes-introduction"
                                v-html="getHighlightedSegmentById('notes-introduction')"></p>

                            <!-- Bullet list for Q26, Q27, Q28 -->
                            <ul class="mb-4 space-y-3 pl-4 text-base text-gray-800">

                                <!-- Q26 -->
                                <li id="question-26" class="relative flex flex-wrap items-baseline gap-1 list-disc"
                                    @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                    <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                        @click.stop="toggleFlag(26)"
                                        class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(26) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                        </svg>
                                    </button>
                                    <span class="select-text" data-segment-id="q26-prefix"
                                        v-html="getHighlightedSegmentById('q26-prefix')"></span>
                                    <input v-model="answers.q26" type="text" placeholder="26"
                                        class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                        spellcheck="false" autocomplete="off"
                                        @focus="hoveredQuestion = 26" @blur="hoveredQuestion = null" />
                                    <span class="select-text" data-segment-id="q26-suffix"
                                        v-html="getHighlightedSegmentById('q26-suffix')"></span>
                                </li>

                                <!-- Q27 -->
                                <li id="question-27" class="relative flex flex-wrap items-baseline gap-1 list-disc"
                                    @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                    <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                        @click.stop="toggleFlag(27)"
                                        class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(27) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                        </svg>
                                    </button>
                                    <span class="select-text" data-segment-id="q27-prefix"
                                        v-html="getHighlightedSegmentById('q27-prefix')"></span>
                                    <input v-model="answers.q27" type="text" placeholder="27"
                                        class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                        spellcheck="false" autocomplete="off"
                                        @focus="hoveredQuestion = 27" @blur="hoveredQuestion = null" />
                                    <span class="select-text" data-segment-id="q27-suffix"
                                        v-html="getHighlightedSegmentById('q27-suffix')"></span>
                                </li>

                                <!-- Q28 -->
                                <li id="question-28" class="relative flex flex-wrap items-baseline gap-1 list-disc"
                                    @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                                    <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                        @click.stop="toggleFlag(28)"
                                        class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(28) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                        </svg>
                                    </button>
                                    <span class="select-text" data-segment-id="q28-prefix"
                                        v-html="getHighlightedSegmentById('q28-prefix')"></span>
                                    <input v-model="answers.q28" type="text" placeholder="28"
                                        class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                        spellcheck="false" autocomplete="off"
                                        @focus="hoveredQuestion = 28" @blur="hoveredQuestion = null" />
                                    <span class="select-text" data-segment-id="q28-suffix"
                                        v-html="getHighlightedSegmentById('q28-suffix')"></span>
                                </li>

                            </ul>

                            <!-- Findings sub-heading -->
                            <p class="mb-3 text-base font-bold text-gray-900 select-text"
                                data-segment-id="findings-title"
                                v-html="getHighlightedSegmentById('findings-title')"></p>

                            <!-- Preparation stage label -->
                            <p class="mb-3 text-base text-gray-800 select-text"
                                data-segment-id="prep-stage"
                                v-html="getHighlightedSegmentById('prep-stage')"></p>

                            <!-- Bullet list for Q29, Q30 -->
                            <ul class="space-y-3 pl-4 text-base text-gray-800">

                                <!-- Q29 -->
                                <li id="question-29" class="relative flex flex-wrap items-baseline gap-1 list-disc"
                                    @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
                                    <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                        @click.stop="toggleFlag(29)"
                                        class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(29) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                        </svg>
                                    </button>
                                    <input v-model="answers.q29" type="text" placeholder="29"
                                        class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                        spellcheck="false" autocomplete="off"
                                        @focus="hoveredQuestion = 29" @blur="hoveredQuestion = null" />
                                    <span class="select-text" data-segment-id="q29-suffix"
                                        v-html="getHighlightedSegmentById('q29-suffix')"></span>
                                </li>

                                <!-- Q30 -->
                                <li id="question-30" class="relative flex flex-wrap items-baseline gap-1 list-disc"
                                    @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
                                    <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                        @click.stop="toggleFlag(30)"
                                        class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(30) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                        </svg>
                                    </button>
                                    <span class="select-text" data-segment-id="q30-prefix"
                                        v-html="getHighlightedSegmentById('q30-prefix')"></span>
                                    <input v-model="answers.q30" type="text" placeholder="30"
                                        class="w-36 border border-gray-500 bg-transparent px-1 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-800 focus:outline-none"
                                        spellcheck="false" autocomplete="off"
                                        @focus="hoveredQuestion = 30" @blur="hoveredQuestion = null" />
                                    <span class="select-text" data-segment-id="q30-suffix"
                                        v-html="getHighlightedSegmentById('q30-suffix')"></span>
                                </li>

                            </ul>
                        </div>
                        <!-- End Notes Box -->

                    </div>
                </div>
            </div>
        </div>
    </div>

    <Teleport to="body">

        <!-- Highlight context menu -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
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
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round">
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
            <div class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md"
                    @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop>
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
        <div v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-base focus:borderlack focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 hover:bg-gray-100">Cancel</button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">Save Note</button>
            </div>
        </div>

    </Teleport>
</template>

<style scoped>
.part-header-box {
    background-color: #F1F2EC;
}

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0%   { background-color: rgba(0, 0, 0, 0.1); }
    50%  { background-color: rgba(0, 0, 0, 0.25); }
    100% { background-color: rgba(0, 0, 0, 0.1); }
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

.highlight-tooltip { position: fixed; z-index: 9999; }

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
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    borderottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}

.tooltip-arrow-down {
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

input:focus { background-color: rgba(0,0,0,0.02); }

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #000; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #374151; }
</style>

<style>
.note-highlight {
    borderottom: 2px solid rgba(0, 0, 0, 0.4);
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
.note-highlight:hover { borderottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>
