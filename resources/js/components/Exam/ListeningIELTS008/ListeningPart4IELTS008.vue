
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

// ─── Answers ─────────────────────────────────────────────────────────────────
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


const textSegments = ref([
    { id: 0,  text: 'Section 4',                                                            offset: 0   },
    { id: 1,  text: ' QUESTIONS 31\u201340',                                                offset: 9   },
    { id: 2,  text: 'Complete the sentences below.',                                        offset: 25  },
    { id: 3,  text: 'Write NO MORE THAN TWO WORDS for each answer.',                        offset: 54  },
    { id: 4,  text: 'Saving the juniper plant',                                             offset: 99  },
    { id: 5,  text: 'Background',                                                           offset: 123 },
    { id: 6,  text: 'Juniper was one of the first plants to colonise Britain after the last', offset: 133 },
    { id: 7,  text: '.',                                                                    offset: 203 },
    { id: 8,  text: 'Its smoke is virtually',                                               offset: 204 },
    { id: 9,  text: 'so juniper wood was used as fuel in illegal activities.',               offset: 226 },
    { id: 10, text: 'Oils from the plant were used to prevent',                             offset: 281 },
    { id: 11, text: 'spreading.',                                                           offset: 321 },
    { id: 12, text: 'Nowadays, its berries are widely used to',                             offset: 331 },
    { id: 13, text: 'food and drink.',                                                      offset: 371 },
    { id: 14, text: 'Ecology',                                                              offset: 386 },
    { id: 15, text: 'Juniper plants also support several species of insects and',           offset: 393 },
    { id: 16, text: '.',                                                                    offset: 451 },
    { id: 17, text: 'Problems',                                                             offset: 452 },
    { id: 18, text: 'In current juniper populations, ratios of the',                        offset: 460 },
    { id: 19, text: 'are poor.',                                                            offset: 505 },
    { id: 20, text: 'Many of the bushes in each group are of the same age so',              offset: 514 },
    { id: 21, text: 'of whole populations is rapid.',                                       offset: 569 },
    { id: 22, text: 'Solutions',                                                            offset: 599 },
    { id: 23, text: 'Plantlife is trialling novel techniques across',                       offset: 608 },
    { id: 24, text: 'areas of England.',                                                    offset: 654 },
    { id: 25, text: 'One measure is to introduce',                                          offset: 671 },
    { id: 26, text: 'for seedlings.',                                                       offset: 698 },
    { id: 27, text: 'A further step is to plant',                                           offset: 712 },
    { id: 28, text: 'from healthy bushes.',                                                 offset: 738 },
]);

// ─── Highlight / note state ───────────────────────────────────────────────────
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

