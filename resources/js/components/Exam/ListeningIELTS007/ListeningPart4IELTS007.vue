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

// Q31-35: text inputs, Q36-40: radio A/B/C
const answers = ref({
    q31: '', q32: '', q33: '', q34: '', q35: '',
    q36: '', q37: '', q38: '', q39: '', q40: '',
});

// Highlight / note state
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
    { id: 'part4-title',        text: 'Part 4',                                                                  offset: 0 },
    { id: 'part4-desc',         text: 'Listen and answer Questions 31-40..',                                                             offset: 23 },
    { id: 'part4-instruction',  text: 'Write NO MORE THAN THREE WORDS for each answer.',                                           offset: 54 },

    { id: 'q31-prefix',         text: 'Bilingualism can be defined as having an equal level of communicative',                     offset: 103 },
    { id: 'q31-suffix',         text: 'in two or more languages.',                                                                  offset: 172 },

    { id: 'q32-prefix',         text: 'Early research suggested that bilingualism caused problems with',                           offset: 198 },
    { id: 'q32-suffix',         text: 'and mental development.',                                                                    offset: 261 },

    { id: 'q33-prefix',         text: 'Early research into bilingualism is now rejected because it did not consider the',         offset: 285 },
    { id: 'q33-middle',         text: 'and',                                                                                        offset: 368 },
    { id: 'q33-suffix',         text: 'backgrounds of the children',                                                                offset: 372 },

    { id: 'q34-prefix',         text: 'It is now thought that there is a',                                                         offset: 402 },
    { id: 'q34-suffix',         text: 'bilingualism and cognitive skills in children.',                                             offset: 435 },

    { id: 'q35-prefix',         text: 'Research done by Ellen Bialystok in Canada now suggests that the effects of bilingualism also', offset: 485 },
    { id: 'q35-suffix',         text: 'apply to',                                                                                   offset: 583 },

    { id: 'q36-40-title',       text: 'Questions 36-40',                                                                           offset: 593 },
    { id: 'q36-40-instruction', text: 'Choose the correct letter A, B or C.',                                                      offset: 609 },

    { id: 'q36-question',       text: '36 In Dr Bialystok\u2019s experiment, the subjects had to react according to',              offset: 646 },
    { id: 'q36-opt-a',          text: 'A the colour of the square on the screen.',                                                  offset: 715 },
    { id: 'q36-opt-b',          text: 'B the location of the square on the screen.',                                               offset: 756 },
    { id: 'q36-opt-c',          text: 'C the location of the shift key on the keyboard.',                                          offset: 801 },

    { id: 'q37-question',       text: '37 The experiment demonstrated the \u2018Simon effect\u2019 because it involved a conflict between', offset: 850 },
    { id: 'q37-opt-a',          text: 'A seeing something and reacting to it.',                                                    offset: 940 },
    { id: 'q37-opt-b',          text: 'B producing fast and slow reactions.',                                                      offset: 978 },
    { id: 'q37-opt-c',          text: 'C demonstrating awareness of shape and colour.',                                            offset: 1015 },

    { id: 'q38-question',       text: '38 The experiment shows that, compared with the monolingual subjects, the bilingual subjects', offset: 1061 },
    { id: 'q38-opt-a',          text: 'A were more intelligent.',                                                                   offset: 1159 },
    { id: 'q38-opt-b',          text: 'B had faster reaction times overall.',                                                      offset: 1184 },
    { id: 'q38-opt-c',          text: 'C had more problems with the \u2018Simon effect\u2019.',                                    offset: 1221 },

    { id: 'q39-question',       text: '39 The results of the experiment indicate that bilingual people may be better at',         offset: 1267 },
    { id: 'q39-opt-a',          text: 'A doing different types of tasks at the same time.',                                        offset: 1347 },
    { id: 'q39-opt-b',          text: 'B thinking about several things at once.',                                                  offset: 1394 },
    { id: 'q39-opt-c',          text: 'C focusing only on what is needed to do a task.',                                           offset: 1433 },

    { id: 'q40-question',       text: '40 Dr Bialystok\u2019s first and second experiments both suggest that bilingualism may',   offset: 1481 },
    { id: 'q40-opt-a',          text: 'A slow down the effects of old age on the brain.',                                          offset: 1562 },
    { id: 'q40-opt-b',          text: 'B lead to mental confusion among old people.',                                             offset: 1608 },
    { id: 'q40-opt-c',          text: 'C help old people to stay in better physical condition',                                    offset: 1652 },
]);

// ── Highlight helpers ──────────────────────────────────────────────────────────
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
    for (const ann of annotations) {
        const aStart = Math.max(0, ann.start - segmentOffset);
        const aEnd = Math.min(plainText.length, ann.end - segmentOffset);
        if (aStart < aEnd) {
            const before = result.substring(0, aStart);
            const middle = result.substring(aStart, aEnd);
            const after = result.substring(aEnd);
            result = ann.type === 'note'
                ? `${before}<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${middle}</mark>${after}`
                : `${before}<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${middle}</mark>${after}`;
        }
    }
    return result;
};

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

