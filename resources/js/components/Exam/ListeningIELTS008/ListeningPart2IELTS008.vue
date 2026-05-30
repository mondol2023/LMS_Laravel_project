<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

const getGroupKey = (questionNumber: number): number => {
    if (questionNumber === 17 || questionNumber === 18) return 17;
    if (questionNumber === 19 || questionNumber === 20) return 19;
    return questionNumber;
};

const isFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(getGroupKey(questionNumber));
};

const toggleFlag = (questionNumber: number) => {
    const key = getGroupKey(questionNumber);
    emit('toggleFlag', key);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));



// ─── Answers ────────────────────────────────────────────────────────────────
const answers = ref({
    q11: '',
    q12: '',
    q13: '',
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
});

const multipleAnswers = ref({
    q17_18: [] as string[],
    q19_20: [] as string[],
});

// Sync multipleAnswers groups → individual answers (so footer tracks 17–20 correctly)
watch(
    () => multipleAnswers.value.q17_18,
    (val) => {
        answers.value.q17 = val[0] ?? '';
        answers.value.q18 = val[1] ?? '';
    },
    { deep: true },
);

watch(
    () => multipleAnswers.value.q19_20,
    (val) => {
        answers.value.q19 = val[0] ?? '';
        answers.value.q20 = val[1] ?? '';
    },
    { deep: true },
);

// ─── Flow-chart drag-and-drop (Q11–16) ──────────────────────────────────────
const draggingLetter = ref<string | null>(null);
const dragOverSlot = ref<string | null>(null);

const flowChartOptions = [
    { letter: 'A', text: 'air' },
    { letter: 'B', text: 'ash' },
    { letter: 'C', text: 'earth' },
    { letter: 'D', text: 'grass' },
    { letter: 'E', text: 'sticks' },
    { letter: 'F', text: 'stones' },
    { letter: 'G', text: 'water' },
];

const onBankDragStart = (letter: string) => {
    draggingLetter.value = letter;
};

const onSlotDragStart = (questionKey: keyof typeof answers.value) => {
    draggingLetter.value = answers.value[questionKey] as string;
};

const onSlotDrop = (questionKey: keyof typeof answers.value) => {
    if (!draggingLetter.value) return;
    const incoming = draggingLetter.value;
    const slotKeys = ['q11', 'q12', 'q13', 'q14', 'q15', 'q16'] as const;
    slotKeys.forEach((key) => {
        if (answers.value[key] === incoming) answers.value[key] = '';
    });
    answers.value[questionKey] = incoming;
    draggingLetter.value = null;
    dragOverSlot.value = null;
};

const onBankDrop = (e: DragEvent) => {
    e.preventDefault();
    if (!draggingLetter.value) return;
    const slotKeys = ['q11', 'q12', 'q13', 'q14', 'q15', 'q16'] as const;
    slotKeys.forEach((key) => {
        if (answers.value[key] === draggingLetter.value) answers.value[key] = '';
    });
    draggingLetter.value = null;
    dragOverSlot.value = null;
};

const onDragOver = (e: DragEvent, slotKey?: string) => {
    e.preventDefault();
    if (slotKey) dragOverSlot.value = slotKey;
};

const onDragLeave = () => {
    dragOverSlot.value = null;
};

const onDragEnd = () => {
    draggingLetter.value = null;
    dragOverSlot.value = null;
};



const placedLetters = computed(() => {
    const keys = ['q11', 'q12', 'q13', 'q14', 'q15', 'q16'] as const;
    return new Set(keys.map((k) => answers.value[k]).filter(Boolean));
});

const getOptionText = (letter: string) => {
    const option = flowChartOptions.find(opt => opt.letter === letter);
    return option ? option.text : '';
};

// ─── Checkbox helpers (Q17–18 and Q19–20) ───────────────────────────────────
const isCheckboxDisabled = (
    group: keyof typeof multipleAnswers.value,
    letter: string,
): boolean => {
    const sel = multipleAnswers.value[group];
    return sel.length >= 2 && !sel.includes(letter);
};

const toggleCheckbox = (
    group: keyof typeof multipleAnswers.value,
    letter: string,
) => {
    if (isCheckboxDisabled(group, letter)) return;
    const sel = multipleAnswers.value[group];
    multipleAnswers.value[group] = sel.includes(letter)
        ? sel.filter((l) => l !== letter)
        : [...sel, letter];
};

