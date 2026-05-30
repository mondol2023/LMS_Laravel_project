<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

// Q11-16 options: A–I, duplicates allowed (same option can be used multiple times? 
// Actually: choose SIX from A–I, each letter used once. But we keep same as original: options never removed.
const moduleOptions = [
    { value: 'A', label: 'on washing machine' },
    { value: 'B', label: 'in hallway cupboard' },
    { value: 'C', label: 'in hot water cupboard' },
    { value: 'D', label: 'next to back door' },
    { value: 'E', label: 'in bathroom' },
    { value: 'F', label: 'on top of television' },
    { value: 'G', label: 'in bedroom' },
    { value: 'H', label: 'under kitchen sink' },
    { value: 'I', label: 'above front door' },
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

const getModuleOptionLabel = (value: string): string => {
    const option = moduleOptions.find((opt) => opt.value === value);
    return option ? `${option.value} – ${option.label}` : '';
};

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
    q11: '', q12: '', q13: '', q14: '', q15: '', q16: '',
    q17: '', q18: '', q19: '', q20: '',
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
    // Header
    { id: 'sec2-title', text: 'Part 2', offset: 0 },
    { id: 'sec2-subtitle', text: 'Listen and answer questions 11–20.', offset: 7 },
    { id: 'sec2-qrange', text: 'Questions 11–16', offset: 42 },
    { id: 'sec2-inst', text: 'Choose SIX answers from the box and write the correct letter A–I.', offset: 58 },

    // Box options
    { id: 'box-a', text: 'A. on washing machine', offset: 124 },
    { id: 'box-b', text: 'B. in hallway cupboard', offset: 146 },
    { id: 'box-c', text: 'C. in hot water cupboard', offset: 169 },
    { id: 'box-d', text: 'D. next to back door', offset: 194 },
    { id: 'box-e', text: 'E. in bathroom', offset: 215 },
    { id: 'box-f', text: 'F. on top of television', offset: 230 },
    { id: 'box-g', text: 'G. in bedroom', offset: 254 },
    { id: 'box-h', text: 'H. under kitchen sink', offset: 268 },
    { id: 'box-i', text: 'I. above front door', offset: 290 },

    // Q11-16 labels
    { id: 'q11-label', text: 'Alarm', offset: 310 },
    { id: 'q12-label', text: 'Garage key', offset: 316 },
    { id: 'q13-label', text: 'Laundry detergent', offset: 327 },
    { id: 'q14-label', text: 'Beach towels', offset: 345 },
    { id: 'q15-label', text: 'Bath towels', offset: 358 },
    { id: 'q16-label', text: 'Light bulbs', offset: 370 },

    // Q17-20 section
    { id: 'sec2-qrange2', text: 'Questions 17–20', offset: 382 },
    { id: 'sec2-inst2', text: 'Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.', offset: 398 },

    { id: 'q17-stem', text: 'There is difficult parking in town at the weekend because of so many', offset: 460 },
    { id: 'q18-stem', text: 'The museum is closed on', offset: 529 },
    { id: 'q19-stem', text: 'Recommended places to eat:', offset: 580 },
    { id: 'q19-tail', text: 'for Chinese food Pizzeria for Italian food.', offset: 607 },
    { id: 'q20-stem', text: 'Phone number for takeaway pizza:', offset: 624 },
]);

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

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return plainText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset, end: h.end_offset,
            type: 'highlight' as const, color: h.color, id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start, end: n.end,
            type: 'note' as const, color: 'blue', id: n.id, noteText: n.note,
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

const usedOptions = computed(() => {
    return new Set([
        answers.value.q11, answers.value.q12, answers.value.q13,
        answers.value.q14, answers.value.q15, answers.value.q16,
    ].filter(Boolean));
});

