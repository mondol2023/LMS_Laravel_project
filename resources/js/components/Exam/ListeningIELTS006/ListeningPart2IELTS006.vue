<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// ── Drag-and-drop for Q11-16 (A–G letter options) ──────────────────────────
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const steamOptions = [
    { value: 'A', label: 'air' },
    { value: 'B', label: 'ash' },
    { value: 'C', label: 'earth' },
    { value: 'D', label: 'grass' },
    { value: 'E', label: 'sticks' },
    { value: 'F', label: 'stones' },
    { value: 'G', label: 'water' },
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
        (answers.value as any)[key] = option;
    }
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    (answers.value as any)[key] = '';
};

const getOptionLabel = (value: string): string => {
    const option = steamOptions.find((opt) => opt.value === value);
    return option ? `${option.value} – ${option.label}` : '';
};

// Options still available (not yet used in Q11–16)
const availableOptions = computed(() => {
    const used = [
        answers.value.q11, answers.value.q12, answers.value.q13,
        answers.value.q14, answers.value.q15, answers.value.q16,
    ].filter(Boolean);
    return steamOptions.filter((opt) => !used.includes(opt.value));
});

// ── Q17-18 / Q19-20 multi-select (choose TWO) ──────────────────────────────
const bambooOptions = [
    { value: 'A', label: "It's suitable for windy weather." },
    { value: 'B', label: 'The fire is lit below the bottom end of the bamboo.' },
    { value: 'C', label: 'The bamboo is cut into equal lengths.' },
    { value: 'D', label: 'The oven hangs from a stick.' },
    { value: 'E', label: 'It cooks food by steaming it.' },
];

const fungiOptions = [
    { value: 'A', label: "Cooking doesn't make poisonous fungi edible." },
    { value: 'B', label: 'Edible wild fungi can be eaten without cooking.' },
    { value: 'C', label: 'Wild fungi are highly nutritious.' },
    { value: 'D', label: 'Some edible fungi look very similar to poisonous varieties.' },
    { value: 'E', label: 'Fungi which cannot be identified should only be eaten in small quantities.' },
];

const toggleMultiSelect = (
    arr: string[],
    value: string,
    max: number,
    setter: (v: string[]) => void,
) => {
    const idx = arr.indexOf(value);
    if (idx > -1) {
        setter(arr.filter((v) => v !== value));
    } else if (arr.length < max) {
        setter([...arr, value]);
    }
};

// ── Props / emits ───────────────────────────────────────────────────────────
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

// ── Answers ─────────────────────────────────────────────────────────────────
const answers = ref({
    // Q11-16: drag-and-drop letter
    q11: '', q12: '', q13: '', q14: '', q15: '', q16: '',
    // Q17-18: choose TWO from A-E (bamboo oven)
    q17_18: [] as string[],
    // Q19-20: choose TWO from A-E (wild fungi)
    q19_20: [] as string[],
});

// ── Highlight / note infrastructure ────────────────────────────────────────
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