// ─── Option data ─────────────────────────────────────────────────────────────
const bambooOptions: { letter: 'A' | 'B' | 'C' | 'D' | 'E'; text: string }[] = [
    { letter: 'A', text: "It's suitable for windy weather." },
    { letter: 'B', text: 'The fire is lit below the bottom end of the bamboo.' },
    { letter: 'C', text: 'The bamboo is cut into equal lengths.' },
    { letter: 'D', text: 'The oven hangs from a stick.' },
    { letter: 'E', text: 'It cooks food by steaming it.' },
];

const fungiOptions: { letter: 'A' | 'B' | 'C' | 'D' | 'E'; text: string }[] = [
    { letter: 'A', text: "Cooking doesn't make poisonous fungi edible." },
    { letter: 'B', text: 'Edible wild fungi can be eaten without cooking.' },
    { letter: 'C', text: 'Wild fungi are highly nutritious.' },
    { letter: 'D', text: 'Some edible fungi look very similar to poisonous varieties.' },
    { letter: 'E', text: 'Fungi which cannot be identified should only be eaten in small quantities.' },
];

// ─── Text segments (from old Part 2) ─────────────────────────────────────────
let currentOffset = 0;
const newSegments: { id: number; text: string; offset: number }[] = [];
let segId = 0;

const addSegment = (text: string) => {
    newSegments.push({ id: segId++, text: text.toString(), offset: currentOffset });
    currentOffset += text.toString().length;
};

addSegment('Section 2');   // 0
addSegment('Questions 11–16');              // 1
addSegment('Complete the flow chart below.'); // 2
addSegment('Choose SIX answers from the box and write the correct letter A–G next to questions 11–16.'); // 3

flowChartOptions.forEach((item) => {
    addSegment(item.letter);  // 4,6,8,10,12,14,16
    addSegment(item.text);    // 5,7,9,11,13,15,17
});

addSegment('Making a steam pit');           // 18
addSegment('Dig a pit');                    // 19
addSegment('Arrange a row of');             // 20
addSegment('over the pit');                 // 21
addSegment('Place');                        // 22
addSegment('on top');                       // 23
addSegment('Light the wood and let it burn out'); // 24
addSegment('Remove');                       // 25
addSegment('Insert a stick');               // 26
addSegment('Cover the pit with');           // 27
addSegment('Place wrapped food on top and cover it with'); // 28
addSegment('Remove the stick and put');     // 29
addSegment('into the hole');                // 30

addSegment('Questions 17–18');              // 31
addSegment('Choose TWO letters, A–E.');     // 32
addSegment('Which TWO characteristics apply to the bamboo oven?'); // 33

bambooOptions.forEach((opt) => {
    addSegment(opt.letter);   // 34,36,38,40,42
    addSegment(opt.text);     // 35,37,39,41,43
});

addSegment('Questions 19–20');              // 44
addSegment('Choose TWO letters, A–E.');     // 45
addSegment('Which TWO pieces of advice does the speaker give about eating wild fungi?'); // 46

fungiOptions.forEach((opt) => {
    addSegment(opt.letter);   // 47,49,51,53,55
    addSegment(opt.text);     // 48,50,52,54,56
});

const textSegments = ref(newSegments);

// ─── Highlight / Note state ───────────────────────────────────────────────────
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

