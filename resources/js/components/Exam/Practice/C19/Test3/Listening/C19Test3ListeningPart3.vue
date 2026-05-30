<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    phone?: string | null;
    examId?: string;
}

defineProps<Props>();

// Answers State
const answers = ref<Record<string, string>>({
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

// Highlighting & Notes
const contentTextRef = ref<HTMLDivElement | null>(null);
const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// MCQ Questions 21-25
const mcqQuestions = [
    {
        num: 21,
        question: 'How does Clare feel about the students in her Year 12 science class?',
        options: [
            { letter: 'A', text: 'worried that they are not making progress' },
            { letter: 'B', text: 'challenged by their poor behaviour in class' },
            { letter: 'C', text: 'frustrated at their lack of interest in the subject' },
        ],
    },
    {
        num: 22,
        question: "How does Jake react to Clare's suggestion about an experiment based on children's diet?",
        options: [
            { letter: 'A', text: 'He is concerned that the results might not be meaningful.' },
            { letter: 'B', text: 'He feels some of the data might be difficult to obtain.' },
            { letter: 'C', text: 'He suspects that the conclusions might be upsetting.' },
        ],
    },
    {
        num: 23,
        question: 'What problem do they agree may be involved in an experiment involving animals?',
        options: [
            { letter: 'A', text: 'Any results may not apply to humans.' },
            { letter: 'B', text: 'It may be complicated to get permission.' },
            { letter: 'C', text: 'Students may not be happy about animal experiments.' },
        ],
    },
    {
        num: 24,
        question: 'What question do they decide the experiment should address?',
        options: [
            { letter: 'A', text: 'Are mice capable of controlling their food intake?' },
            { letter: 'B', text: 'Does an increase in sugar lead to health problems?' },
            { letter: 'C', text: 'How much do supplements of different kinds affect health?' },
        ],
    },
    {
        num: 25,
        question: 'Clare might also consider doing another experiment involving',
        options: [
            { letter: 'A', text: 'other types of food supplement.' },
            { letter: 'B', text: 'different genetic strains of mice.' },
            { letter: 'C', text: 'varying amounts of exercise.' },
        ],
    },
];

// Flowchart options for Q26-30
const flowchartOptions = [
    { letter: 'A', text: 'size' },
    { letter: 'B', text: 'escape' },
    { letter: 'C', text: 'age' },
    { letter: 'D', text: 'water' },
    { letter: 'E', text: 'cereal' },
    { letter: 'F', text: 'calculations' },
    { letter: 'G', text: 'changes' },
    { letter: 'H', text: 'colour' },
];

// Text segments with continuous offsets for multiline highlighting
const textSegments = ref([
    { id: 'header', text: 'PART 3', offset: 0 },
    { id: 'questions', text: 'Questions 21 - 30', offset: 7 },
    { id: 'instruction21', text: 'Choose the correct letter, A, B or C.', offset: 25 },
    // MCQ Questions
    { id: 'q21', text: 'How does Clare feel about the students in her Year 12 science class?', offset: 63 },
    { id: 'q21_A', text: 'worried that they are not making progress', offset: 132 },
    { id: 'q21_B', text: 'challenged by their poor behaviour in class', offset: 174 },
    { id: 'q21_C', text: 'frustrated at their lack of interest in the subject', offset: 218 },
    { id: 'q22', text: "How does Jake react to Clare's suggestion about an experiment based on children's diet?", offset: 270 },
    { id: 'q22_A', text: 'He is concerned that the results might not be meaningful.', offset: 358 },
    { id: 'q22_B', text: 'He feels some of the data might be difficult to obtain.', offset: 416 },
    { id: 'q22_C', text: 'He suspects that the conclusions might be upsetting.', offset: 472 },
    { id: 'q23', text: 'What problem do they agree may be involved in an experiment involving animals?', offset: 525 },
    { id: 'q23_A', text: 'Any results may not apply to humans.', offset: 604 },
    { id: 'q23_B', text: 'It may be complicated to get permission.', offset: 641 },
    { id: 'q23_C', text: 'Students may not be happy about animal experiments.', offset: 682 },
    { id: 'q24', text: 'What question do they decide the experiment should address?', offset: 734 },
    { id: 'q24_A', text: 'Are mice capable of controlling their food intake?', offset: 794 },
    { id: 'q24_B', text: 'Does an increase in sugar lead to health problems?', offset: 845 },
    { id: 'q24_C', text: 'How much do supplements of different kinds affect health?', offset: 896 },
    { id: 'q25', text: 'Clare might also consider doing another experiment involving', offset: 954 },
    { id: 'q25_A', text: 'other types of food supplement.', offset: 1015 },
    { id: 'q25_B', text: 'different genetic strains of mice.', offset: 1047 },
    { id: 'q25_C', text: 'varying amounts of exercise.', offset: 1082 },
    // Flowchart section
    { id: 'instruction26', text: 'Complete the flow-chart below.', offset: 1111 },
    { id: 'instruction26b', text: 'Choose FIVE answers from the box and write the correct letter, A-H, next to Questions 26-30.', offset: 1142 },
    // Flowchart options
    { id: 'fc_A', text: 'size', offset: 1235 },
    { id: 'fc_B', text: 'escape', offset: 1240 },
    { id: 'fc_C', text: 'age', offset: 1247 },
    { id: 'fc_D', text: 'water', offset: 1251 },
    { id: 'fc_E', text: 'cereal', offset: 1257 },
    { id: 'fc_F', text: 'calculations', offset: 1264 },
    { id: 'fc_G', text: 'changes', offset: 1277 },
    { id: 'fc_H', text: 'colour', offset: 1285 },
    { id: 'flowchartTitle', text: 'Experiment Plan', offset: 1292 },
    { id: 'step1', text: 'Divide mice into two groups according to their', offset: 1308 },
    { id: 'step2', text: 'Add supplements to their', offset: 1363 },
    { id: 'step3', text: 'Make cages that prevent mice from', offset: 1396 },
    { id: 'step4', text: 'Carry out', offset: 1438 },
    { id: 'step4b', text: 'each week', offset: 1456 },
    { id: 'step5', text: 'Record', offset: 1466 },
    { id: 'step5b', text: 'in data for each mouse', offset: 1481 },
]);

// Get highlighted segment with multiline support
const getHighlightedSegment = (segmentText: string, segmentId?: string) => {
    const segment = segmentId ? textSegments.value.find((s) => s.id === segmentId) : textSegments.value.find((s) => s.text === segmentText);

    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    if (overlappingHighlights.length === 0) return segmentText;

    const sorted = [...overlappingHighlights].sort((a, b) => b.start_offset - a.start_offset);

    let result = segmentText;
    for (const highlight of sorted) {
        const highlightStart = Math.max(0, highlight.start_offset - segmentOffset);
        const highlightEnd = Math.min(segmentText.length, highlight.end_offset - segmentOffset);

        if (highlightStart < highlightEnd) {
            const before = result.substring(0, highlightStart);
            const highlighted = result.substring(highlightStart, highlightEnd);
            const after = result.substring(highlightEnd);
            result = `${before}<mark class="highlight-${highlight.color} transition-all duration-300" data-highlight-id="${highlight.id}">${highlighted}</mark>${after}`;
        }
    }
    return result;
};

// Handle text selection
const handleContentTextSelect = (event: MouseEvent) => {
    if (!contentTextRef.value?.contains(event.target as Node)) return;

    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0 || selection.toString().trim().length === 0) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container: Node | null = node;
            if (container.nodeType !== Node.ELEMENT_NODE) container = container.parentNode;

            const segmentEl = (container as Element)?.closest('[data-segment-id]');
            if (!segmentEl) return null;

            const segmentId = segmentEl.getAttribute('data-segment-id');
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) return null;

            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT, null);
            let currentNode;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) {
                    offsetInSegment += offsetInNode;
                    break;
                } else {
                    offsetInSegment += currentNode.textContent?.length || 0;
                }
            }
            return segment.offset + offsetInSegment;
        };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset = getAbsoluteOffset(range.endContainer, range.endOffset);

        if (startAbsOffset === null || endAbsOffset === null) {
            showContextMenu.value = false;
            window.getSelection()?.removeAllRanges();
            return;
        }

        if (startAbsOffset > endAbsOffset) {
            [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];
        }

        contextMenuPosition.value = { x: event.clientX, y: event.clientY };
        showContextMenu.value = true;
        selectedText.value = selection.toString();
        selectionRange.value = { start: startAbsOffset, end: endAbsOffset };
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 50 };
    showNoteInput.value = true;
    showContextMenu.value = false;
    nextTick(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus());
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    addHighlight(selectedText.value, 'yellow', selectionRange.value.start, selectionRange.value.end);
    notes.value.push({
        id: Date.now(),
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: selectionRange.value.start,
        end: selectionRange.value.end,
        part: 'Part 3',
    });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => {
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const deleteNote = (id: number) => {
    notes.value = notes.value.filter((note) => note.id !== id);
};

const handleClickOutside = (event: MouseEvent) => {
    if (showContextMenu.value) {
        const contextMenu = document.querySelector('.context-menu');
        if (contextMenu && !contextMenu.contains(event.target as Node)) {
            showContextMenu.value = false;
        }
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        if (showContextMenu.value) showContextMenu.value = false;
        if (showNoteInput.value) showNoteInput.value = false;
    }
};

const scrollToQuestion = (questionNumber: number) => {
    const inputEl = document.querySelector(`[data-question="${questionNumber}"]`);
    if (inputEl) {
        inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

onMounted(() => {
    document.addEventListener('mouseup', handleContentTextSelect);
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('mouseup', handleContentTextSelect);
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

defineExpose({
    getAnswers: () => answers.value,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" class="mx-auto mb-16 px-4 py-8">
        <div class="animate-fadeIn w-full rounded-2xl bg-white/70 p-6 shadow-2xl backdrop-blur-xl">
            <!-- Header - Simple style like Test 1 -->
            <div class="flex-shrink-0 border-b border-gray-200 bg-white p-4 lg:p-6">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center space-x-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600">
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                ></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold tracking-widest text-emerald-700 uppercase">
                                <span data-segment-id="header" v-html="getHighlightedSegment('PART 3', 'header')"></span>
                            </h2>
                            <h1 class="text-base font-bold text-gray-900">
                                <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 21 - 30', 'questions')"></span>
                            </h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions 21-25: MCQ -->
            <div class="animate-slideUp mb-8 pt-6" style="animation-delay: 0.1s">
                <div class="mb-4">
                    <div class="mb-2 inline-block rounded-lg bg-emerald-100 px-3 py-1">
                        <span class="text-sm font-semibold text-emerald-700">Questions 21-25</span>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-emerald-100 to-teal-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="instruction21"
                            class="text-sm font-semibold text-emerald-800"
                            v-html="getHighlightedSegment('Choose the correct letter, A, B or C.', 'instruction21')"
                        ></span>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Question 21 -->
                    <div
                        :data-question="21"
                        class="rounded-2xl border border-emerald-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl"
                    >
                        <div class="mb-4 flex items-start gap-4">
                            <span
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 font-bold text-white shadow-lg"
                                >21</span
                            >
                            <p
                                data-segment-id="q21"
                                class="pt-2 font-medium text-gray-800"
                                v-html="getHighlightedSegment('How does Clare feel about the students in her Year 12 science class?', 'q21')"
                            ></p>
                        </div>
                        <div class="ml-14 space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q21 === 'A'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q21" value="A" v-model="answers.q21" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q21 === 'A'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">A</span>
                                </div>
                                <span
                                    data-segment-id="q21_A"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q21 === 'A' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('worried that they are not making progress', 'q21_A')"
                                ></span>
                                <div v-if="answers.q21 === 'A'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q21 === 'B'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q21" value="B" v-model="answers.q21" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q21 === 'B'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">B</span>
                                </div>
                                <span
                                    data-segment-id="q21_B"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q21 === 'B' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('challenged by their poor behaviour in class', 'q21_B')"
                                ></span>
                                <div v-if="answers.q21 === 'B'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q21 === 'C'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q21" value="C" v-model="answers.q21" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q21 === 'C'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">C</span>
                                </div>
                                <span
                                    data-segment-id="q21_C"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q21 === 'C' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('frustrated at their lack of interest in the subject', 'q21_C')"
                                ></span>
                                <div v-if="answers.q21 === 'C'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Question 22 -->
                    <div
                        :data-question="22"
                        class="rounded-2xl border border-emerald-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl"
                    >
                        <div class="mb-4 flex items-start gap-4">
                            <span
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 font-bold text-white shadow-lg"
                                >22</span
                            >
                            <p
                                data-segment-id="q22"
                                class="pt-2 font-medium text-gray-800"
                                v-html="
                                    getHighlightedSegment(
                                        `How does Jake react to Clare's suggestion about an experiment based on children's diet?`,
                                        'q22',
                                    )
                                "
                            ></p>
                        </div>
                        <div class="ml-14 space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q22 === 'A'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q22" value="A" v-model="answers.q22" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q22 === 'A'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">A</span>
                                </div>
                                <span
                                    data-segment-id="q22_A"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q22 === 'A' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('He is concerned that the results might not be meaningful.', 'q22_A')"
                                ></span>
                                <div v-if="answers.q22 === 'A'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q22 === 'B'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q22" value="B" v-model="answers.q22" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q22 === 'B'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">B</span>
                                </div>
                                <span
                                    data-segment-id="q22_B"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q22 === 'B' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('He feels some of the data might be difficult to obtain.', 'q22_B')"
                                ></span>
                                <div v-if="answers.q22 === 'B'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q22 === 'C'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q22" value="C" v-model="answers.q22" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q22 === 'C'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">C</span>
                                </div>
                                <span
                                    data-segment-id="q22_C"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q22 === 'C' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('He suspects that the conclusions might be upsetting.', 'q22_C')"
                                ></span>
                                <div v-if="answers.q22 === 'C'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Question 23 -->
                    <div
                        :data-question="23"
                        class="rounded-2xl border border-emerald-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl"
                    >
                        <div class="mb-4 flex items-start gap-4">
                            <span
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 font-bold text-white shadow-lg"
                                >23</span
                            >
                            <p
                                data-segment-id="q23"
                                class="pt-2 font-medium text-gray-800"
                                v-html="
                                    getHighlightedSegment('What problem do they agree may be involved in an experiment involving animals?', 'q23')
                                "
                            ></p>
                        </div>
                        <div class="ml-14 space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q23 === 'A'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q23" value="A" v-model="answers.q23" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q23 === 'A'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">A</span>
                                </div>
                                <span
                                    data-segment-id="q23_A"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q23 === 'A' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('Any results may not apply to humans.', 'q23_A')"
                                ></span>
                                <div v-if="answers.q23 === 'A'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q23 === 'B'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q23" value="B" v-model="answers.q23" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q23 === 'B'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">B</span>
                                </div>
                                <span
                                    data-segment-id="q23_B"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q23 === 'B' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('It may be complicated to get permission.', 'q23_B')"
                                ></span>
                                <div v-if="answers.q23 === 'B'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q23 === 'C'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q23" value="C" v-model="answers.q23" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q23 === 'C'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">C</span>
                                </div>
                                <span
                                    data-segment-id="q23_C"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q23 === 'C' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('Students may not be happy about animal experiments.', 'q23_C')"
                                ></span>
                                <div v-if="answers.q23 === 'C'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Question 24 -->
                    <div
                        :data-question="24"
                        class="rounded-2xl border border-emerald-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl"
                    >
                        <div class="mb-4 flex items-start gap-4">
                            <span
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 font-bold text-white shadow-lg"
                                >24</span
                            >
                            <p
                                data-segment-id="q24"
                                class="pt-2 font-medium text-gray-800"
                                v-html="getHighlightedSegment('What question do they decide the experiment should address?', 'q24')"
                            ></p>
                        </div>
                        <div class="ml-14 space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q24 === 'A'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q24" value="A" v-model="answers.q24" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q24 === 'A'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">A</span>
                                </div>
                                <span
                                    data-segment-id="q24_A"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q24 === 'A' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('Are mice capable of controlling their food intake?', 'q24_A')"
                                ></span>
                                <div v-if="answers.q24 === 'A'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q24 === 'B'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q24" value="B" v-model="answers.q24" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q24 === 'B'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">B</span>
                                </div>
                                <span
                                    data-segment-id="q24_B"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q24 === 'B' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('Does an increase in sugar lead to health problems?', 'q24_B')"
                                ></span>
                                <div v-if="answers.q24 === 'B'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q24 === 'C'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q24" value="C" v-model="answers.q24" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q24 === 'C'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">C</span>
                                </div>
                                <span
                                    data-segment-id="q24_C"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q24 === 'C' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('How much do supplements of different kinds affect health?', 'q24_C')"
                                ></span>
                                <div v-if="answers.q24 === 'C'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Question 25 -->
                    <div
                        :data-question="25"
                        class="rounded-2xl border border-emerald-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl"
                    >
                        <div class="mb-4 flex items-start gap-4">
                            <span
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 font-bold text-white shadow-lg"
                                >25</span
                            >
                            <p
                                data-segment-id="q25"
                                class="pt-2 font-medium text-gray-800"
                                v-html="getHighlightedSegment('Clare might also consider doing another experiment involving', 'q25')"
                            ></p>
                        </div>
                        <div class="ml-14 space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q25 === 'A'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q25" value="A" v-model="answers.q25" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q25 === 'A'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">A</span>
                                </div>
                                <span
                                    data-segment-id="q25_A"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q25 === 'A' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('other types of food supplement.', 'q25_A')"
                                ></span>
                                <div v-if="answers.q25 === 'A'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q25 === 'B'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q25" value="B" v-model="answers.q25" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q25 === 'B'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">B</span>
                                </div>
                                <span
                                    data-segment-id="q25_B"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q25 === 'B' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('different genetic strains of mice.', 'q25_B')"
                                ></span>
                                <div v-if="answers.q25 === 'B'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                                :class="
                                    answers.q25 === 'C'
                                        ? 'border-2 border-emerald-400 bg-gradient-to-r from-emerald-100 to-teal-100 shadow-md'
                                        : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-emerald-50 hover:to-teal-50'
                                "
                            >
                                <input type="radio" name="q25" value="C" v-model="answers.q25" class="sr-only" />
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                    :class="
                                        answers.q25 === 'C'
                                            ? 'scale-110 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    <span class="font-bold">C</span>
                                </div>
                                <span
                                    data-segment-id="q25_C"
                                    class="flex-1 text-gray-700"
                                    :class="answers.q25 === 'C' ? 'font-semibold text-gray-900' : ''"
                                    v-html="getHighlightedSegment('varying amounts of exercise.', 'q25_C')"
                                ></span>
                                <div v-if="answers.q25 === 'C'" class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions 26-30: Flowchart -->
            <div class="animate-slideUp border-t-2 border-dashed border-emerald-200 pt-8" style="animation-delay: 0.2s">
                <div class="mb-4">
                    <div class="mb-2 inline-block rounded-lg bg-teal-100 px-3 py-1">
                        <span class="text-sm font-semibold text-teal-700">Questions 26-30</span>
                    </div>
                    <p class="mb-1 text-sm text-gray-600">
                        <span
                            data-segment-id="instruction26"
                            v-html="getHighlightedSegment('Complete the flow-chart below.', 'instruction26')"
                        ></span>
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-100 to-emerald-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="instruction26b"
                            class="text-sm font-semibold text-teal-800"
                            v-html="
                                getHighlightedSegment(
                                    'Choose FIVE answers from the box and write the correct letter, A-H, next to Questions 26-30.',
                                    'instruction26b',
                                )
                            "
                        ></span>
                    </div>
                </div>

                <!-- Options Box -->
                <div class="mb-6 rounded-xl border border-teal-200 bg-gradient-to-br from-teal-50 to-emerald-50 p-5">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div
                            class="flex items-center gap-3 rounded-lg border border-teal-100 bg-white p-3 shadow-sm transition-colors hover:border-teal-300"
                        >
                            <span
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-md"
                                >A</span
                            >
                            <span
                                data-segment-id="fc_A"
                                class="text-sm font-medium text-gray-700"
                                v-html="getHighlightedSegment('size', 'fc_A')"
                            ></span>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-lg border border-teal-100 bg-white p-3 shadow-sm transition-colors hover:border-teal-300"
                        >
                            <span
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-md"
                                >B</span
                            >
                            <span
                                data-segment-id="fc_B"
                                class="text-sm font-medium text-gray-700"
                                v-html="getHighlightedSegment('escape', 'fc_B')"
                            ></span>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-lg border border-teal-100 bg-white p-3 shadow-sm transition-colors hover:border-teal-300"
                        >
                            <span
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-md"
                                >C</span
                            >
                            <span
                                data-segment-id="fc_C"
                                class="text-sm font-medium text-gray-700"
                                v-html="getHighlightedSegment('age', 'fc_C')"
                            ></span>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-lg border border-teal-100 bg-white p-3 shadow-sm transition-colors hover:border-teal-300"
                        >
                            <span
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-md"
                                >D</span
                            >
                            <span
                                data-segment-id="fc_D"
                                class="text-sm font-medium text-gray-700"
                                v-html="getHighlightedSegment('water', 'fc_D')"
                            ></span>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-lg border border-teal-100 bg-white p-3 shadow-sm transition-colors hover:border-teal-300"
                        >
                            <span
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-md"
                                >E</span
                            >
                            <span
                                data-segment-id="fc_E"
                                class="text-sm font-medium text-gray-700"
                                v-html="getHighlightedSegment('cereal', 'fc_E')"
                            ></span>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-lg border border-teal-100 bg-white p-3 shadow-sm transition-colors hover:border-teal-300"
                        >
                            <span
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-md"
                                >F</span
                            >
                            <span
                                data-segment-id="fc_F"
                                class="text-sm font-medium text-gray-700"
                                v-html="getHighlightedSegment('calculations', 'fc_F')"
                            ></span>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-lg border border-teal-100 bg-white p-3 shadow-sm transition-colors hover:border-teal-300"
                        >
                            <span
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-md"
                                >G</span
                            >
                            <span
                                data-segment-id="fc_G"
                                class="text-sm font-medium text-gray-700"
                                v-html="getHighlightedSegment('changes', 'fc_G')"
                            ></span>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-lg border border-teal-100 bg-white p-3 shadow-sm transition-colors hover:border-teal-300"
                        >
                            <span
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-md"
                                >H</span
                            >
                            <span
                                data-segment-id="fc_H"
                                class="text-sm font-medium text-gray-700"
                                v-html="getHighlightedSegment('colour', 'fc_H')"
                            ></span>
                        </div>
                    </div>
                </div>

                <!-- Flowchart -->
                <div class="rounded-2xl border border-teal-100 bg-white/80 p-6 shadow-lg">
                    <h3
                        class="mb-8 border-b-2 border-teal-200 bg-gradient-to-r from-teal-600 to-emerald-600 bg-clip-text pb-2 text-center text-xl font-bold text-transparent"
                    >
                        <span data-segment-id="flowchartTitle" v-html="getHighlightedSegment('Experiment Plan', 'flowchartTitle')"></span>
                    </h3>

                    <div class="space-y-4">
                        <!-- Step 1 -->
                        <div
                            :data-question="26"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-teal-50/80 to-emerald-50/80 p-4 transition-all duration-300 hover:from-teal-100/80 hover:to-emerald-100/80"
                        >
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >26</span
                            >
                            <span
                                data-segment-id="step1"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Divide mice into two groups according to their', 'step1')"
                            ></span>
                            <select
                                v-model="answers.q26"
                                class="w-20 cursor-pointer rounded-xl border-2 border-teal-300 bg-white px-3 py-2.5 text-center font-bold text-teal-700 transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in flowchartOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                        </div>

                        <div class="flex justify-center">
                            <svg class="h-8 w-8 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </div>

                        <!-- Step 2 -->
                        <div
                            :data-question="27"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-emerald-50/80 to-teal-50/80 p-4 transition-all duration-300 hover:from-emerald-100/80 hover:to-teal-100/80"
                        >
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >27</span
                            >
                            <span
                                data-segment-id="step2"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Add supplements to their', 'step2')"
                            ></span>
                            <select
                                v-model="answers.q27"
                                class="w-20 cursor-pointer rounded-xl border-2 border-emerald-300 bg-white px-3 py-2.5 text-center font-bold text-emerald-700 transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in flowchartOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                        </div>

                        <div class="flex justify-center">
                            <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </div>

                        <!-- Step 3 -->
                        <div
                            :data-question="28"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-teal-50/80 to-emerald-50/80 p-4 transition-all duration-300 hover:from-teal-100/80 hover:to-emerald-100/80"
                        >
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >28</span
                            >
                            <span
                                data-segment-id="step3"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Make cages that prevent mice from', 'step3')"
                            ></span>
                            <select
                                v-model="answers.q28"
                                class="w-20 cursor-pointer rounded-xl border-2 border-teal-300 bg-white px-3 py-2.5 text-center font-bold text-teal-700 transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in flowchartOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                        </div>

                        <div class="flex justify-center">
                            <svg class="h-8 w-8 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </div>

                        <!-- Step 4 -->
                        <div
                            :data-question="29"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-emerald-50/80 to-teal-50/80 p-4 transition-all duration-300 hover:from-emerald-100/80 hover:to-teal-100/80"
                        >
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >29</span
                            >
                            <span data-segment-id="step4" class="text-gray-700" v-html="getHighlightedSegment('Carry out', 'step4')"></span>
                            <select
                                v-model="answers.q29"
                                class="w-20 cursor-pointer rounded-xl border-2 border-emerald-300 bg-white px-3 py-2.5 text-center font-bold text-emerald-700 transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in flowchartOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                            <span data-segment-id="step4b" class="text-gray-700" v-html="getHighlightedSegment('each week', 'step4b')"></span>
                        </div>

                        <div class="flex justify-center">
                            <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </div>

                        <!-- Step 5 -->
                        <div
                            :data-question="30"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-teal-50/80 to-emerald-50/80 p-4 transition-all duration-300 hover:from-teal-100/80 hover:to-emerald-100/80"
                        >
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >30</span
                            >
                            <span data-segment-id="step5" class="text-gray-700" v-html="getHighlightedSegment('Record', 'step5')"></span>
                            <select
                                v-model="answers.q30"
                                class="w-20 cursor-pointer rounded-xl border-2 border-teal-300 bg-white px-3 py-2.5 text-center font-bold text-teal-700 transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in flowchartOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                            <span
                                data-segment-id="step5b"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('in data for each mouse', 'step5b')"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="showContextMenu"
                    class="pointer-events-none fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px` }"
                >
                    <div
                        class="context-menu pointer-events-auto rounded-xl border border-gray-200 bg-white/95 p-3 shadow-2xl backdrop-blur-sm"
                        @click.stop
                    >
                        <div class="flex items-center gap-2">
                            <button
                                v-for="color in colors"
                                :key="color.name"
                                @click="applyHighlight(color.name)"
                                :class="[
                                    color.class,
                                    'h-9 w-9 rounded-lg border-2 border-gray-200 transition-all duration-200 hover:scale-110 hover:border-gray-400 hover:shadow-md',
                                ]"
                                :title="`Highlight ${color.name}`"
                            ></button>
                            <div class="mx-1 h-6 w-px bg-gray-200"></div>
                            <button
                                @click="openNoteInput"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-emerald-200 bg-emerald-50 transition-all duration-200 hover:scale-110 hover:bg-emerald-100 hover:shadow-md"
                                title="Add Note"
                            >
                                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    ></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Note Input Modal -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="showNoteInput"
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-emerald-300 bg-white p-4 shadow-2xl"
                    :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="mb-3">
                        <p class="mb-3 line-clamp-2 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 italic">
                            "{{ selectedText }}"
                        </p>
                        <input
                            v-model="noteInputText"
                            type="text"
                            placeholder="Write your note here..."
                            class="note-input-field w-full rounded-lg border-2 border-emerald-200 px-4 py-3 text-sm transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                            @keyup.enter="saveNote"
                            @keyup.escape="cancelNote"
                        />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button
                            @click="cancelNote"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveNote"
                            class="rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-emerald-500/20 transition-all hover:from-emerald-700 hover:to-teal-700"
                        >
                            Save Note
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
mark[data-highlight-id] {
    padding: 2px 4px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}
mark[data-highlight-id]:hover {
    filter: brightness(0.95);
}
.highlight-nocolor {
    background-color: transparent;
}
.highlight-yellow {
    background-color: rgba(254, 240, 138, 0.8);
}
.highlight-green {
    background-color: rgba(187, 247, 208, 0.8);
}
.highlight-blue {
    background-color: rgba(191, 219, 254, 0.8);
}
.highlight-pink {
    background-color: rgba(251, 207, 232, 0.8);
}
.highlight-orange {
    background-color: rgba(254, 215, 170, 0.8);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.5s ease-out;
}
.animate-slideUp {
    animation: slideUp 0.5s ease-out both;
}
</style>
