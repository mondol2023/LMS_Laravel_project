
<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref , watch } from 'vue';

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
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
    q27: '',
    q28: '',
    q29: '',
    q30: '',
});

// ─── Toggle radio (click same option to deselect) ────────────────────────────
const toggleRadio = (key: keyof typeof answers.value, value: string) => {
    answers.value[key] = answers.value[key] === value ? '' : value;
};

// ─── Drag-and-drop for Q26–30 ────────────────────────────────────────────────
const draggingLetter = ref<string | null>(null);
const dragOverSlot = ref<string | null>(null);

const onBankDragStart = (letter: string) => {
    draggingLetter.value = letter;
};

const onSlotDragStart = (questionKey: keyof typeof answers.value) => {
    draggingLetter.value = answers.value[questionKey];
};


const getDifficultyLabel = (letter: string | null): string => {
    if (!letter) return '';
    const found = difficulties.find(d => d.letter === letter);
    return found ? `${found.letter} – ${found.text}` : letter;
};

const onSlotDrop = (questionKey: keyof typeof answers.value) => {
    if (!draggingLetter.value) return;
    const incoming = draggingLetter.value;
    const slotKeys = ['q26', 'q27', 'q28', 'q29', 'q30'] as const;
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
    const slotKeys = ['q26', 'q27', 'q28', 'q29', 'q30'] as const;
    slotKeys.forEach((key) => {
        if (answers.value[key] === draggingLetter.value) answers.value[key] = '';
    });
    draggingLetter.value = null;
    dragOverSlot.value = null;
};

const availableDifficulties = computed(() => {
    return difficulties.filter(d => !placedLetters.value.has(d.letter));
});

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
    const keys = ['q26', 'q27', 'q28', 'q29', 'q30'] as const;
    return new Set(keys.map((k) => answers.value[k]).filter(Boolean));
});

watch(placedLetters, (newVal) => {
    console.log('Placed letters:', Array.from(newVal));
}, { deep: true });

const getDifficultyIndex = (letter: string) => difficulties.findIndex(d => d.letter === letter);


// ─── Question data ────────────────────────────────────────────────────────────
const mcq = [
    {
        no: 21,
        key: 'q21',
        prompt: "Phoebe's main reason for choosing her topic was that",
        options: [
            { value: 'A', label: 'her classmates had been very interested in it.' },
            { value: 'B', label: 'it would help prepare her for her first teaching post.' },
            { value: 'C', label: 'she had been inspired by a particular book.' },
        ],
    },
    {
        no: 22,
        key: 'q22',
        prompt: "Phoebe's main research question related to",
        options: [
            { value: 'A', label: 'the effect of teacher discipline.' },
            { value: 'B', label: 'the variety of learning activities.' },
            { value: 'C', label: 'levels of pupil confidence.' },
        ],
    },
    {
        no: 23,
        key: 'q23',
        prompt: 'Phoebe was most surprised by her finding that',
        options: [
            { value: 'A', label: 'gender did not influence behaviour significantly.' },
            { value: 'B', label: 'girls were more negative about school than boys.' },
            { value: 'C', label: 'boys were more talkative than girls in class.' },
        ],
    },
    {
        no: 24,
        key: 'q24',
        prompt: 'Regarding teaching, Phoebe says she has learned that',
        options: [
            { value: 'A', label: 'teachers should be flexible in their lesson planning.' },
            { value: 'B', label: 'brighter children learn from supporting weaker ones.' },
            { value: 'C', label: 'children vary from each other in unpredictable ways.' },
        ],
    },
    {
        no: 25,
        key: 'q25',
        prompt: "Tony is particularly impressed by Phoebe's ability to",
        options: [
            { value: 'A', label: 'recognise the limitations of such small-scale research.' },
            { value: 'B', label: 'reflect on her own research experience in an interesting way.' },
            { value: 'C', label: 'design her research in such a way as to minimise difficulties.' },
        ],
    },
];

const difficulties = [
    { letter: 'A', text: 'Obtaining permission' },
    { letter: 'B', text: 'Deciding on a suitable focus' },
    { letter: 'C', text: 'Concentrating while gathering data' },
    { letter: 'D', text: 'Working collaboratively' },
    { letter: 'E', text: 'Processing data she had gathered' },
    { letter: 'F', text: 'Finding a suitable time to conduct the research' },
    { letter: 'G', text: 'Getting hold of suitable equipment' },
];

