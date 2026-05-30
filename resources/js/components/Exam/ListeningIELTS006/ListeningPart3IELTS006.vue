<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const difficultyOptions = [
    { value: 'A', label: 'Obtaining permission' },
    { value: 'B', label: 'Deciding on a suitable focus' },
    { value: 'C', label: 'Concentrating while gathering data' },
    { value: 'D', label: 'Working collaboratively' },
    { value: 'E', label: 'Processing data she had gathered' },
    { value: 'F', label: 'Finding a suitable time to conduct the research' },
    { value: 'G', label: 'Getting hold of suitable equipment' },
];

const handleDragStart = (e: DragEvent, option: string) => {
    draggedOption.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleDragEnd = () => {
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const handleDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
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

const getOptionLabel = (value: string): string => {
    const option = difficultyOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

const availableOptions = computed(() => {
    const usedOptions = [
        answers.value.q26, answers.value.q27, answers.value.q28,
        answers.value.q29, answers.value.q30,
    ].filter(Boolean);
    return difficultyOptions.filter((opt) => !usedOptions.includes(opt.value));
});

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
    { id: 'part3-title', text: 'Part 3', offset: 0 },
    { id: 'part3-desc', text: 'Listen and answer questions 21–30.', offset: 6 },
    { id: 2, text: 'Questions 21-25', offset: 61 },
    { id: 3, text: 'Choose the correct letter, A, B or C.', offset: 77 },
    { id: 'section-sub', text: 'Research project on attitudes towards study', offset: 115 },
    { id: 'q21-num', text: '21.', offset: 160 },
    { id: 'q22-num', text: '22.', offset: 164 },
    { id: 'q23-num', text: '23.', offset: 168 },
    { id: 'q24-num', text: '24.', offset: 172 },
    { id: 'q25-num', text: '25.', offset: 176 },
    { id: 4, text: "Phoebe's main reason for choosing her topic was that", offset: 180 },
    { id: 5, text: 'her classmates had been very interested in it.', offset: 235 },
    { id: 6, text: 'it would help prepare her for her first teaching post.', offset: 284 },
    { id: 7, text: 'she had been inspired by a particular book.', offset: 341 },
    { id: 8, text: "Phoebe's main research question related to", offset: 387 },
    { id: 9, text: 'the effect of teacher discipline.', offset: 432 },
    { id: 10, text: 'the variety of learning activities.', offset: 467 },
    { id: 11, text: 'levels of pupil confidence.', offset: 505 },
    { id: 12, text: 'Phoebe was most surprised by her finding that', offset: 534 },
    { id: 13, text: 'gender did not influence behaviour significantly.', offset: 582 },
    { id: 14, text: 'girls were more negative about school than boys.', offset: 633 },
    { id: 15, text: 'boys were more talkative than girls in class.', offset: 684 },
    { id: 16, text: 'Regarding teaching, Phoebe says she has learned that', offset: 732 },
    { id: 17, text: 'teachers should be flexible in their lesson planning.', offset: 787 },
    { id: 18, text: 'brighter children learn from supporting weaker ones.', offset: 843 },
    { id: 19, text: 'children vary from each other in unpredictable ways.', offset: 898 },
    { id: 20, text: "Tony is particularly impressed by Phoebe's ability to", offset: 953 },
    { id: 21, text: 'recognise the limitations of such small-scale research.', offset: 1009 },
    { id: 22, text: 'reflect on her own research experience in an interesting way.', offset: 1067 },
    { id: 23, text: 'design her research in such a way as to minimise difficulties.', offset: 1130 },
    { id: 24, text: 'Questions 26-30', offset: 1195 },
    { id: 25, text: 'What did Phoebe find difficult about the different research techniques she used?', offset: 1211 },
    { id: 'q2630-inst', text: 'Choose FIVE answers from the box and write the correct letter A-G, next to questions 26-30.', offset: 1292 },
    { id: 'diff-title', text: 'Difficulties', offset: 1386 },
    { id: 35, text: 'Observing lessons', offset: 1400 },
    { id: 36, text: 'Interviewing teachers', offset: 1420 },
    { id: 37, text: 'Interviewing pupils', offset: 1443 },
    { id: 38, text: 'Using questionnaires', offset: 1465 },
    { id: 39, text: 'Taking photographs', offset: 1488 },
]);

// ─── HIGHLIGHT FIX: escape helper ────────────────────────────────────────────
const escapeHtml = (str: string) =>
    str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

// ─── HIGHLIGHT FIX: cursor-based renderer (no in-place string mutation) ──────
const getHighlightedSegmentById = (segmentId: number | string) => {
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
        return escapeHtml(plainText);
    }

    // Build annotation spans clamped to this segment's local coordinate space
    type Span = {
        start: number;
        end: number;
        type: 'highlight' | 'note';
        color: string;
        id: number;
        noteText?: string;
    };

    const spans: Span[] = [
        ...overlappingHighlights.map((h) => ({
            start: Math.max(0, h.start_offset - segmentOffset),
            end: Math.min(plainText.length, h.end_offset - segmentOffset),
            type: 'highlight' as const,
            color: h.color,
            id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: Math.max(0, n.start - segmentOffset),
            end: Math.min(plainText.length, n.end - segmentOffset),
            type: 'note' as const,
            color: 'blue',
            id: n.id,
            noteText: n.note,
        })),
    ]
        // Drop any span that has no actual overlap within this segment
        .filter((s) => s.start < s.end)
        // Sort ascending by start so we can walk left-to-right with a cursor
        .sort((a, b) => a.start - b.start || a.end - b.end);

    // Walk plainText with a cursor, emitting plain text for gaps and
    // <mark> tags for annotated regions. We always slice from plainText
    // (never the accumulating result string), so indices never drift.
    let cursor = 0;
    let result = '';

    for (const span of spans) {
        // Emit any plain text between the cursor and this span
        if (span.start > cursor) {
            result += escapeHtml(plainText.slice(cursor, span.start));
        }

        // Only emit the span if it starts at or after the cursor
        // (overlapping / nested spans are skipped to keep first-annotation wins)
        if (span.start >= cursor) {
            const inner = escapeHtml(plainText.slice(span.start, span.end));
            result +=
                span.type === 'note'
                    ? `<mark class="highlight-${span.color} note-highlight" data-note-id="${span.id}">${inner}</mark>`
                    : `<mark class="highlight-${span.color}" data-highlight-id="${span.id}">${inner}</mark>`;
            cursor = span.end;
        }
    }

    // Emit any remaining plain text after the last annotation
    if (cursor < plainText.length) {
        result += escapeHtml(plainText.slice(cursor));
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
                    const segId = /^\d+$/.test(attr) ? parseInt(attr, 10) : attr;
                    const segment = textSegments.value.find((s) => s.id === segId);
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

defineExpose({ getAnswers, answers: answers.value, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part3-title"
                    v-html="getHighlightedSegmentById('part3-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part3-desc"
                    v-html="getHighlightedSegmentById('part3-desc')"></p>
            </div>

            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-3 sm:space-y-4 md:space-y-6">

                        <!-- Section header Q21-25 -->
                        <div>
                            <h3 class="text-lg font-bold text-black select-text" data-segment-id="2"
                                v-html="getHighlightedSegmentById(2)"></h3>
                            <p class="text-sm text-gray-900 select-text" data-segment-id="3"
                                v-html="getHighlightedSegmentById(3)"></p>
                            <p class="mt-1 text-sm font-semibold text-gray-800 select-text" data-segment-id="section-sub"
                                v-html="getHighlightedSegmentById('section-sub')"></p>
                        </div>

                        <!-- ───── Q21 ───── -->
<div id="question-21" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 21"
    @mouseleave="hoveredQuestion = null">
    <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
        @click.stop="toggleFlag(21)"
        class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
        :class="isQuestionFlagged(21) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
        </svg>
    </button>
    <div class="flex items-start">
        <span class="mr-1 text-base font-bold select-text">21.</span>
        <div class="min-w-0 flex-1">
            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="4"
                v-html="getHighlightedSegmentById(4)"></p>
            <div class="space-y-0.5">
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q21" value="A"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q21" value="B"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q21" value="C"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- ───── Q22 ───── -->
<div id="question-22" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 22"
    @mouseleave="hoveredQuestion = null">
    <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
        @click.stop="toggleFlag(22)"
        class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
        :class="isQuestionFlagged(22) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
        </svg>
    </button>
    <div class="flex items-start">
        <span class="mr-1 text-base font-bold select-text">22.</span>
        <div class="min-w-0 flex-1">
            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="8"
                v-html="getHighlightedSegmentById(8)"></p>
            <div class="space-y-0.5">
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q22" value="A"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q22" value="B"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q22" value="C"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- ───── Q23 ───── -->
<div id="question-23" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 23"
    @mouseleave="hoveredQuestion = null">
    <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
        @click.stop="toggleFlag(23)"
        class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
        :class="isQuestionFlagged(23) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
        </svg>
    </button>
    <div class="flex items-start">
        <span class="mr-1 text-base font-bold select-text">23.</span>
        <div class="min-w-0 flex-1">
            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="12"
                v-html="getHighlightedSegmentById(12)"></p>
            <div class="space-y-0.5">
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q23" value="A"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q23" value="B"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q23" value="C"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- ───── Q24 ───── -->
<div id="question-24" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 24"
    @mouseleave="hoveredQuestion = null">
    <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
        @click.stop="toggleFlag(24)"
        class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
        :class="isQuestionFlagged(24) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
        </svg>
    </button>
    <div class="flex items-start">
        <span class="mr-1 text-base font-bold select-text">24.</span>
        <div class="min-w-0 flex-1">
            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="16"
                v-html="getHighlightedSegmentById(16)"></p>
            <div class="space-y-0.5">
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q24" value="A"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q24" value="B"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q24" value="C"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- ───── Q25 ───── -->
<div id="question-25" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 25"
    @mouseleave="hoveredQuestion = null">
    <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
        @click.stop="toggleFlag(25)"
        class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
        :class="isQuestionFlagged(25) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
        </svg>
    </button>
    <div class="flex items-start">
        <span class="mr-1 text-base font-bold select-text">25.</span>
        <div class="min-w-0 flex-1">
            <p class="mb-2 text-base font-medium text-gray-800 select-text" data-segment-id="20"
                v-html="getHighlightedSegmentById(20)"></p>
            <div class="space-y-0.5">
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q25" value="A"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q25" value="B"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                    <input type="radio" v-model="answers.q25" value="C"
                        class="h-4 w-4 flex-shrink-0 accent-black" />
                    <span class=" text-gray-800 select-text"
                        data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                </label>
            </div>
        </div>
    </div>
</div>

                        <!-- ───── Q26-30 ───── -->
                        <div class="p-2 sm:p-3">
                            <div class="mb-6">
                                <h3 class="text-lg font-bold text-black select-text" data-segment-id="24"
                                    v-html="getHighlightedSegmentById(24)"></h3>
                                <div class="mt-1 space-y-1">
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="25"
                                        v-html="getHighlightedSegmentById(25)"></p>
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="q2630-inst"
                                        v-html="getHighlightedSegmentById('q2630-inst')"></p>
                                </div>
                            </div>

                            <div class="flex gap-12">
                                <!-- Drop zones -->
                                <div class="space-y-5">

                                    <!-- Q26 -->
                                    <div id="question-26" class="flex items-center gap-3"
                                        @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-700 select-text" data-segment-id="35"
                                            v-html="getHighlightedSegmentById(35)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 26 ? 'border-blue-500 bg-blue-50' : answers.q26 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 26)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 26)" @click="clearAnswer(26)"
                                            :title="answers.q26 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q26">{{ getOptionLabel(answers.q26) }}</span>
                                            <span v-else class="font-bold text-gray-900">26</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                                @click.stop="toggleFlag(26)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(26) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q27 -->
                                    <div id="question-27" class="flex items-center gap-3"
                                        @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-700 select-text" data-segment-id="36"
                                            v-html="getHighlightedSegmentById(36)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 27 ? 'border-blue-500 bg-blue-50' : answers.q27 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 27)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 27)" @click="clearAnswer(27)"
                                            :title="answers.q27 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q27">{{ getOptionLabel(answers.q27) }}</span>
                                            <span v-else class="font-bold text-gray-900">27</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                                @click.stop="toggleFlag(27)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(27) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q28 -->
                                    <div id="question-28" class="flex items-center gap-3"
                                        @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-700 select-text" data-segment-id="37"
                                            v-html="getHighlightedSegmentById(37)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 28 ? 'border-blue-500 bg-blue-50' : answers.q28 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 28)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 28)" @click="clearAnswer(28)"
                                            :title="answers.q28 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q28">{{ getOptionLabel(answers.q28) }}</span>
                                            <span v-else class="font-bold text-gray-900">28</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                                @click.stop="toggleFlag(28)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(28) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q29 -->
                                    <div id="question-29" class="flex items-center gap-3"
                                        @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-700 select-text" data-segment-id="38"
                                            v-html="getHighlightedSegmentById(38)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 29 ? 'border-blue-500 bg-blue-50' : answers.q29 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 29)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 29)" @click="clearAnswer(29)"
                                            :title="answers.q29 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q29">{{ getOptionLabel(answers.q29) }}</span>
                                            <span v-else class="font-bold text-gray-900">29</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                                @click.stop="toggleFlag(29)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(29) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q30 -->
                                    <div id="question-30" class="flex items-center gap-3"
                                        @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base text-gray-700 select-text" data-segment-id="39"
                                            v-html="getHighlightedSegmentById(39)"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 30 ? 'border-blue-500 bg-blue-50' : answers.q30 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 30)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 30)" @click="clearAnswer(30)"
                                            :title="answers.q30 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q30">{{ getOptionLabel(answers.q30) }}</span>
                                            <span v-else class="font-bold text-gray-900">30</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                                @click.stop="toggleFlag(30)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(30) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                </div>

                                <!-- Draggable options panel -->
                                <div class="w-64 shrink-0 self-start sticky top-12">
                                    <p class="mb-3 text-sm font-semibold text-gray-700">Drag options to fill blanks:</p>
                                    <div class="border border-gray-900 p-3 bg-white">
                                        <div class="space-y-2">
                                            <div v-for="option in availableOptions" :key="option.value"
                                                class="flex cursor-grab items-center gap-2 rounded border border-gray-300 px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                :class="{ 'opacity-50': draggedOption === option.value }"
                                                draggable="true" @dragstart="handleDragStart($event, option.value)"
                                                @dragend="handleDragEnd">
                                                <span class="font-bold text-gray-900 text-sm">{{ option.value }}</span>
                                                <span class="text-gray-700 text-sm">{{ option.label }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                            <path
                                d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
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
                    <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 hover:bg-red-50"
                        title="Delete note">
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
                <p
                    class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"</p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
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
    0% { background-color: rgba(0, 0, 0, 0.1); }
    50% { background-color: rgba(0, 0, 0, 0.25); }
    100% { background-color: rgba(0, 0, 0, 0.1); }
}

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #000; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #374151; }

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
    border-bottom: 8px solid white;
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

.note-highlight:hover { border-bottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>