const availableOptions = computed(() => 
    moduleOptions.filter(opt => !usedOptions.value.has(opt.value))
);

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

            <!-- ── Part Header: "Part 2" + subtitle ── -->
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3" style="background-color: #F1F2EC;">
                <h3 class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="sec2-title"
                    v-html="getHighlightedSegmentById('sec2-title')"></h3>
                <p class="text-sm text-gray-700 select-text"
                    data-segment-id="sec2-subtitle"
                    v-html="getHighlightedSegmentById('sec2-subtitle')"></p>
            </div>

            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-3 sm:space-y-4 md:space-y-6">

                        <!-- ── Q11-16 sub-header ── -->
                        <div>
                            <p class="text-base font-bold text-gray-900 select-text"
                                data-segment-id="sec2-qrange"
                                v-html="getHighlightedSegmentById('sec2-qrange')"></p>
                            <p class="text-sm text-gray-700 select-text"
                                data-segment-id="sec2-inst"
                                v-html="getHighlightedSegmentById('sec2-inst')"></p>
                        </div>

                        <!-- ───── Q11-16 ───── -->
                        <div class="p-2 sm:p-3">
                            <!-- Drop zones + options side-by-side -->
                            <div class="flex flex-wrap">
                                <!-- Drop zones for Q11-16 -->
                                <div class="space-y-5 min-w-0 pr-4">

                                    <!-- Q11 -->
                                    <div id="question-11" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none">•</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="q11-label"
                                            v-html="getHighlightedSegmentById('q11-label')"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 11 ? 'border-blue-500 bg-blue-50' : answers.q11 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 11)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 11)" @click="clearAnswer(11)"
                                            :title="answers.q11 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q11">{{ getModuleOptionLabel(answers.q11) }}</span>
                                            <span v-else class="font-bold text-gray-900">11</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                                @click.stop="toggleFlag(11)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(11) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q12 -->
                                    <div id="question-12" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none">•</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="q12-label"
                                            v-html="getHighlightedSegmentById('q12-label')"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 12 ? 'border-blue-500 bg-blue-50' : answers.q12 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 12)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 12)" @click="clearAnswer(12)"
                                            :title="answers.q12 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q12">{{ getModuleOptionLabel(answers.q12) }}</span>
                                            <span v-else class="font-bold text-gray-900">12</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                                @click.stop="toggleFlag(12)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(12) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q13 -->
                                    <div id="question-13" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none">•</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="q13-label"
                                            v-html="getHighlightedSegmentById('q13-label')"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 13 ? 'border-blue-500 bg-blue-50' : answers.q13 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 13)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 13)" @click="clearAnswer(13)"
                                            :title="answers.q13 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q13">{{ getModuleOptionLabel(answers.q13) }}</span>
                                            <span v-else class="font-bold text-gray-900">13</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                                @click.stop="toggleFlag(13)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(13) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q14 -->
                                    <div id="question-14" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none">•</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="q14-label"
                                            v-html="getHighlightedSegmentById('q14-label')"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 14 ? 'border-blue-500 bg-blue-50' : answers.q14 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 14)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 14)" @click="clearAnswer(14)"
                                            :title="answers.q14 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q14">{{ getModuleOptionLabel(answers.q14) }}</span>
                                            <span v-else class="font-bold text-gray-900">14</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                                @click.stop="toggleFlag(14)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(14) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q15 -->
                                    <div id="question-15" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none">•</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="q15-label"
                                            v-html="getHighlightedSegmentById('q15-label')"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 15 ? 'border-blue-500 bg-blue-50' : answers.q15 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 15)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 15)" @click="clearAnswer(15)"
                                            :title="answers.q15 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q15">{{ getModuleOptionLabel(answers.q15) }}</span>
                                            <span v-else class="font-bold text-gray-900">15</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                                @click.stop="toggleFlag(15)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(15) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Q16 -->
                                    <div id="question-16" class="flex items-center gap-3 flex-wrap"
                                        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                        <span class="text-base font-bold text-gray-900 select-none">•</span>
                                        <span class="text-base text-gray-700 select-text" data-segment-id="q16-label"
                                            v-html="getHighlightedSegmentById('q16-label')"></span>
                                        <span
                                            class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                            :class="dragOverQuestion === 16 ? 'border-blue-500 bg-blue-50' : answers.q16 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 16)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 16)" @click="clearAnswer(16)"
                                            :title="answers.q16 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q16">{{ getModuleOptionLabel(answers.q16) }}</span>
                                            <span v-else class="font-bold text-gray-900">16</span>
                                        </span>
                                        <div class="w-7 h-7 shrink-0">
                                            <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                                @click.stop="toggleFlag(16)"
                                                class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                </div>

                                <!-- Draggable options panel -->
                                <div class="w-56 shrink-0 self-start sticky top-12">
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
                                    <p class="mt-2 text-xs text-gray-500">Click a filled box to clear it.</p>
                                </div>
                            </div>
                        </div>

                        <!-- ── Q17-20 sub-header ── -->
                        <div class="mt-6">
                            <p class="text-base font-bold text-gray-900 select-text"
                                data-segment-id="sec2-qrange2"
                                v-html="getHighlightedSegmentById('sec2-qrange2')"></p>
                            <p class="text-sm text-gray-700 select-text"
                                data-segment-id="sec2-inst2"
                                v-html="getHighlightedSegmentById('sec2-inst2')"></p>
                        </div>

                        <!-- ───── Q17 ───── -->