const researchTechniques = [
    { no: 26, key: 'q26', text: 'Observing lessons' },
    { no: 27, key: 'q27', text: 'Interviewing teachers' },
    { no: 28, key: 'q28', text: 'Interviewing pupils' },
    { no: 29, key: 'q29', text: 'Using questionnaires' },
    { no: 30, key: 'q30', text: 'Taking photographs' },
];

// ─── Text segments (auto-assigned numeric IDs) ───────────────────────────────
let currentOffset = 0;
let segId = 0;
const allTextSegments: { id: number; text: string; offset: number }[] = [];

const addSegment = (text: string) => {
    allTextSegments.push({ id: segId++, text, offset: currentOffset });
    currentOffset += text.length;
};

// IDs assigned in order:
// 0  – part title
// 1  – subtitle
// 2  – 'Questions 21–25'
// 3  – '— Choose the correct letter, '
// 4  – 'A, B or C'
// 5..19  – mcq prompts + options (q21: 5,6,7,8 | q22: 9,10,11,12 | ... q25: 21,22,23,24)
// 25 – 'Questions 26–30'
// 26 – long instruction text
// 27 – 'Choose '
// 28 – 'FIVE'
// 29 – ' answers from the box and write the correct letter '
// 30 – 'A–G'
// 31 – ', next to questions '
// 32 – '26–30'
// 33 – '.'
// 34 – 'Difficulties'
// 35..48 – difficulty items
// 49 – 'Research techniques'
// 50,51 .. 60 – technique number + text pairs

addSegment('Part 3');                                          // 0
addSegment('Research project on attitudes towards study');                      // 1
addSegment('Questions 21–25');                                                  // 2
addSegment('— Choose the correct letter, ');                                    // 3
addSegment('A, B or C');                                                        // 4

mcq.forEach((q) => {
    addSegment(q.prompt);                                                       // 5,9,13,17,21
    q.options.forEach((opt) => addSegment(`${opt.value}. ${opt.label}`));       // 6-8,10-12,14-16,18-20,22-24
});

addSegment('Questions 26–30');                                                  // 25
addSegment('What did Phoebe find difficult about the different research techniques she used?'); // 26
addSegment('Choose ');                                                          // 27
addSegment('FIVE');                                                             // 28
addSegment(' answers from the box and write the correct letter ');              // 29
addSegment('A–G');                                                              // 30
addSegment(', next to questions ');                                             // 31
addSegment('26–30');                                                            // 32
addSegment('.');                                                                // 33
addSegment('Difficulties');                                                     // 34

difficulties.forEach((d) => {
    addSegment(d.letter);                                                       // 35,37,39,41,43,45,47
    addSegment(d.text);                                                         // 36,38,40,42,44,46,48
});

addSegment('Research techniques');                                              // 49

researchTechniques.forEach((t) => {
    addSegment(`${t.no}.`);                                                     // 50,52,54,56,58
    addSegment(t.text);                                                         // 51,53,55,57,59
});