// ── Text segments ───────────────────────────────────────────────────────────
const textSegments = ref([
    { id: 'part2-title', text: 'Part 2', offset: 0 },
    { id: 'part2-desc', text: 'Listen and answer questions 11–20.', offset: 6 },

    // Section 2 header
    { id: 'sec2-header', text: 'Section 2: Questions 11-16', offset: 41 },
    { id: 'sec2-inst1', text: 'Complete the flow chart below.', offset: 67 },
    { id: 'sec2-inst2', text: 'Choose SIX answers from the box and write the correct letter A-G next to questions 11-16.', offset: 98 },
    { id: 'flow-title', text: 'Making a steam pit', offset: 150 },
    // Flow chart static text
    { id: 'flow-1', text: 'Dig a pit', offset: 190 },
    { id: 'flow-2', text: 'Arrange a row of', offset: 200 },
    { id: 'flow-3-suffix', text: 'over the pit', offset: 217 },
    { id: 'flow-4', text: 'Place', offset: 230 },
    { id: 'flow-4-suffix', text: 'on top', offset: 236 },
    { id: 'flow-5', text: 'Light the wood and let it burn out', offset: 243 },
    { id: 'flow-6', text: 'Remove', offset: 278 },
    { id: 'flow-7', text: 'Insert a stick', offset: 285 },
    { id: 'flow-8', text: 'Cover the pit with', offset: 300 },
    { id: 'flow-9', text: 'Place wrapped food on top and cover it with', offset: 319 },
    { id: 'flow-10', text: 'Remove the stick and put', offset: 363 },
    { id: 'flow-10-suffix', text: 'into the hole', offset: 388 },

    // Q17-18 section
    { id: 'q1718-header', text: 'Questions 17-18', offset: 402 },
    { id: 'q1718-inst1', text: 'Choose TWO letters, A-E.', offset: 418 },
    { id: 'q1718-inst2', text: 'Which TWO characteristics apply to the bamboo oven?', offset: 443 },
    { id: 'bamboo-A', text: "It's suitable for windy weather.", offset: 494 },
    { id: 'bamboo-B', text: 'The fire is lit below the bottom end of the bamboo.', offset: 527 },
    { id: 'bamboo-C', text: 'The bamboo is cut into equal lengths.', offset: 579 },
    { id: 'bamboo-D', text: 'The oven hangs from a stick.', offset: 617 },
    { id: 'bamboo-E', text: 'It cooks food by steaming it.', offset: 646 },

    // Q19-20 section
    { id: 'q1920-header', text: 'Questions 19-20', offset: 676 },
    { id: 'q1920-inst1', text: 'Choose TWO letters, A-E.', offset: 692 },
    { id: 'q1920-inst2', text: 'Which TWO pieces of advice does the speaker give about eating wild fungi?', offset: 717 },
    { id: 'fungi-A', text: "Cooking doesn't make poisonous fungi edible.", offset: 789 },
    { id: 'fungi-B', text: 'Edible wild fungi can be eaten without cooking.', offset: 834 },
    { id: 'fungi-C', text: 'Wild fungi are highly nutritious.', offset: 882 },
    { id: 'fungi-D', text: 'Some edible fungi look very similar to poisonous varieties.', offset: 916 },
    { id: 'fungi-E', text: 'Fungi which cannot be identified should only be eaten in small quantities.', offset: 975 },
]);

// Recalculate all text segment offsets based on current DOM
const recalculateTextOffsets = () => {
    if (!contentTextRef.value) return;

    const rawText = contentTextRef.value.textContent || '';
    let currentOffset = 0;

    // Recalculate offsets for each segment
    for (let i = 0; i < textSegments.value.length; i++) {
        const segment = textSegments.value[i];
        const textIndex = rawText.indexOf(segment.text, currentOffset);
        if (textIndex !== -1) {
            textSegments.value[i].offset = textIndex;
            currentOffset = textIndex + segment.text.length;
        }
    }
};

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