// ─── Highlight rendering ──────────────────────────────────────────────────────
const getHighlightedSegmentById = (segmentId: number) => {
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
            noteText: n.note,
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

// ─── Selection / highlight logic ─────────────────────────────────────────────
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
                while (targetElement && !(targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const parent = targetElement.parentNode;
                    if (!parent || parent === contentTextRef.value) break;
                    targetElement = parent;
                }
                if (targetElement && (targetElement as Element).hasAttribute?.('data-segment-id')) {
                    const segmentIdAttr = (targetElement as Element).getAttribute('data-segment-id') || '';
                    const segmentId = parseInt(segmentIdAttr, 10);
                    const segment = textSegments.value.find((s) => s.id === segmentId);
                    if (segment) {
                        const selectionStartInElement = getTextOffsetInElement(
                            targetElement as Element,
                            range.startContainer,
                            range.startOffset,
                        );
                        const startOffset = segment.offset + selectionStartInElement;
                        const endOffset = startOffset + selected.length;
                        selectedText.value = selected;
                        selectionRange.value = { start: startOffset, end: endOffset };
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
            const tooltipHeight = 50;
            let y = rect.top - tooltipHeight - 8;
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
            <div class="part-header-box mb-3 rounded border-gray-200 px-4 py-3">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"
                ></h3>
            </div>

            <!-- ══════════════════════════════════════
                 Q11–16  FLOW CHART DRAG & DROP
            ═══════════════════════════════════════ -->
            <div class="mb-6 bg-white">

                <!-- Instructions -->
                <div class="shrink-0 px-2 pb-3 sm:px-3">
                    <p
                        class="text-base font-bold text-gray-900 select-text"
                        data-segment-id="1"
                        v-html="getHighlightedSegmentById(1)"
                    ></p>
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

                <!-- Answer Bank -->
                <div
                    class="mb-5 flex flex-wrap gap-2 rounded border  p-3 mx-2"
                    @dragover="onDragOver($event)"
                    @drop="onBankDrop"
                    @dragleave="onDragLeave"
                >
                    <span class="self-center text-xs font-semibold text-gray-500 uppercase tracking-wide mr-1">Options</span>
                    <!-- segment ids 4/5 = A/air, 6/7 = B/ash … 16/17 = G/water -->
                    <div
                        v-for="(item, idx) in flowChartOptions"
                        :key="item.letter"
                        draggable="true"
                        @dragstart="onBankDragStart(item.letter)"
                        @dragend="onDragEnd"
                        :class="[
                            'flex cursor-grab items-center gap-1.5 rounded border px-3 py-1 text-sm font-medium transition-all select-none',
                            placedLetters.has(item.letter)
                                ? 'border-gray-200 bg-gray-100 text-gray-400 opacity-40 cursor-not-allowed pointer-events-none'
                                : 'border-gray-400 bg-white text-gray-900 hover:border-gray-700 active:cursor-grabbing',
                        ]"
                    >
                        <span
                            class="font-bold"
                            :data-segment-id="4 + idx * 2"
                            v-html="getHighlightedSegmentById(4 + idx * 2)"
                        ></span>
                        <span class="text-gray-400">–</span>
                        <span
                            :data-segment-id="5 + idx * 2"
                            v-html="getHighlightedSegmentById(5 + idx * 2)"
                        ></span>
                    </div>
                </div>

                <!-- Flow Chart Frame -->
                <div class=" mx-2">
                    <div class="border-gray-800 px-4 py-2 text-center">
                        <h3
                            class="font-bold text-gray-900 select-text"
                            data-segment-id="18"
                            v-html="getHighlightedSegmentById(18)"
                        ></h3>
                    </div>

                    <div class="flex flex-col items-center px-4 py-5 space-y-1">

                        <!-- Static: Dig a pit -->
                        <div class="w-full max-w-lg rounded border  px-4 py-2 text-center text-sm text-gray-900 select-text">
                            <span data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                        </div>
                        <div class="text-lg text-gray-400">↓</div>

                        <!-- Q11: Arrange a row of [11] over the pit -->
                        <div
                            id="question-11"
                            class="relative w-full max-w-lg text-center rounded border  px-6 py-2 text-sm text-gray-900"
                            @mouseenter="hoveredQuestion = 11"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                @click.stop="toggleFlag(11)"
                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(11) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark'"
                            >
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                            <!-- Drop slot -->
                            <span
                                @dragover="onDragOver($event, 'q11')"
                                @dragleave="onDragLeave"
                                @drop.prevent="onSlotDrop('q11')"
                                :class="[
                                    'mx-1 inline-flex min-w-[8rem] cursor-pointer items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-sm font-bold transition-all align-middle select-none',
                                    answers.q11
                                        ? 'border-gray-600 bg-white text-gray-900'
                                        : dragOverSlot === 'q11'
                                          ? 'border-gray-700 bg-gray-100 text-gray-500'
                                          : 'border-gray-400 bg-white text-gray-400',
                                ]"
                                :draggable="!!answers.q11"
                                @dragstart="answers.q11 ? onSlotDragStart('q11') : undefined"
                                @dragend="onDragEnd"
                                @dblclick="answers.q11 = ''"
                                title="Drop here. Double-click to clear."
                            >{{ answers.q11 ? getOptionText(answers.q11) : '11' }}</span>
                            <span class="select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                        </div>
                        <div class="text-lg text-gray-400">↓</div>

                        <!-- Q12: Place [12] on top -->
                        <div
                            id="question-12"
                            class="relative w-full max-w-lg rounded text-center border  px-4 py-2 text-sm text-gray-900"
                            @mouseenter="hoveredQuestion = 12"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                @click.stop="toggleFlag(12)"
                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(12) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark'"
                            >
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                            <span
                                @dragover="onDragOver($event, 'q12')"
                                @dragleave="onDragLeave"
                                @drop.prevent="onSlotDrop('q12')"
                                :class="[
                                    'mx-1 inline-flex min-w-[7rem] cursor-pointer items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-sm font-bold transition-all align-middle select-none',
                                    answers.q12
                                        ? 'border-gray-600 bg-white text-gray-900'
                                        : dragOverSlot === 'q12'
                                          ? 'border-gray-700 bg-gray-100 text-gray-500'
                                          : 'border-gray-400 bg-white text-gray-400',
                                ]"
                                :draggable="!!answers.q12"
                                @dragstart="answers.q12 ? onSlotDragStart('q12') : undefined"
                                @dragend="onDragEnd"
                                @dblclick="answers.q12 = ''"
                                title="Drop here. Double-click to clear."
                            >{{ answers.q12 ? getOptionText(answers.q12) : '12' }}</span>
                            <span class="select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                        </div>
                        <div class="text-lg text-gray-400">↓</div>

                        <!-- Static: Light the wood -->
                        <div class="w-full max-w-lg rounded border  px-4 py-2 text-center text-sm text-gray-900 select-text">
                            <span data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                        </div>
                        <div class="text-lg text-gray-400">↓</div>

                        <!-- Q13: Remove [13] -->
                        <div
                            id="question-13"
                            class="relative w-full max-w-lg rounded text-center border px-4 py-2 text-sm text-gray-900"
                            @mouseenter="hoveredQuestion = 13"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                @click.stop="toggleFlag(13)"
                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(13) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark'"
                            >
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                            <span
                                @dragover="onDragOver($event, 'q13')"
                                @dragleave="onDragLeave"
                                @drop.prevent="onSlotDrop('q13')"
                                :class="[
                                    'mx-1 inline-flex min-w-[8rem] cursor-pointer items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-sm font-bold transition-all align-middle select-none',
                                    answers.q13
                                        ? 'border-gray-600 bg-white text-gray-900'
                                        : dragOverSlot === 'q13'
                                          ? 'border-gray-700 bg-gray-100 text-gray-500'
                                          : 'border-gray-400 bg-white text-gray-400',
                                ]"
                                :draggable="!!answers.q13"
                                @dragstart="answers.q13 ? onSlotDragStart('q13') : undefined"
                                @dragend="onDragEnd"
                                @dblclick="answers.q13 = ''"
                                title="Drop here. Double-click to clear."
                            >{{ answers.q13 ? getOptionText(answers.q13) : '13' }}</span>
                        </div>
                        <div class="text-lg text-gray-400">↓</div>

                        <!-- Static: Insert a stick -->
                        <div class="w-full max-w-lg rounded border  px-4 py-2 text-center text-sm text-gray-900 select-text">
                            <span data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                        </div>
                        <div class="text-lg text-gray-400">↓</div>

                        <!-- Q14: Cover the pit with [14] -->
                        <div
                            id="question-14"
                            class="relative w-full max-w-lg text-center rounded border  px-4 py-2 text-sm text-gray-900"
                            @mouseenter="hoveredQuestion = 14"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                @click.stop="toggleFlag(14)"
                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(14) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark'"
                            >
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="select-text" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></span>
                            <span
                                @dragover="onDragOver($event, 'q14')"
                                @dragleave="onDragLeave"
                                @drop.prevent="onSlotDrop('q14')"
                                :class="[
                                    'mx-1 inline-flex min-w-[8rem] cursor-pointer items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-sm font-bold transition-all align-middle select-none',
                                    answers.q14
                                        ? 'border-gray-600 bg-white text-gray-900'
                                        : dragOverSlot === 'q14'
                                          ? 'border-gray-700 bg-gray-100 text-gray-500'
                                          : 'border-gray-400 bg-white text-gray-400',
                                ]"
                                :draggable="!!answers.q14"
                                @dragstart="answers.q14 ? onSlotDragStart('q14') : undefined"
                                @dragend="onDragEnd"
                                @dblclick="answers.q14 = ''"
                                title="Drop here. Double-click to clear."
                            >{{ answers.q14 ? getOptionText(answers.q14) : '14' }}</span>
                        </div>
                        <div class="text-lg text-gray-400">↓</div>

                        <!-- Q15: Place wrapped food on top and cover it with [15] -->
                        <div
                            id="question-15"
                            class="relative w-full max-w-lg text-center rounded border  px-4 py-2 text-sm text-gray-900"
                            @mouseenter="hoveredQuestion = 15"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                @click.stop="toggleFlag(15)"
                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(15) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark'"
                            >
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
                            <span
                                @dragover="onDragOver($event, 'q15')"
                                @dragleave="onDragLeave"
                                @drop.prevent="onSlotDrop('q15')"
                                :class="[
                                    'mx-1 inline-flex min-w-[8rem] cursor-pointer items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-sm font-bold transition-all align-middle select-none',
                                    answers.q15
                                        ? 'border-gray-600 bg-white text-gray-900'
                                        : dragOverSlot === 'q15'
                                          ? 'border-gray-700 bg-gray-100 text-gray-500'
                                          : 'border-gray-400 bg-white text-gray-400',
                                ]"
                                :draggable="!!answers.q15"
                                @dragstart="answers.q15 ? onSlotDragStart('q15') : undefined"
                                @dragend="onDragEnd"
                                @dblclick="answers.q15 = ''"
                                title="Drop here. Double-click to clear."
                            >{{ answers.q15 ? getOptionText(answers.q15) : '15' }}</span>
                        </div>
                        <div class="text-lg text-gray-400">↓</div>

                        <!-- Q16: Remove the stick and put [16] into the hole -->
                        <div
                            id="question-16"
                            class="relative w-full max-w-lg rounded text-center border px-4 py-2 text-sm text-gray-900"
                            @mouseenter="hoveredQuestion = 16"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                @click.stop="toggleFlag(16)"
                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark'"
                            >
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <span class="select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></span>
                            <span
                                @dragover="onDragOver($event, 'q16')"
                                @dragleave="onDragLeave"
                                @drop.prevent="onSlotDrop('q16')"
                                :class="[
                                    'mx-1 inline-flex min-w-[8rem] cursor-pointer items-center justify-center rounded border-2 border-dashed px-2 py-0.5 text-sm font-bold transition-all align-middle select-none',
                                    answers.q16
                                        ? 'border-gray-600 bg-white text-gray-900'
                                        : dragOverSlot === 'q16'
                                          ? 'border-gray-700 bg-gray-100 text-gray-500'
                                          : 'border-gray-400 bg-white text-gray-400',
                                ]"
                                :draggable="!!answers.q16"
                                @dragstart="answers.q16 ? onSlotDragStart('q16') : undefined"
                                @dragend="onDragEnd"
                                @dblclick="answers.q16 = ''"
                                title="Drop here. Double-click to clear."
                            >{{ answers.q16 ? getOptionText(answers.q16) : '16' }}</span>
                            <span class="select-text" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 Q17–18  CHECKBOX (max 2)
            ═══════════════════════════════════════ -->
            <div class="mb-6 bg-white relative group">
                <button
                        @click.stop="toggleFlag(17)"
                        class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all
                            opacity-0 group-hover:opacity-100"
                        :class="isFlagged(17)
                            ? 'border-gray-400 text-red-500 opacity-100'
                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark Q17–18"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                <div class="shrink-0 px-2 pb-3 sm:px-3">
                    <!-- FLAG BUTTON -->

                    <p
                        class="text-base font-bold text-gray-900 select-text"
                        data-segment-id="31"
                        v-html="getHighlightedSegmentById(31)"
                    ></p>
                    <p
                        class="text-sm text-gray-700 select-text"
                        data-segment-id="32"
                        v-html="getHighlightedSegmentById(32)"
                    ></p>
                    <p
                        class="text-sm text-gray-700 select-text"
                        data-segment-id="33"
                        v-html="getHighlightedSegmentById(33)"
                    ></p>
                    <!-- Flag button on the side (left) -->

                </div>

                <div class="space-y-2 px-2" id="question-17">
                    <!-- Each answer option now has the flag button on the side -->
                    <div
                        v-for="(opt, idx) in bambooOptions"
                        :key="opt.letter"
                        class="flex items-start gap-3"
                    >


                        <!-- Answer option content -->
                        <div
                            @click="toggleCheckbox('q17_18', opt.letter)"
                            :class="[
                                'flex-1 flex cursor-pointer items-start gap-3 rounded border p-3 transition-all select-none',
                                multipleAnswers.q17_18.includes(opt.letter)
                                    ? 'border-gray-700 bg-gray-100'
                                    : isCheckboxDisabled('q17_18', opt.letter)
                                    ? 'cursor-not-allowed border-gray-200 bg-gray-50 opacity-40'
                                    : 'border-gray-200 hover:border-gray-400 hover:bg-gray-50',
                            ]"
                        >
                            <!-- Custom checkbox -->
                            <div
                                :class="[
                                    'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 transition-all',
                                    multipleAnswers.q17_18.includes(opt.letter)
                                        ? 'border-gray-700 bg-gray-700'
                                        : 'border-gray-400 bg-white',
                                ]"
                            >
                                <svg
                                    v-if="multipleAnswers.q17_18.includes(opt.letter)"
                                    class="h-3 w-3 text-white"
                                    fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="3"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="flex items-start gap-2 text-sm text-gray-900 select-text">
                                <span
                                    class="font-bold"
                                    :data-segment-id="34 + idx * 2"
                                    v-html="getHighlightedSegmentById(34 + idx * 2)"
                                ></span>
                                <span
                                    :data-segment-id="35 + idx * 2"
                                    v-html="getHighlightedSegmentById(35 + idx * 2)"
                                ></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Counter -->
                <div class="mt-2 flex items-center justify-between px-2">
                    <p class="text-xs text-gray-400">{{ multipleAnswers.q17_18.length }} / 2 selected</p>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                Q19–20  CHECKBOX (max 2)
            ═══════════════════════════════════════ -->
            <div class="mb-6 bg-white relative group">
                <button
                        @click.stop="toggleFlag(19)"
                        class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all
                            opacity-0 group-hover:opacity-100"
                        :class="isFlagged(19)
                            ? 'border-gray-400 text-red-500 opacity-100'
                            : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                        title="Bookmark Q19–20"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>

                <div class="shrink-0 px-2 pb-3 sm:px-3">
                    <p
                        class="text-base font-bold text-gray-900 select-text"
                        data-segment-id="44"
                        v-html="getHighlightedSegmentById(44)"
                    ></p>
                    <p
                        class="text-sm text-gray-700 select-text"
                        data-segment-id="45"
                        v-html="getHighlightedSegmentById(45)"
                    ></p>
                    <p
                        class="text-sm text-gray-700 select-text"
                        data-segment-id="46"
                        v-html="getHighlightedSegmentById(46)"
                    ></p>
                </div>

                <div class="space-y-2 px-2" id="question-19">
                    <!-- Each answer option now has the flag button on the side -->
                    <div
                        v-for="(opt, idx) in fungiOptions"
                        :key="opt.letter"
                        class="flex items-start gap-3"
                    >
                        <!-- Flag button on the side (left) -->


                        <!-- Answer option content -->
                        <div
                            @click="toggleCheckbox('q19_20', opt.letter)"
                            :class="[
                                'flex-1 flex cursor-pointer items-start gap-3 rounded border p-3 transition-all select-none',
                                multipleAnswers.q19_20.includes(opt.letter)
                                    ? 'border-gray-700 bg-gray-100'
                                    : isCheckboxDisabled('q19_20', opt.letter)
                                    ? 'cursor-not-allowed border-gray-200 bg-gray-50 opacity-40'
                                    : 'border-gray-200 hover:border-gray-400 hover:bg-gray-50',
                            ]"
                        >
                            <div
                                :class="[
                                    'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 transition-all',
                                    multipleAnswers.q19_20.includes(opt.letter)
                                        ? 'border-gray-700 bg-gray-700'
                                        : 'border-gray-400 bg-white',
                                ]"
                            >
                                <svg
                                    v-if="multipleAnswers.q19_20.includes(opt.letter)"
                                    class="h-3 w-3 text-white"
                                    fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="3"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="flex items-start gap-2 text-sm text-gray-900 select-text">
                                <span
                                    class="font-bold"
                                    :data-segment-id="47 + idx * 2"
                                    v-html="getHighlightedSegmentById(47 + idx * 2)"
                                ></span>
                                <span
                                    :data-segment-id="48 + idx * 2"
                                    v-html="getHighlightedSegmentById(48 + idx * 2)"
                                ></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Counter -->
                <div class="mt-2 flex items-center justify-between px-2">
                    <p class="text-xs text-gray-400">{{ multipleAnswers.q19_20.length }} / 2 selected</p>
                </div>
            </div>

        </div>

        <!-- ── Tooltips (Teleport to body) ────────────────────────────────── -->
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
                        class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
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

<!-- Non-scoped styles for v-html generated note indicators -->
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