// ─── Highlight rendering ──────────────────────────────────────────────────────
const getHighlightedSegmentById = (segmentId: number): string => {
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
        })),
    ].sort((a, b) => b.start - a.start);

    let result = plainText;
    for (const annotation of annotations) {
        const annotationStart = Math.max(0, annotation.start - segmentOffset);
        const annotationEnd = Math.min(plainText.length, annotation.end - segmentOffset);
        if (annotationStart < annotationEnd) {
            const before = result.substring(0, annotationStart);
            const annotated = result.substring(annotationStart, annotationEnd);
            const after = result.substring(annotationEnd);
            result =
                annotation.type === 'note'
                    ? `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`
                    : `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
        }
    }
    return result;
};

// ─── Selection handling ───────────────────────────────────────────────────────
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
            const viewportWidth = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), viewportWidth - 80),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            if (selection && range) {
                let targetElement: Node | null = range.startContainer;
                while (targetElement && targetElement.nodeType !== Node.ELEMENT_NODE)
                    targetElement = targetElement.parentNode;
                while (
                    targetElement &&
                    !(targetElement as Element).hasAttribute?.('data-segment-id')
                ) {
                    const parent = targetElement.parentNode;
                    if (!parent || parent === contentTextRef.value) break;
                    targetElement = parent;
                }
                if (targetElement && (targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const segmentId = parseInt(
                        (targetElement as Element).getAttribute('data-segment-id') || '0',
                        10,
                    );
                    const segment = textSegments.value.find((s) => s.id === segmentId);
                    if (segment) {
                        const startInEl = getTextOffsetInElement(
                            targetElement as Element,
                            range.startContainer,
                            range.startOffset,
                        );
                        selectedText.value = selected;
                        selectionRange.value = {
                            start: segment.offset + startInEl,
                            end: segment.offset + startInEl + selected.length,
                        };
                    }
                }
            }
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    notes.value = notes.value.filter(
        (n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start),
    );
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
    let x: number, y: number;
    if (selection && selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const rect = range.getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    } else {
        x = contextMenuPosition.value.x;
        y = contextMenuPosition.value.y + 70;
    }
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const half = modalWidth / 2;
    if (x - half < padding) x = half + padding;
    else if (x + half > vw - padding) x = vw - half - padding;
    if (y + modalHeight > vh - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            y = range.getBoundingClientRect().top - modalHeight - 10;
        }
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
    notes.value.push({
        id: Date.now(),
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

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;
    if (highlightMark) {
        const highlightId = highlightMark.getAttribute('data-highlight-id');
        if (highlightId) {
            event.stopPropagation();
            const rect = highlightMark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
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
        const id = highlightToDelete.value;
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        deleteHighlight(id);
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const noteMark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (noteMark) {
        const noteId = parseInt(noteMark.getAttribute('data-note-id') || '0', 10);
        const note = notes.value.find((n) => n.id === noteId);
        if (note) {
            const rect = noteMark.getBoundingClientRect();
            let y = rect.top - 58;
            if (y < 10) y = rect.bottom + 8;
            noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
            hoveredNoteId.value = noteId;
            hoveredNoteText.value = note.note;
            showNoteTooltip.value = true;
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

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

const getAnswers = () => ({ ...answers.value });

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

defineExpose({ getAnswers, answers: answers.value, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div
        ref="contentTextRef"
        @mouseup="handleContentTextSelect"
        @click="handleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6"
    >
        <div class="flex min-h-screen w-full flex-col" >

            <!-- Part Header Box -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3">
                <h3 class="text-base font-bold text-gray-900 select-text">
                    <span
                        data-segment-id="0"
                        v-html="getHighlightedSegmentById(0)"
                    ></span><span
                        data-segment-id="1"
                        v-html="getHighlightedSegmentById(1)"
                    ></span>
                </h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="2"
                    v-html="getHighlightedSegmentById(2)"
                ></p>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="3"
                    v-html="getHighlightedSegmentById(3)"
                ></p>
            </div>

            <!-- Passage title -->
            <div class="mb-4 px-2 text-center">
                <h2
                    class="text-base font-bold text-gray-900 select-text sm:text-lg"
                    data-segment-id="4"
                    v-html="getHighlightedSegmentById(4)"
                ></h2>
            </div>

            <!-- ── Background ─────────────────────────────────────────────── -->
            <div class="mb-5 px-2">
                <h3
                    class="mb-2 text-sm font-bold text-gray-900 select-text uppercase tracking-wide"
                    data-segment-id="5"
                    v-html="getHighlightedSegmentById(5)"
                ></h3>

                <!-- Q31 -->
                <p
                    id="question-31"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 31"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                        @click.stop="toggleFlag(31)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(31) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">

                        <input
                            v-model="answers.q31"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent px-1 py-0 text-sm text-center placeholder:font-bold  focus:border-gray-900 focus:outline-none"
                            placeholder="31"
                        />
                    </span>
                    <span class="select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                </p>

                <!-- Q32 -->
                <p
                    id="question-32"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 32"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                        @click.stop="toggleFlag(32)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(32) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">
                        <input
                            v-model="answers.q32"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent placeholder:font-bold  px-1 py-0 text-sm text-center focus:border-gray-900 focus:outline-none"
                            placeholder="32"
                        />
                    </span>
                    <span class="select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                </p>

                <!-- Q33 -->
                <p
                    id="question-33"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 33"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                        @click.stop="toggleFlag(33)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(33) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">
                        <input
                            v-model="answers.q33"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent placeholder:font-bold  px-1 py-0 text-sm text-center focus:border-gray-900 focus:outline-none"
                            placeholder="33"
                        />
                    </span>
                    <span class="select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                </p>

                <!-- Q34 -->
                <p
                    id="question-34"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 34"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                        @click.stop="toggleFlag(34)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(34) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">
                        <input
                            v-model="answers.q34"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent placeholder:font-bold  px-1 py-0 text-sm text-center focus:border-gray-900 focus:outline-none"
                            placeholder="34"
                        />
                    </span>
                    <span class="select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                </p>
            </div>

            <!-- ── Ecology ─────────────────────────────────────────────────── -->
            <div class="mb-5 px-2">
                <h3
                    class="mb-2 text-sm font-bold text-gray-900 select-text uppercase tracking-wide"
                    data-segment-id="14"
                    v-html="getHighlightedSegmentById(14)"
                ></h3>

                <!-- Q35 -->
                <p
                    id="question-35"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 35"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                        @click.stop="toggleFlag(35)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(35) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">
                        <input
                            v-model="answers.q35"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent placeholder:font-bold  px-1 py-0 text-sm text-center focus:border-gray-900 focus:outline-none"
                            placeholder="35"
                        />
                    </span>
                    <span class="select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                </p>
            </div>

            <!-- ── Problems ───────────────────────────────────────────────── -->
            <div class="mb-5 px-2">
                <h3
                    class="mb-2 text-sm font-bold text-gray-900 select-text uppercase tracking-wide"
                    data-segment-id="17"
                    v-html="getHighlightedSegmentById(17)"
                ></h3>

                <!-- Q36 -->
                <p
                    id="question-36"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 36"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                        @click.stop="toggleFlag(36)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(36) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">
                        <input
                            v-model="answers.q36"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent placeholder:font-bold  px-1 py-0 text-sm text-center focus:border-gray-900 focus:outline-none"
                            placeholder="36"
                        />
                    </span>
                    <span class="select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                </p>

                <!-- Q37 -->
                <p
                    id="question-37"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 37"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                        @click.stop="toggleFlag(37)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(37) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">
                        <input
                            v-model="answers.q37"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent placeholder:font-bold  px-1 py-0 text-sm text-center focus:border-gray-900 focus:outline-none"
                            placeholder="37"
                        />
                    </span>
                    <span class="select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                </p>
            </div>

            <!-- ── Solutions ──────────────────────────────────────────────── -->
            <div class="mb-5 px-2">
                <h3
                    class="mb-2 text-sm font-bold text-gray-900 select-text uppercase tracking-wide"
                    data-segment-id="22"
                    v-html="getHighlightedSegmentById(22)"
                ></h3>

                <!-- Q38 -->
                <p
                    id="question-38"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 38"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                        @click.stop="toggleFlag(38)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(38) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">
                        <input
                            v-model="answers.q38"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent placeholder:font-bold  px-1 py-0 text-sm text-center focus:border-gray-900 focus:outline-none"
                            placeholder="38"
                        />
                    </span>
                    <span class="select-text" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                </p>

                <!-- Q39 -->
                <p
                    id="question-39"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 39"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                        @click.stop="toggleFlag(39)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(39) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">
                        <input
                            v-model="answers.q39"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent placeholder:font-bold  px-1 py-0 text-sm text-center focus:border-gray-900 focus:outline-none"
                            placeholder="39"
                        />
                    </span>
                    <span class="select-text" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                </p>

                <!-- Q40 -->
                <p
                    id="question-40"
                    class="relative mb-2 flex flex-wrap items-baseline gap-x-1 text-sm leading-relaxed text-gray-900 sm:text-base"
                    @mouseenter="hoveredQuestion = 40"
                    @mouseleave="hoveredQuestion = null"
                >
                    <button
                        v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                        @click.stop="toggleFlag(40)"
                        class="absolute -top-1 right-0 flex h-6 w-6 items-center justify-center rounded border transition-all"
                        :class="isQuestionFlagged(40) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                    <span class="select-text" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></span>
                    <span class="inline-flex items-center gap-1 align-baseline">
                        <input
                            v-model="answers.q40"
                            type="text"
                            spellcheck="false"
                            autocomplete="off"
                            class="min-w-[7rem] border border-gray-700 bg-transparent placeholder:font-bold  px-1 py-0 text-sm text-center focus:border-gray-900 focus:outline-none"
                            placeholder="40"
                        />
                    </span>
                    <span class="select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
                </p>
            </div>

        </div>

        <!-- ── Tooltips (Teleport to body) ───────────────────────────────────── -->
        <Teleport to="body">

            <!-- Highlight / Note context menu -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
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

            <!-- Delete highlight tooltip -->
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
                <div
                    class="delete-highlight-tooltip fixed z-[9999]"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                >
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button
                            @click="confirmDeleteHighlight"
                            type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50"
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

            <!-- Note hover tooltip -->
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave"
                    @click.stop
                >
                    <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                        <span class="max-w-[180px] text-sm wrap-break-word text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 transition-colors hover:bg-red-50">
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
                    <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        spellcheck="false"
                        autocomplete="off"
                        placeholder="Write your note here..."
                        class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:borderlack focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">Save Note</button>
                </div>
            </div>

        </Teleport>
    </div>
</template>

<style scoped>
/* Part Header Box */
.part-header-box {
    background-color: #F1F2EC;
}

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(0, 0, 0, 0.1);
    }
    50% {
        background-color: rgba(0, 0, 0, 0.25);
    }
    100% {
        background-color: rgba(0, 0, 0, 0.1);
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

/* Tooltip arrow styles */
.highlight-tooltip {
    position: fixed;
    z-index: 9999;
}

.tooltip-arrow {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

.tooltip-arrow-up {
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    borderottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0, 0, 0, 0.1));
}

.tooltip-arrow-down {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}
</style>

<!-- Non-scoped styles for v-html generated note indicators -->
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

.note-highlight:hover {
    borderottom-color: #000;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