// ── Exposed helpers ─────────────────────────────────────────────────────────
// ── Exposed helpers ─────────────────────────────────────────────────────────
const getAnswers = () => {
    const allAnswers: Record<string, string> = {
        q11: answers.value.q11,
        q12: answers.value.q12,
        q13: answers.value.q13,
        q14: answers.value.q14,
        q15: answers.value.q15,
        q16: answers.value.q16,
        // Expand q17_18 array into q17, q18
        q17: answers.value.q17_18[0] || '',
        q18: answers.value.q17_18[1] || '',
        // Expand q19_20 array into q19, q20
        q19: answers.value.q19_20[0] || '',
        q20: answers.value.q19_20[1] || '',
    };
    return allAnswers;
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected || selected.length === 0) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        if (!range) return;

        const rect = range.getBoundingClientRect();

        if (rect && contentTextRef.value) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 58;
            const vw = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), vw - 80),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            // Get the entire text content to calculate offsets
            const fullText = contentTextRef.value.textContent || '';

            // Get the text before the selection to calculate the start offset
            const tempRange = range.cloneRange();
            tempRange.selectNodeContents(contentTextRef.value);
            tempRange.setEnd(range.startContainer, range.startOffset);
            const startOffset = tempRange.toString().length;

            // The selected text length
            const selectedLength = selected.length;

            selectedText.value = selected;
            selectionRange.value = {
                start: startOffset,
                end: startOffset + selectedLength
            };
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
    nextTick(() => {
        recalculateTextOffsets();
    });
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
    nextTick(() => {
        recalculateTextOffsets();
    });
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => {
    notes.value = notes.value.filter((n) => n.id !== id);
    nextTick(() => {
        recalculateTextOffsets();
    });
};
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
        nextTick(() => {
            recalculateTextOffsets();
        });
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

// Watch for changes to highlights and notes to recalculate offsets
watch([highlights, notes], () => {
    nextTick(() => {
        recalculateTextOffsets();
    });
}, { deep: true });

onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);
    nextTick(() => {
        recalculateTextOffsets();
    });
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
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part2-desc"
                    v-html="getHighlightedSegmentById('part2-desc')"></p>
            </div>

            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-6">

                        <!-- ══════════════════════════════════════════════
                             SECTION 2 HEADER
                        ══════════════════════════════════════════════ -->
                        <div>
                            <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="sec2-header"
                                v-html="getHighlightedSegmentById('sec2-header')"></h3>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="sec2-inst1"
                                v-html="getHighlightedSegmentById('sec2-inst1')"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="sec2-inst2"
                                v-html="getHighlightedSegmentById('sec2-inst2')"></p>


                        </div>

                        <!-- ══════════════════════════════════════════════
                             FLOW CHART  Q11-16
                        ══════════════════════════════════════════════ -->
                        <div class="flex gap-6">

                            <!-- Flow chart column -->
                            <div class="flex-1">
                                <h2 data-segment-id="flow-title" v-html="getHighlightedSegmentById('flow-title')"
                                    class="mb-4 text-center text-xl font-bold text-gray-900"></h2>

                                <div class="flex flex-col items-center gap-0">

                                    <!-- Static: Dig a pit -->
                                    <div class="flow-box w-full">
                                        <span class="select-text" data-segment-id="flow-1"
                                            v-html="getHighlightedSegmentById('flow-1')"></span>
                                    </div>
                                    <div class="flow-arrow">▼</div>

                                    <!-- Q11: Arrange a row of [11] over the pit -->
                                    <div id="question-11" class="flow-box w-full relative"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="toggleFlag(11)"
                                            class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="select-text" data-segment-id="flow-2"
                                            v-html="getHighlightedSegmentById('flow-2')"></span>

                                        <span
                                            class="inline-flex min-h-7 min-w-32 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-sm transition-all mx-1"
                                            :class="dragOverQuestion === 11 ? 'border-blue-500 bg-blue-50' : answers.q11 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 11)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 11)" @click.stop="clearAnswer(11)"
                                            :title="answers.q11 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q11">{{ getOptionLabel(answers.q11) }}</span>
                                            <span v-else class="text-gray-400 text-xs">11</span>
                                        </span>
                                        <span class="select-text" data-segment-id="flow-3-suffix"
                                            v-html="getHighlightedSegmentById('flow-3-suffix')"></span>
                                    </div>
                                    <div class="flow-arrow">▼</div>

                                    <!-- Q12: Place [12] on top -->
                                    <div id="question-12" class="flow-box w-full relative"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="select-text" data-segment-id="flow-4"
                                            v-html="getHighlightedSegmentById('flow-4')"></span>

                                        <span
                                            class="inline-flex min-h-7 min-w-32 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-sm transition-all mx-1"
                                            :class="dragOverQuestion === 12 ? 'border-blue-500 bg-blue-50' : answers.q12 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 12)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 12)" @click.stop="clearAnswer(12)"
                                            :title="answers.q12 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q12">{{ getOptionLabel(answers.q12) }}</span>
                                            <span v-else class="text-gray-400 text-xs">12</span>
                                        </span>
                                        <span class="select-text" data-segment-id="flow-4-suffix"
                                            v-html="getHighlightedSegmentById('flow-4-suffix')"></span>
                                    </div>
                                    <div class="flow-arrow">▼</div>

                                    <!-- Static: Light the wood -->
                                    <div class="flow-box w-full">
                                        <span class="select-text" data-segment-id="flow-5"
                                            v-html="getHighlightedSegmentById('flow-5')"></span>
                                    </div>
                                    <div class="flow-arrow">▼</div>

                                    <!-- Q13: Remove [13] -->
                                    <div id="question-13" class="flow-box w-full relative"
                                        @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="select-text" data-segment-id="flow-6"
                                            v-html="getHighlightedSegmentById('flow-6')"></span>

                                        <span
                                            class="inline-flex min-h-7 min-w-32 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-sm transition-all mx-1"
                                            :class="dragOverQuestion === 13 ? 'border-blue-500 bg-blue-50' : answers.q13 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 13)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 13)" @click.stop="clearAnswer(13)"
                                            :title="answers.q13 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q13">{{ getOptionLabel(answers.q13) }}</span>
                                            <span v-else class="text-gray-400 text-xs">13</span>
                                        </span>
                                    </div>
                                    <div class="flow-arrow">▼</div>

                                    <!-- Static: Insert a stick -->
                                    <div class="flow-box w-full">
                                        <span class="select-text" data-segment-id="flow-7"
                                            v-html="getHighlightedSegmentById('flow-7')"></span>
                                    </div>
                                    <div class="flow-arrow">▼</div>

                                    <!-- Q14: Cover the pit with [14] -->
                                    <div id="question-14" class="flow-box w-full relative"
                                        @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="select-text" data-segment-id="flow-8"
                                            v-html="getHighlightedSegmentById('flow-8')"></span>

                                        <span
                                            class="inline-flex min-h-7 min-w-32 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-sm transition-all mx-1"
                                            :class="dragOverQuestion === 14 ? 'border-blue-500 bg-blue-50' : answers.q14 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 14)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 14)" @click.stop="clearAnswer(14)"
                                            :title="answers.q14 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q14">{{ getOptionLabel(answers.q14) }}</span>
                                            <span v-else class="text-gray-400 text-xs">14</span>
                                        </span>
                                    </div>
                                    <div class="flow-arrow">▼</div>

                                    <!-- Q15: Place wrapped food ... cover it with [15] -->
                                    <div id="question-15" class="flow-box w-full relative"
                                        @mouseenter="hoveredQuestion = 15" @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="toggleFlag(15)"
                                            class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="select-text" data-segment-id="flow-9"
                                            v-html="getHighlightedSegmentById('flow-9')"></span>

                                        <span
                                            class="inline-flex min-h-7 min-w-32 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-sm transition-all mx-1"
                                            :class="dragOverQuestion === 15 ? 'border-blue-500 bg-blue-50' : answers.q15 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 15)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 15)" @click.stop="clearAnswer(15)"
                                            :title="answers.q15 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q15">{{ getOptionLabel(answers.q15) }}</span>
                                            <span v-else class="text-gray-400 text-xs">15</span>
                                        </span>
                                    </div>
                                    <div class="flow-arrow">▼</div>

                                    <!-- Q16: Remove the stick and put [16] into the hole -->
                                    <div id="question-16" class="flow-box w-full relative"
                                        @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="toggleFlag(16)"
                                            class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                            </svg>
                                        </button>
                                        <span class="select-text" data-segment-id="flow-10"
                                            v-html="getHighlightedSegmentById('flow-10')"></span>

                                        <span
                                            class="inline-flex min-h-7 min-w-32 items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-center text-sm transition-all mx-1"
                                            :class="dragOverQuestion === 16 ? 'border-blue-500 bg-blue-50' : answers.q16 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                            @dragover="handleDragOver($event, 16)" @dragleave="handleDragLeave"
                                            @drop="handleDrop($event, 16)" @click.stop="clearAnswer(16)"
                                            :title="answers.q16 ? 'Click to clear' : 'Drop answer here'">
                                            <span v-if="answers.q16">{{ getOptionLabel(answers.q16) }}</span>
                                            <span v-else class="text-gray-400 text-xs">16</span>
                                        </span>
                                        <span class="select-text" data-segment-id="flow-10-suffix"
                                            v-html="getHighlightedSegmentById('flow-10-suffix')"></span>
                                    </div>

                                </div>
                            </div>

                            <!-- Draggable options panel -->
                            <div class="w-48  shrink-0 self-start sticky top-12 mt-32">
                                <p class="mb-2 text-xs font-semibold text-gray-600">Drag to fill blanks:</p>
                                <div class="border border-gray-900 p-2 bg-white">
                                    <div class="space-y-1.5">
                                        <div v-for="option in availableOptions" :key="option.value"
                                            class="flex cursor-grab items-center gap-2 rounded border border-gray-300 px-2 py-1.5 text-sm transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                            :class="{ 'opacity-50': draggedOption === option.value }" draggable="true"
                                            @dragstart="handleDragStart($event, option.value)" @dragend="handleDragEnd">
                                            <span class="font-bold text-gray-900">{{ option.value }}</span>
                                            <span class="text-gray-700">{{ option.label }}</span>
                                        </div>
                                        <p v-if="availableOptions.length === 0"
                                            class="text-xs text-gray-400 text-center py-2">All used</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ══════════════════════════════════════════════
     Q17-18  Choose TWO – Bamboo oven