// ── Selection handler ──────────────────────────────────────────────────────────
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
    <div  ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part4-title"
                    v-html="getHighlightedSegmentById('part4-title')"></h3>
                <p class="text-base text-gray-700 select-text" data-segment-id="part4-desc"
                    v-html="getHighlightedSegmentById('part4-desc')"></p>

            </div>

            <!-- Scrollable content -->
            <div
                class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-4">

                    <!-- ── Q31-35: sentence completion ──────────────────────────────── -->
                    <div class="mb-8 space-y-4">
                        <p class="text-base font-bold text-gray-900 select-text" data-segment-id="part4-instruction"
                    v-html="getHighlightedSegmentById('part4-instruction')"></p>

                        <!-- Q31 -->
                        <div id="question-31" class="relative flex flex-wrap items-center gap-1"
                            @mouseenter="hoveredQuestion = 31" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                @click.stop="toggleFlag(31)"
                                class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(31) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q31-prefix"
                                v-html="getHighlightedSegmentById('q31-prefix')"></span>
                            <input v-model="answers.q31" type="text" placeholder="31" spellcheck="false"
                                autocomplete="off"
                                class="w-36 border border-gray-400 px-2 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q31-suffix"
                                v-html="getHighlightedSegmentById('q31-suffix')"></span>
                        </div>

                        <!-- Q32 -->
                        <div id="question-32" class="relative flex flex-wrap items-center gap-1"
                            @mouseenter="hoveredQuestion = 32" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                @click.stop="toggleFlag(32)"
                                class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(32) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q32-prefix"
                                v-html="getHighlightedSegmentById('q32-prefix')"></span>
                            <input v-model="answers.q32" type="text" placeholder="32" spellcheck="false"
                                autocomplete="off"
                                class="w-36 border border-gray-400 px-2 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q32-suffix"
                                v-html="getHighlightedSegmentById('q32-suffix')"></span>
                        </div>

                        <!-- Q33: prefix + input + middle + input + suffix -->
                        <div id="question-33" class="relative flex flex-wrap items-center gap-1"
                            @mouseenter="hoveredQuestion = 33" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                @click.stop="toggleFlag(33)"
                                class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(33) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q33-prefix"
                                v-html="getHighlightedSegmentById('q33-prefix')"></span>
                            <input v-model="answers.q33" type="text" placeholder="33" spellcheck="false"
                                autocomplete="off"
                                class="w-36 border border-gray-400 px-2 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q33-middle"
                                v-html="getHighlightedSegmentById('q33-middle')"></span>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q33-suffix"
                                v-html="getHighlightedSegmentById('q33-suffix')"></span>
                        </div>

                        <!-- Q34 -->
                        <div id="question-34" class="relative flex flex-wrap items-center gap-1"
                            @mouseenter="hoveredQuestion = 34" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                @click.stop="toggleFlag(34)"
                                class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(34) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q34-prefix"
                                v-html="getHighlightedSegmentById('q34-prefix')"></span>
                            <input v-model="answers.q34" type="text" placeholder="34" spellcheck="false"
                                autocomplete="off"
                                class="w-36 border border-gray-400 px-2 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q34-suffix"
                                v-html="getHighlightedSegmentById('q34-suffix')"></span>
                        </div>

                        <!-- Q35 -->
                        <div id="question-35" class="relative flex flex-wrap items-center gap-1"
                            @mouseenter="hoveredQuestion = 35" @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                @click.stop="toggleFlag(35)"
                                class="absolute -top-1 right-0 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(35) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="text-base text-gray-800 select-text" data-segment-id="q35-prefix"
                                v-html="getHighlightedSegmentById('q35-prefix')"></span>
                            <input v-model="answers.q35" type="text" placeholder="35" spellcheck="false"
                                autocomplete="off"
                                class="w-36 border border-gray-400 px-2 py-0.5 text-center text-base placeholder:font-bold placeholder:text-gray-900 focus:border-gray-600 focus:outline-none" />
                            <span class="text-base text-gray-800 select-text" data-segment-id="q35-suffix"
                                v-html="getHighlightedSegmentById('q35-suffix')"></span>
                        </div>

                    </div>

                    <!-- ── Q36-40: radio A/B/C ─────────────────────────────────────── -->
                    <div class="mb-4">
                        <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q36-40-title"
                            v-html="getHighlightedSegmentById('q36-40-title')"></h3>
                        <p class="mb-4 text-base text-gray-700 select-text" data-segment-id="q36-40-instruction"
                            v-html="getHighlightedSegmentById('q36-40-instruction')"></p>
                    </div>

                    <div class="space-y-5">

                        <!-- Q36 -->
                        <div id="question-36" class="relative group" @mouseenter="hoveredQuestion = 36"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                @click.stop="toggleFlag(36)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(36) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q36-question"
                                v-html="getHighlightedSegmentById('q36-question')"></p>
                            <div class="space-y-1 pl-2">
                                <label v-for="opt in ['a','b','c']" :key="opt"
                                    class="flex cursor-pointer items-center gap-2 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q36" :value="opt.toUpperCase()"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" :data-segment-id="`q36-opt-${opt}`"
                                        v-html="getHighlightedSegmentById(`q36-opt-${opt}`)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Q37 -->
                        <div id="question-37" class="relative group" @mouseenter="hoveredQuestion = 37"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                @click.stop="toggleFlag(37)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(37) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q37-question"
                                v-html="getHighlightedSegmentById('q37-question')"></p>
                            <div class="space-y-1 pl-2">
                                <label v-for="opt in ['a','b','c']" :key="opt"
                                    class="flex cursor-pointer items-center gap-2 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q37" :value="opt.toUpperCase()"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" :data-segment-id="`q37-opt-${opt}`"
                                        v-html="getHighlightedSegmentById(`q37-opt-${opt}`)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Q38 -->
                        <div id="question-38" class="relative group" @mouseenter="hoveredQuestion = 38"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                @click.stop="toggleFlag(38)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(38) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q38-question"
                                v-html="getHighlightedSegmentById('q38-question')"></p>
                            <div class="space-y-1 pl-2">
                                <label v-for="opt in ['a','b','c']" :key="opt"
                                    class="flex cursor-pointer items-center gap-2 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q38" :value="opt.toUpperCase()"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" :data-segment-id="`q38-opt-${opt}`"
                                        v-html="getHighlightedSegmentById(`q38-opt-${opt}`)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Q39 -->
                        <div id="question-39" class="relative group" @mouseenter="hoveredQuestion = 39"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                @click.stop="toggleFlag(39)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(39) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q39-question"
                                v-html="getHighlightedSegmentById('q39-question')"></p>
                            <div class="space-y-1 pl-2">
                                <label v-for="opt in ['a','b','c']" :key="opt"
                                    class="flex cursor-pointer items-center gap-2 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q39" :value="opt.toUpperCase()"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" :data-segment-id="`q39-opt-${opt}`"
                                        v-html="getHighlightedSegmentById(`q39-opt-${opt}`)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Q40 -->
                        <div id="question-40" class="relative group" @mouseenter="hoveredQuestion = 40"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                @click.stop="toggleFlag(40)"
                                class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(40) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="q40-question"
                                v-html="getHighlightedSegmentById('q40-question')"></p>
                            <div class="space-y-1 pl-2">
                                <label v-for="opt in ['a','b','c']" :key="opt"
                                    class="flex cursor-pointer items-center gap-2 py-1 hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q40" :value="opt.toUpperCase()"
                                        class="h-4 w-4 flex-shrink-0 accent-black" />
                                    <span class="text-base text-gray-800 select-text" :data-segment-id="`q40-opt-${opt}`"
                                        v-html="getHighlightedSegmentById(`q40-opt-${opt}`)"></span>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Teleport overlays ───────────────────────────────────────────────────── -->
    <Teleport to="body">

        <!-- Highlight context menu -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 hover:bg-gray-50"
                        title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 hover:bg-gray-50"
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
                        class="flex flex-col items-center justify-center px-4 py-3 hover:bg-gray-50">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                        <span class="mt-1.5 text-xs font-medium text-gray-600">Delete</span>
                        <span class="text-xs font-medium text-gray-600">Highlight</span>
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
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 hover:bg-red-50">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
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
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-base focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
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
.part-header-box { background-color: #F1F2EC; }

.highlight-question {
    background-color: rgba(0,0,0,0.08);
    border-radius: 4px;
}

.select-text {
    user-select: text;
    -webkit-user-select: text;
}

mark[data-highlight-id] { padding: 2px 0; border-radius: 2px; cursor: pointer; }
.highlight-yellow { background-color: rgba(254,240,138,0.5); }
.highlight-green  { background-color: rgba(187,247,208,0.5); }
.highlight-blue   { background-color: rgba(191,219,254,0.5); }
.highlight-pink   { background-color: rgba(251,207,232,0.5); }
.highlight-orange { background-color: rgba(254,215,170,0.5); }

.tooltip-arrow {
    position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.tooltip-arrow-up {
    position: absolute; top: -8px; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.tooltip-arrow-down {
    position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
</style>

<style>
.note-highlight {
    border-bottom: 2px solid rgba(0,0,0,0.4);
    cursor: help; position: relative; display: inline;
}
.note-highlight::after {
    content: '📝'; display: inline-block; margin-left: 2px;
    font-size: 0.7em; vertical-align: super; line-height: 0; opacity: 0.9; pointer-events: none;
}
.note-highlight:hover { border-bottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>