const textSegments = ref(allTextSegments);

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

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

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
                    const segmentIdAttr = (targetElement as Element).getAttribute('data-segment-id') || '';
                    const segmentId = parseInt(segmentIdAttr, 10);
                    const segment = textSegments.value.find((s) => s.id === segmentId);
                    if (segment) {
                        const selectionStartInElement = getTextOffsetInElement(
                            targetElement as Element,
                            range.startContainer,
                            range.startOffset,
                        );
                        selectedText.value = selected;
                        selectionRange.value = {
                            start: segment.offset + selectionStartInElement,
                            end: segment.offset + selectionStartInElement + selected.length,
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
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"
                ></h3>
                <p
                    class="text-sm text-gray-600 select-text"
                    data-segment-id="1"
                    v-html="getHighlightedSegmentById(1)"
                ></p>
            </div>

            <!-- ══════════════════════════════════════
                 Q21–25  MCQ RADIO
            ═══════════════════════════════════════ -->
            <div class="mb-6 bg-white">
                <div class="shrink-0 px-2 pb-3 sm:px-3">
                    <p class="text-base font-bold text-gray-900 select-text">
                        <span
                            data-segment-id="2"
                            v-html="getHighlightedSegmentById(2)"
                        ></span>
                        <span
                            class="ml-1 font-normal text-gray-700"
                            data-segment-id="3"
                            v-html="getHighlightedSegmentById(3)"
                        ></span>
                        <span
                            class="font-normal"
                            data-segment-id="4"
                            v-html="getHighlightedSegmentById(4)"
                        ></span>
                    </p>
                </div>

                <div class="space-y-3 px-2">
                    <!-- Each MCQ question: ids 5,9,13,17,21 = prompts; 6-8,10-12,14-16,18-20,22-24 = options -->
                    <div
                        v-for="(q, qIdx) in mcq"
                        :key="q.no"
                        :id="`question-${q.no}`"
                        class="relative  p-2 sm:p-4"
                        @mouseenter="hoveredQuestion = q.no"
                        @mouseleave="hoveredQuestion = null"
                    >
                        <!-- Bookmark button -->
                        <button
                            v-show="hoveredQuestion === q.no || isQuestionFlagged(q.no)"
                            @click.stop="toggleFlag(q.no)"
                            class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded border transition-all"
                            :class="
                                isQuestionFlagged(q.no)
                                    ? 'border-gray-400 bg-transparent text-red-500'
                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                            "
                            :title="isQuestionFlagged(q.no) ? 'Remove bookmark' : 'Bookmark this question'"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                            </svg>
                        </button>

                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 shrink-0 text-base  text-gray-900 sm:text">{{ q.no }}.</span>
                            <div class="min-w-0 flex-1">
                                <!-- prompt: base segment id = 5 + qIdx*4 -->
                                <p
                                    class="mb-2 text-base leading-snug text-gray-800 select-text "
                                    :data-segment-id="5 + qIdx * 4"
                                    v-html="getHighlightedSegmentById(5 + qIdx * 4)"
                                ></p>
                                <div class="space-y-1.5">
                                    <!-- options: base = 6 + qIdx*4, 7 + qIdx*4, 8 + qIdx*4 -->
                                    <label
                                        v-for="(opt, oIdx) in q.options"
                                        :key="opt.value"
                                        class="flex cursor-pointer items-start gap-2 p-1.5 transition-colors hover:bg-gray-50 sm:p-2"
                                    >
                                        <input
                                            type="radio"
                                            :name="q.key"
                                            :value="opt.value"
                                            :checked="answers[q.key as keyof typeof answers] === opt.value"
                                            @click="toggleRadio(q.key as keyof typeof answers, opt.value)"
                                            class="mt-0.5 h-4 w-4 shrink-0 accent-black sm:h-5 sm:w-5"
                                        />
                                        <span
                                            class="text-base  leading-relaxed text-gray-800 select-text "
                                            :data-segment-id="6 + qIdx * 4 + oIdx"
                                            v-html="getHighlightedSegmentById(6 + qIdx * 4 + oIdx)"
                                        ></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 Q26–30  DRAG & DROP
            ═══════════════════════════════════════ -->
            <div class="mb-6 bg-white">

                <!-- Instructions -->
                <div class="shrink-0 px-2 pb-3 sm:px-3">
                    <p class="text-base font-bold text-gray-900 select-text"
                        data-segment-id="25"
                        v-html="getHighlightedSegmentById(25)"
                    ></p>
                    <p class="text-sm text-gray-700 select-text"
                        data-segment-id="26"
                        v-html="getHighlightedSegmentById(26)"
                    ></p>
                    <p class="text-sm text-gray-700 select-text">
                        <span data-segment-id="27" v-html="getHighlightedSegmentById(27)"></span>
                        <span class="font-bold" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
                        <span data-segment-id="29" v-html="getHighlightedSegmentById(29)"></span>
                        <span class="font-bold" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></span>
                        <span data-segment-id="31" v-html="getHighlightedSegmentById(31)"></span>
                        <span class="font-bold" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></span>
                        <span data-segment-id="33" v-html="getHighlightedSegmentById(33)"></span>
                    </p>
                </div>

                <!-- Difficulties box + draggable bank -->
                <div class="mx-2 mb-5 border border-gray-800">
                    <div
                        class="border-b border-gray-800 px-4 py-2 font-bold text-gray-900 select-text"
                        data-segment-id="34"
                        v-html="getHighlightedSegmentById(34)"
                    ></div>
                    <!-- Bank: drag from here, drop back to clear -->
                    <!-- Bank: drag from here, drop back to clear -->
                    <div
                        class="flex flex-wrap gap-2 p-3"
                        @dragover="onDragOver($event)"
                        @drop="onBankDrop"
                        @dragleave="onDragLeave"
                    >
                        <!-- ✅ Filtered via computed property -->
                        <div
                            v-for="d in availableDifficulties"
                            :key="d.letter"
                            draggable="true"
                            @dragstart="onBankDragStart(d.letter)"
                            @dragend="onDragEnd"
                            class="flex cursor-grab items-center gap-1.5 rounded border border-gray-400 bg-white px-8 py-1 text-sm font-medium text-gray-900 transition-all hover:border-gray-700 active:cursor-grabbing select-none"
                        >
                            <span
                                class="font-bold"
                                :data-segment-id="35 + difficulties.findIndex(orig => orig.letter === d.letter) * 2"
                                v-html="getHighlightedSegmentById(35 + difficulties.findIndex(orig => orig.letter === d.letter) * 2)"
                            ></span>
                            <span class="text-gray-400">–</span>
                            <span
                                :data-segment-id="36 + difficulties.findIndex(orig => orig.letter === d.letter) * 2"
                                v-html="getHighlightedSegmentById(36 + difficulties.findIndex(orig => orig.letter === d.letter) * 2)"
                            ></span>
                        </div>

                        <!-- Empty state -->
                        <div v-if="availableDifficulties.length === 0"
                            class="text-sm text-gray-500 italic p-2">
                            All items placed! 🎉
                        </div>
                    </div>
                </div>

                <!-- Research techniques with drop slots -->
                <div class="mx-2">
                    <p
                        class="mb-3 text-sm font-bold text-gray-900 select-text sm:text-base"
                        data-segment-id="49"
                        v-html="getHighlightedSegmentById(49)"
                    ></p>

                    <div class="space-y-2">
                        <!-- technique ids: number 50+tIdx*2, text 51+tIdx*2 -->
                        <div
                            v-for="(t, tIdx) in researchTechniques"

                            :key="t.no"
                            :id="`question-${t.no}`"
                            class="relative flex items-center gap-3 rounded border border-gray-200 px-10 py-2"
                            @mouseenter="hoveredQuestion = t.no"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <!-- Bookmark -->
                            <button
                                v-show="hoveredQuestion === t.no || isQuestionFlagged(t.no)"
                                @click.stop="toggleFlag(t.no)"
                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                :class="
                                    isQuestionFlagged(t.no)
                                        ? 'border-gray-400 text-red-500'
                                        : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                "
                                :title="isQuestionFlagged(t.no) ? 'Remove bookmark' : 'Bookmark'"
                            >
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                </svg>
                            </button>

                            <!-- Question number -->
                            <span
                                class="shrink-0 text-sm font-bold text-gray-900 select-text sm:text-base"
                                :data-segment-id="50 + tIdx * 2"
                                v-html="getHighlightedSegmentById(50 + tIdx * 2)"
                            ></span>

                            <!-- Technique name -->
                            <span
                                class="flex-1 text-sm text-gray-800 select-text sm:text-base"
                                :data-segment-id="51 + tIdx * 2"
                                v-html="getHighlightedSegmentById(51 + tIdx * 2)"
                            ></span>

                            <!-- Drop slot -->
                            <span
                                @dragover="onDragOver($event, t.key)"
                                @dragleave="onDragLeave"
                                @drop.prevent="onSlotDrop(t.key as keyof typeof answers)"
                                :class="[
                                    'inline-flex min-w-[15rem] cursor-pointer items-center justify-center rounded border-2 border-dashed px-6 py-0.5 text-sm font-bold transition-all select-none',
                                    answers[t.key as keyof typeof answers]
                                        ? 'border-gray-600 bg-white text-gray-900'
                                        : dragOverSlot === t.key
                                          ? 'border-gray-700 bg-gray-100 text-gray-500'
                                          : 'border-gray-400 bg-white text-gray-400',
                                ]"
                                :draggable="!!answers[t.key as keyof typeof answers]"
                                @dragstart="answers[t.key as keyof typeof answers] ? onSlotDragStart(t.key as keyof typeof answers) : undefined"
                                @dragend="onDragEnd"
                                @dblclick="answers[t.key as keyof typeof answers] = ''"
                                title="Drop here. Double-click to clear."
                            >
                                {{ answers[t.key as keyof typeof answers]
                                    ? getDifficultyLabel(answers[t.key as keyof typeof answers])
                                    : t.no }}
                            </span>
                        </div>
                    </div>
                </div>
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