══════════════════════════════════════════════ -->
                        <div class="border-t pt-5">
                            <div id="question-17" class="relative" @mouseenter="hoveredQuestion = 17"
                                @mouseleave="hoveredQuestion = null">
                                <button
                                    v-show="hoveredQuestion === 17 || isQuestionFlagged(17) || isQuestionFlagged(18)"
                                    @click.stop="toggleFlag(17); toggleFlag(18)"
                                    class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="(isQuestionFlagged(17) || isQuestionFlagged(18)) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                    </svg>
                                </button>
                                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q1718-header"
                                    v-html="getHighlightedSegmentById('q1718-header')"></h3>
                                <p class="text-sm text-gray-700 select-text" data-segment-id="q1718-inst1"
                                    v-html="getHighlightedSegmentById('q1718-inst1')"></p>
                                <p class="mt-1 text-sm font-medium text-gray-800 select-text"
                                    data-segment-id="q1718-inst2" v-html="getHighlightedSegmentById('q1718-inst2')"></p>

                                <div class="mt-2 space-y-1">
                                    <label v-for="opt in bambooOptions" :key="opt.value"
                                        class="flex cursor-pointer items-start gap-2 rounded px-1 py-1 hover:bg-gray-50"
                                        :class="{ 'opacity-50 cursor-not-allowed': !answers.q17_18.includes(opt.value) && answers.q17_18.length >= 2 }">
                                        <input type="checkbox" :checked="answers.q17_18.includes(opt.value)"
                                            @change="toggleMultiSelect(answers.q17_18, opt.value, 2, (v) => answers.q17_18 = v)"
                                            :disabled="!answers.q17_18.includes(opt.value) && answers.q17_18.length >= 2"
                                            class="mt-0.5 h-4 w-4 flex-shrink-0 accent-black" />
                                        <span class=" text-gray-900 w-4 shrink-0">{{ opt.value
                                            }}</span>
                                        <span class="text-sm text-gray-800 select-text"
                                            :data-segment-id="`bamboo-${opt.value}`"
                                            v-html="getHighlightedSegmentById(`bamboo-${opt.value}`)"></span>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    Selected: {{ answers.q17_18.length }}/2
                                    <span v-if="answers.q17_18.length > 0" class="ml-2 font-medium text-gray-700">
                                        ({{ answers.q17_18.join(', ') }})
                                    </span>
                                </p>
                            </div>
                            <!-- Hidden indicator for Q18 flag state -->
                            <div class="hidden" id="question-18" data-flag-indicator="18"></div>
                        </div>

                        <!-- ══════════════════════════════════════════════
     Q19-20  Choose TWO – Wild fungi
