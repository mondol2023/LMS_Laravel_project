<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

// Q37-40 options: A-F, duplicates allowed
const moduleOptions = [
    { value: 'A', label: 'gave false data' },
    { value: 'B', label: 'decided to stop participating' },
    { value: 'C', label: 'refusal to tell Shona about their job' },
    { value: 'D', label: 'kept changing their mind about participating' },
    { value: 'E', label: 'became very angry with Shona' },
    { value: 'F', label: 'was worried about confidentiality' },
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
    q31: '', q32: '', q33: '', q34: '', q35: '', q36: '',
    q37: '', q38: '', q39: '', q40: '',
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
    { id: 'sec4-title', text: 'Part 4', offset: 0 },
    { id: 'sec4-subtitle', text: 'Listen and answer questions 31–40.', offset: 7 },
    { id: 'sec4-qrange', text: 'Questions 31–36', offset: 42 },
    { id: 'sec4-inst', text: 'Choose the correct letter A, B or C.', offset: 58 },
    { id: 'sec4-topic', text: 'Research on questions about doctors', offset: 95 },

    // Q31
    { id: 'q31-num', text: '31.', offset: 131 },
    { id: 'q31-stem', text: 'In order to set up her research programme, Shona got', offset: 135 },
    { id: 'q31-a', text: 'advice from personal friends in other countries', offset: 188 },
    { id: 'q31-b', text: 'help from students in other countries', offset: 236 },
    { id: 'q31-c', text: "information from her tutor's contacts in other countries", offset: 273 },

    // Q32
    { id: 'q32-num', text: '32.', offset: 330 },
    { id: 'q32-stem', text: 'What types of people were included in the research?', offset: 334 },
    { id: 'q32-a', text: 'young people in their first job', offset: 386 },
    { id: 'q32-b', text: 'men who were working', offset: 417 },
    { id: 'q32-c', text: 'women who were unemployed', offset: 438 },

    // Q33
    { id: 'q33-num', text: '33.', offset: 464 },
    { id: 'q33-stem', text: 'Shona says that in her questionnaire her aim was', offset: 468 },
    { id: 'q33-a', text: 'to get a wide range of data', offset: 517 },
    { id: 'q33-b', text: "to limit people's responses", offset: 545 },
    { id: 'q33-c', text: 'to guide people through interviews', offset: 573 },

    // Q34
    { id: 'q34-num', text: '34.', offset: 607 },
    { id: 'q34-stem', text: "What do Shona's initial results show about medical services in Britain?", offset: 611 },
    { id: 'q34-a', text: 'Current concerns are misrepresented by the press', offset: 682 },
    { id: 'q34-b', text: 'Financial issues are critical to the government', offset: 731 },
    { id: 'q34-c', text: 'Reforms within hospitals have been unsuccessful', offset: 779 },

    // Q35
    { id: 'q35-num', text: '35.', offset: 827 },
    { id: 'q35-stem', text: 'Shona needs to do further research in order to', offset: 831 },
    { id: 'q35-a', text: 'present the government with her findings', offset: 878 },
    { id: 'q35-b', text: 'decide the level of extra funding needed', offset: 918 },
    { id: 'q35-c', text: 'identify the preferences of the public', offset: 958 },

    // Q36
    { id: 'q36-num', text: '36.', offset: 996 },
    { id: 'q36-stem', text: 'Shona has learnt from the research project that', offset: 1000 },
    { id: 'q36-a', text: 'it is important to plan projects carefully', offset: 1048 },
    { id: 'q36-b', text: 'people do not like answering questions', offset: 1090 },
    { id: 'q36-c', text: 'colleagues do not always agree', offset: 1129 },

    // Q37-40 section
    { id: 'q3740-title', text: 'Questions 37–40', offset: 1160 },
    { id: 'q3740-inst1', text: 'Which statement applies to each of the following people who were interviewed by Shona?', offset: 1176 },
    { id: 'q3740-inst2', text: 'Choose FOUR answers from the box and write the correct letter, A–F, next to questions 37–40.', offset: 1263 },
    { id: 'q3740-subhead', text: 'People interviewed by Shona', offset: 1357 },

    // Q37-40 labels
    { id: 'q37-label', text: 'a person interviewed in the street', offset: 1385 },
    { id: 'q38-label', text: 'an undergraduate at the university', offset: 1420 },
    { id: 'q39-label', text: 'a colleague in her department', offset: 1455 },
    { id: 'q40-label', text: 'a tutor in a foreign university', offset: 1485 },
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

const usedOptions = computed(() =>
    new Set([answers.value.q37, answers.value.q38, answers.value.q39, answers.value.q40].filter(Boolean))
);

const availableOptions = computed(() =>
    moduleOptions.filter(opt => !usedOptions.value.has(opt.value))
);

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

            <!-- ── Part Header ── -->
            <div class="part-header-box mb-3 rounded border bg-[#F1F2EC] border-gray-200 px-4 py-3"
                style="background-color: #F1F2EC;">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="sec4-title"
                    v-html="getHighlightedSegmentById('sec4-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="sec4-subtitle"
                    v-html="getHighlightedSegmentById('sec4-subtitle')"></p>
            </div>

            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-3 sm:space-y-4 md:space-y-6">

                        <!-- Topic heading -->
                        <div class="">
                            <h3 class="text-lg font-bold text-black select-text" data-segment-id="sec4-topic"
                                v-html="getHighlightedSegmentById('sec4-topic')"></h3>
                        </div>

                        <!-- Q31-36 sub-header -->
                        <div>
                            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="sec4-qrange"
                                v-html="getHighlightedSegmentById('sec4-qrange')"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="sec4-inst"
                                v-html="getHighlightedSegmentById('sec4-inst')"></p>
                        </div>

                        <!-- ───── Q31 ───── -->
                        <div id="question-31" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 31"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                @click.stop="toggleFlag(31)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                :class="isQuestionFlagged(31) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <div class="flex items-start">
                                <span class="mr-1 text-base font-bold select-text" data-segment-id="q31-num"
                                    v-html="getHighlightedSegmentById('q31-num')"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="mb-2 text-base font-medium text-gray-800 select-text"
                                        data-segment-id="q31-stem" v-html="getHighlightedSegmentById('q31-stem')"></p>
                                    <div class="space-y-0.5">
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q31" value="A"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q31-a"
                                                v-html="getHighlightedSegmentById('q31-a')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q31" value="B"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q31-b"
                                                v-html="getHighlightedSegmentById('q31-b')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q31" value="C"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q31-c"
                                                v-html="getHighlightedSegmentById('q31-c')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ───── Q32 ───── -->
                        <div id="question-32" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 32"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                @click.stop="toggleFlag(32)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                :class="isQuestionFlagged(32) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <div class="flex items-start">
                                <span class="mr-1 text-base font-bold select-text" data-segment-id="q32-num"
                                    v-html="getHighlightedSegmentById('q32-num')"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="mb-2 text-base font-medium text-gray-800 select-text"
                                        data-segment-id="q32-stem" v-html="getHighlightedSegmentById('q32-stem')"></p>
                                    <div class="space-y-0.5">
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q32" value="A"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q32-a"
                                                v-html="getHighlightedSegmentById('q32-a')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q32" value="B"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q32-b"
                                                v-html="getHighlightedSegmentById('q32-b')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q32" value="C"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q32-c"
                                                v-html="getHighlightedSegmentById('q32-c')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ───── Q33 ───── -->
                        <div id="question-33" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 33"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                @click.stop="toggleFlag(33)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                :class="isQuestionFlagged(33) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <div class="flex items-start">
                                <span class="mr-1 text-base font-bold select-text" data-segment-id="q33-num"
                                    v-html="getHighlightedSegmentById('q33-num')"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="mb-2 text-base font-medium text-gray-800 select-text"
                                        data-segment-id="q33-stem" v-html="getHighlightedSegmentById('q33-stem')"></p>
                                    <div class="space-y-0.5">
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q33" value="A"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q33-a"
                                                v-html="getHighlightedSegmentById('q33-a')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q33" value="B"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q33-b"
                                                v-html="getHighlightedSegmentById('q33-b')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q33" value="C"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q33-c"
                                                v-html="getHighlightedSegmentById('q33-c')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ───── Q34 ───── -->
                        <div id="question-34" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 34"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                @click.stop="toggleFlag(34)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                :class="isQuestionFlagged(34) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <div class="flex items-start">
                                <span class="mr-1 text-base font-bold select-text" data-segment-id="q34-num"
                                    v-html="getHighlightedSegmentById('q34-num')"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="mb-2 text-base font-medium text-gray-800 select-text"
                                        data-segment-id="q34-stem" v-html="getHighlightedSegmentById('q34-stem')"></p>
                                    <div class="space-y-0.5">
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q34" value="A"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q34-a"
                                                v-html="getHighlightedSegmentById('q34-a')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q34" value="B"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q34-b"
                                                v-html="getHighlightedSegmentById('q34-b')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q34" value="C"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q34-c"
                                                v-html="getHighlightedSegmentById('q34-c')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ───── Q35 ───── -->
                        <div id="question-35" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 35"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                @click.stop="toggleFlag(35)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                :class="isQuestionFlagged(35) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <div class="flex items-start">
                                <span class="mr-1 text-base font-bold select-text" data-segment-id="q35-num"
                                    v-html="getHighlightedSegmentById('q35-num')"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="mb-2 text-base font-medium text-gray-800 select-text"
                                        data-segment-id="q35-stem" v-html="getHighlightedSegmentById('q35-stem')"></p>
                                    <div class="space-y-0.5">
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q35" value="A"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q35-a"
                                                v-html="getHighlightedSegmentById('q35-a')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q35" value="B"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q35-b"
                                                v-html="getHighlightedSegmentById('q35-b')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q35" value="C"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q35-c"
                                                v-html="getHighlightedSegmentById('q35-c')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ───── Q36 ───── -->
                        <div id="question-36" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 36"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                @click.stop="toggleFlag(36)"
                                class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                :class="isQuestionFlagged(36) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>
                            <div class="flex items-start">
                                <span class="mr-1 text-base font-bold select-text" data-segment-id="q36-num"
                                    v-html="getHighlightedSegmentById('q36-num')"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="mb-2 text-base font-medium text-gray-800 select-text"
                                        data-segment-id="q36-stem" v-html="getHighlightedSegmentById('q36-stem')"></p>
                                    <div class="space-y-0.5">
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q36" value="A"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q36-a"
                                                v-html="getHighlightedSegmentById('q36-a')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q36" value="B"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q36-b"
                                                v-html="getHighlightedSegmentById('q36-b')"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-center gap-2 px-1 py-1 hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q36" value="C"
                                                class="h-4 w-4 flex-shrink-0 accent-black" />
                                            <span class="text-sm font-bold text-gray-800 mr-1"></span>
                                            <span class="text-sm font-medium text-gray-800 select-text"
                                                data-segment-id="q36-c"
                                                v-html="getHighlightedSegmentById('q36-c')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ───── Q37-40 Drag & Drop ───── -->
                        <div class="p-2 sm:p-3">
                            <div class="mb-4">
                                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q3740-title"
                                    v-html="getHighlightedSegmentById('q3740-title')"></p>
                                <div class="mt-1 space-y-0.5">
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="q3740-inst1"
                                        v-html="getHighlightedSegmentById('q3740-inst1')"></p>
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="q3740-inst2"
                                        v-html="getHighlightedSegmentById('q3740-inst2')"></p>
                                </div>
                            </div>




                            <div class="flex gap-4">
                                <div>
                                    <!-- Sub-heading -->
                                    <p class="mb-3 text-sm font-bold text-gray-900 select-text"
                                        data-segment-id="q3740-subhead"
                                        v-html="getHighlightedSegmentById('q3740-subhead')">
                                    </p>

                                    <!-- Drop zones for Q37-40 -->
                                    <div class="space-y-5">

                                        <!-- Q37 -->
                                        <div id="question-37" class="flex items-center gap-3 flex-wrap"
                                            @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900 select-none w-8">37</span>
                                            <span class="text-base text-gray-700 select-text"
                                                data-segment-id="q37-label"
                                                v-html="getHighlightedSegmentById('q37-label')"></span>
                                            <span
                                                class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                                :class="dragOverQuestion === 37 ? 'border-blue-500 bg-blue-50' : answers.q37 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 37)" @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 37)" @click="clearAnswer(37)"
                                                :title="answers.q37 ? 'Click to clear' : 'Drop answer here'">
                                                <span v-if="answers.q37">{{ getModuleOptionLabel(answers.q37) }}</span>
                                                <span v-else class="font-bold text-gray-900">37</span>
                                            </span>
                                            <div class="w-7 h-7 shrink-0">
                                                <button v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                    @click.stop="toggleFlag(37)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                    :class="isQuestionFlagged(37) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Q38 -->
                                        <div id="question-38" class="flex items-center gap-3 flex-wrap"
                                            @mouseenter="hoveredQuestion = 38" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900 select-none w-8">38</span>
                                            <span class="text-base text-gray-700 select-text"
                                                data-segment-id="q38-label"
                                                v-html="getHighlightedSegmentById('q38-label')"></span>
                                            <span
                                                class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                                :class="dragOverQuestion === 38 ? 'border-blue-500 bg-blue-50' : answers.q38 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 38)" @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 38)" @click="clearAnswer(38)"
                                                :title="answers.q38 ? 'Click to clear' : 'Drop answer here'">
                                                <span v-if="answers.q38">{{ getModuleOptionLabel(answers.q38) }}</span>
                                                <span v-else class="font-bold text-gray-900">38</span>
                                            </span>
                                            <div class="w-7 h-7 shrink-0">
                                                <button v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                    @click.stop="toggleFlag(38)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                    :class="isQuestionFlagged(38) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Q39 -->
                                        <div id="question-39" class="flex items-center gap-3 flex-wrap"
                                            @mouseenter="hoveredQuestion = 39" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900 select-none w-8">39</span>
                                            <span class="text-base text-gray-700 select-text"
                                                data-segment-id="q39-label"
                                                v-html="getHighlightedSegmentById('q39-label')"></span>
                                            <span
                                                class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                                :class="dragOverQuestion === 39 ? 'border-blue-500 bg-blue-50' : answers.q39 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 39)" @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 39)" @click="clearAnswer(39)"
                                                :title="answers.q39 ? 'Click to clear' : 'Drop answer here'">
                                                <span v-if="answers.q39">{{ getModuleOptionLabel(answers.q39) }}</span>
                                                <span v-else class="font-bold text-gray-900">39</span>
                                            </span>
                                            <div class="w-7 h-7 shrink-0">
                                                <button v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                                    @click.stop="toggleFlag(39)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                    :class="isQuestionFlagged(39) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Q40 -->
                                        <div id="question-40" class="flex items-center gap-3 flex-wrap"
                                            @mouseenter="hoveredQuestion = 40" @mouseleave="hoveredQuestion = null">
                                            <span class="text-base font-bold text-gray-900 select-none w-8">40</span>
                                            <span class="text-base text-gray-700 select-text"
                                                data-segment-id="q40-label"
                                                v-html="getHighlightedSegmentById('q40-label')"></span>
                                            <span
                                                class="inline-flex min-h-9 min-w-44 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all"
                                                :class="dragOverQuestion === 40 ? 'border-blue-500 bg-blue-50' : answers.q40 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 40)" @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 40)" @click="clearAnswer(40)"
                                                :title="answers.q40 ? 'Click to clear' : 'Drop answer here'">
                                                <span v-if="answers.q40">{{ getModuleOptionLabel(answers.q40) }}</span>
                                                <span v-else class="font-bold text-gray-900">40</span>
                                            </span>
                                            <div class="w-7 h-7 shrink-0">
                                                <button v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                                    @click.stop="toggleFlag(40)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                                                    :class="isQuestionFlagged(40) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </div>


                                <!-- Options box shown above drop zones (A–F) -->
                                <div class="mb-4 border border-gray-900 p-3 bg-white">
                                    <p class="mb-2 text-sm font-semibold text-gray-700">Drag options to fill blanks:</p>
                                    <div class="flex flex-col gap-2 min-h-[40px]">
                                        <div v-for="option in availableOptions" :key="option.value"
                                            class="flex cursor-grab items-center gap-2 rounded border border-gray-300 px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                            :class="{ 'opacity-50': draggedOption === option.value }" draggable="true"
                                            @dragstart="handleDragStart($event, option.value)" @dragend="handleDragEnd">
                                            <span class="font-bold text-gray-900 text-sm">{{ option.value }}</span>
                                            <span class="text-gray-700 text-sm">{{ option.label }}</span>
                                        </div>
                                        <p v-if="availableOptions.length === 0" class="text-xs text-gray-400 italic">All
                                            options placed.</p>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">Click a filled box to clear it.</p>
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