<div id="question-17" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
    <div class="flex items-start gap-2">
        <span class="text-base font-bold text-gray-900 shrink-0">•</span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-1">
                <span class="text-base font-medium text-gray-800 select-text" data-segment-id="q17-stem"
                    v-html="getHighlightedSegmentById('q17-stem')"></span>
                <input
                    v-model="answers.q17"
                    type="text"
                    spellcheck="false"
                    autocomplete="off"
                    placeholder="17"
                    class="inline-block min-w-36 border border-gray-900 bg-transparent px-1 py-0.5 text-sm font-bold text-center text-gray-900 placeholder-gray-400 focus:border-black focus:outline-none"
                    @click.stop
                />
                <span class="text-base font-medium text-gray-800">.</span>
                <div class="relative inline-flex h-7 w-7 items-center justify-center shrink-0">
                    <button
                        @click.stop="toggleFlag(17)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all duration-200"
                        :class="[
                            (hoveredQuestion === 17 || isQuestionFlagged(17))
                                ? 'opacity-100'
                                : 'opacity-0 pointer-events-none',
                            isQuestionFlagged(17)
                                ? 'border-gray-400 bg-transparent text-red-500'
                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                        ]"
                        :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ───── Q18 ───── -->
<div id="question-18" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
    <div class="flex items-start gap-2">
        <span class="text-base font-bold text-gray-900 shrink-0">•</span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-1">
                <span class="text-base font-medium text-gray-800 select-text" data-segment-id="q18-stem"
                    v-html="getHighlightedSegmentById('q18-stem')"></span>
                <input
                    v-model="answers.q18"
                    type="text"
                    spellcheck="false"
                    autocomplete="off"
                    placeholder="18"
                    class="inline-block min-w-36 border border-gray-900 bg-transparent px-1 py-0.5 text-sm font-bold text-center text-gray-900 placeholder-gray-400 focus:border-black focus:outline-none"
                    @click.stop
                />
                <span class="text-base font-medium text-gray-800">.</span>
                <div class="relative inline-flex h-7 w-7 items-center justify-center shrink-0">
                    <button
                        @click.stop="toggleFlag(18)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all duration-200"
                        :class="[
                            (hoveredQuestion === 18 || isQuestionFlagged(18))
                                ? 'opacity-100'
                                : 'opacity-0 pointer-events-none',
                            isQuestionFlagged(18)
                                ? 'border-gray-400 bg-transparent text-red-500'
                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                        ]"
                        :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ───── Q19 ───── -->
<div id="question-19" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 19" @mouseleave="hoveredQuestion = null">
    <div class="flex items-start gap-2">
        <span class="text-base font-bold text-gray-900 shrink-0">•</span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-1">
                <span class="text-base font-medium text-gray-800 select-text" data-segment-id="q19-stem"
                    v-html="getHighlightedSegmentById('q19-stem')"></span>
                <input
                    v-model="answers.q19"
                    type="text"
                    spellcheck="false"
                    autocomplete="off"
                    placeholder="19"
                    class="inline-block min-w-36 border border-gray-900 bg-transparent px-1 py-0.5 text-sm font-bold text-center text-gray-900 placeholder-gray-400 focus:border-black focus:outline-none"
                    @click.stop
                />
                <span class="text-base font-medium text-gray-800 select-text" data-segment-id="q19-tail"
                    v-html="getHighlightedSegmentById('q19-tail')"></span>
                <div class="relative inline-flex h-7 w-7 items-center justify-center shrink-0">
                    <button
                        @click.stop="toggleFlag(19)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all duration-200"
                        :class="[
                            (hoveredQuestion === 19 || isQuestionFlagged(19))
                                ? 'opacity-100'
                                : 'opacity-0 pointer-events-none',
                            isQuestionFlagged(19)
                                ? 'border-gray-400 bg-transparent text-red-500'
                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                        ]"
                        :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ───── Q20 ───── -->
<div id="question-20" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 20" @mouseleave="hoveredQuestion = null">
    <div class="flex items-start gap-2">
        <span class="text-base font-bold text-gray-900 shrink-0">•</span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-1">
                <span class="text-base font-medium text-gray-800 select-text" data-segment-id="q20-stem"
                    v-html="getHighlightedSegmentById('q20-stem')"></span>
                <input
                    v-model="answers.q20"
                    type="text"
                    spellcheck="false"
                    autocomplete="off"
                    placeholder="20"
                    class="inline-block min-w-36 border border-gray-900 bg-transparent px-1 py-0.5 text-sm font-bold text-center text-gray-900 placeholder-gray-400 focus:border-black focus:outline-none"
                    @click.stop
                />
                <div class="relative inline-flex h-7 w-7 items-center justify-center shrink-0">
                    <button
                        @click.stop="toggleFlag(20)"
                        class="absolute inset-0 flex items-center justify-center rounded border transition-all duration-200"
                        :class="[
                            (hoveredQuestion === 20 || isQuestionFlagged(20))
                                ? 'opacity-100'
                                : 'opacity-0 pointer-events-none',
                            isQuestionFlagged(20)
                                ? 'border-gray-400 bg-transparent text-red-500'
                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                        ]"
                        :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
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
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
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

.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green  { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue   { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink   { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }

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
    border-bottom: 8px solid white;
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