══════════════════════════════════════════════ -->
                        <div class="border-t pt-5">
                            <div id="question-19" class="relative" @mouseenter="hoveredQuestion = 19"
                                @mouseleave="hoveredQuestion = null">
                                <button
                                    v-show="hoveredQuestion === 19 || isQuestionFlagged(19) || isQuestionFlagged(20)"
                                    @click.stop="toggleFlag(19); toggleFlag(20)"
                                    class="absolute top-0 right-0 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="(isQuestionFlagged(19) || isQuestionFlagged(20)) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                    </svg>
                                </button>
                                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q1920-header"
                                    v-html="getHighlightedSegmentById('q1920-header')"></h3>
                                <p class="text-sm text-gray-700 select-text" data-segment-id="q1920-inst1"
                                    v-html="getHighlightedSegmentById('q1920-inst1')"></p>
                                <p class="mt-1 text-sm font-medium text-gray-800 select-text"
                                    data-segment-id="q1920-inst2" v-html="getHighlightedSegmentById('q1920-inst2')"></p>

                                <div class="mt-2 space-y-1">
                                    <label v-for="opt in fungiOptions" :key="opt.value"
                                        class="flex cursor-pointer items-start gap-2 rounded px-1 py-1 hover:bg-gray-50"
                                        :class="{ 'opacity-50 cursor-not-allowed': !answers.q19_20.includes(opt.value) && answers.q19_20.length >= 2 }">
                                        <input type="checkbox" :checked="answers.q19_20.includes(opt.value)"
                                            @change="toggleMultiSelect(answers.q19_20, opt.value, 2, (v) => answers.q19_20 = v)"
                                            :disabled="!answers.q19_20.includes(opt.value) && answers.q19_20.length >= 2"
                                            class="mt-0.5 h-4 w-4 flex-shrink-0 accent-black" />
                                        <span class=" text-gray-900 w-4 shrink-0">{{ opt.value
                                            }}</span>
                                        <span class="text-sm text-gray-800 select-text"
                                            :data-segment-id="`fungi-${opt.value}`"
                                            v-html="getHighlightedSegmentById(`fungi-${opt.value}`)"></span>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    Selected: {{ answers.q19_20.length }}/2
                                    <span v-if="answers.q19_20.length > 0" class="ml-2 font-medium text-gray-700">
                                        ({{ answers.q19_20.join(', ') }})
                                    </span>
                                </p>
                            </div>
                            <!-- Hidden indicator for Q20 flag state -->
                            <div class="hidden" id="question-20" data-flag-indicator="20"></div>
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

/* Flow chart boxes */
.flow-box {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    border: 1px solid #6b7280;
    background: #ffffff;
    padding: 8px 32px 8px 12px;
    text-align: center;
    font-size: 0.9rem;
    color: #1f2937;
    min-height: 2.5rem;
    position: relative;
}

.flow-arrow {
    color: gray;
    font-size: 1.25rem;
    line-height: 1;
    margin: 0;
    padding: 0;
    user-select: none;
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

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #000;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #374151;
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

.note-highlight:hover {
    border-bottom-color: #000;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
