<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

interface Props {
    phone?: string | null;
    examId?: string;
}

defineProps<Props>();

// Answers State
const answers = ref<Record<string, string>>({
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

// Highlighting & Notes
const contentTextRef = ref<HTMLDivElement | null>(null);
const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Multiple answer selections for Choose TWO questions
const multipleAnswers = reactive({
    q17_18: [] as string[],
    q19_20: [] as string[],
});

watch(
    () => multipleAnswers.q17_18,
    (newVal) => {
        answers.value.q17 = newVal[0] || '';
        answers.value.q18 = newVal[1] || '';
    },
    { deep: true },
);

watch(
    () => multipleAnswers.q19_20,
    (newVal) => {
        answers.value.q19 = newVal[0] || '';
        answers.value.q20 = newVal[1] || '';
    },
    { deep: true },
);

const toggleMultipleAnswer = (group: 'q17_18' | 'q19_20', value: string) => {
    const arr = multipleAnswers[group];
    const index = arr.indexOf(value);
    if (index > -1) {
        arr.splice(index, 1);
    } else if (arr.length < 2) {
        arr.push(value);
    }
};

const isSelected = (group: 'q17_18' | 'q19_20', value: string) => {
    return multipleAnswers[group].includes(value);
};

// Matching options
const matchingOptions = [
    { letter: 'A', text: 'involves painting and drawing' },
    { letter: 'B', text: 'will be led by a prize-winning author' },
    { letter: 'C', text: 'is aimed at children with a disability' },
    { letter: 'D', text: 'involves a drama activity' },
    { letter: 'E', text: 'focuses on new relationships' },
    { letter: 'F', text: 'is aimed at a specific age group' },
    { letter: 'G', text: 'explores an unhappy feeling' },
    { letter: 'H', text: 'raises awareness of a particular culture' },
];

const workshops = [
    { num: 11, name: 'Superheroes' },
    { num: 12, name: 'Just do it' },
    { num: 13, name: 'Count on me' },
    { num: 14, name: 'Speak up' },
    { num: 15, name: 'Jump for joy' },
    { num: 16, name: 'Sticks and stones' },
];

const aliveKickingOptions = [
    { letter: 'A', text: 'It will appeal to both boys and girls.' },
    { letter: 'B', text: 'The author is well known.' },
    { letter: 'C', text: 'It has colourful illustrations.' },
    { letter: 'D', text: 'It is funny.' },
    { letter: 'E', text: 'It deals with an important topic.' },
];

const readingAdviceOptions = [
    { letter: 'A', text: 'Encourage children to write down new vocabulary.' },
    { letter: 'B', text: 'Allow children to listen to audio books.' },
    { letter: 'C', text: 'Get recommendations from librarians.' },
    { letter: 'D', text: 'Give children a choice about what they read.' },
    { letter: 'E', text: 'Only read aloud to children until they can read independently.' },
];

// Text segments with continuous offsets for multiline highlighting
const textSegments = ref([
    { id: 'header', text: 'PART 2', offset: 0 },
    { id: 'questions', text: 'Questions 11 - 20', offset: 7 },
    { id: 'title', text: 'Festival workshops', offset: 25 },
    { id: 'instruction11', text: 'What information is given about each of the following festival workshops?', offset: 44 },
    { id: 'instruction11b', text: 'Choose SIX answers from the box and write the correct letter, A-H, next to Questions 11-16.', offset: 119 },
    { id: 'infoTitle', text: 'Information', offset: 211 },
    // Matching options A-H
    { id: 'optA', text: 'involves painting and drawing', offset: 223 },
    { id: 'optB', text: 'will be led by a prize-winning author', offset: 253 },
    { id: 'optC', text: 'is aimed at children with a disability', offset: 291 },
    { id: 'optD', text: 'involves a drama activity', offset: 330 },
    { id: 'optE', text: 'focuses on new relationships', offset: 356 },
    { id: 'optF', text: 'is aimed at a specific age group', offset: 385 },
    { id: 'optG', text: 'explores an unhappy feeling', offset: 418 },
    { id: 'optH', text: 'raises awareness of a particular culture', offset: 446 },
    { id: 'workshopsTitle', text: 'Festival workshops', offset: 487 },
    // Workshop names
    { id: 'w11', text: 'Superheroes', offset: 506 },
    { id: 'w12', text: 'Just do it', offset: 518 },
    { id: 'w13', text: 'Count on me', offset: 529 },
    { id: 'w14', text: 'Speak up', offset: 541 },
    { id: 'w15', text: 'Jump for joy', offset: 550 },
    { id: 'w16', text: 'Sticks and stones', offset: 563 },
    // Questions 17-18
    { id: 'instruction17', text: 'Which TWO reasons does the speaker give for recommending Alive and Kicking?', offset: 581 },
    { id: 'instruction17b', text: 'Choose TWO letters, A-E.', offset: 657 },
    // Alive and Kicking options
    { id: 'ak_A', text: 'It will appeal to both boys and girls.', offset: 682 },
    { id: 'ak_B', text: 'The author is well known.', offset: 721 },
    { id: 'ak_C', text: 'It has colourful illustrations.', offset: 747 },
    { id: 'ak_D', text: 'It is funny.', offset: 779 },
    { id: 'ak_E', text: 'It deals with an important topic.', offset: 792 },
    // Questions 19-20
    { id: 'instruction19', text: 'Which TWO pieces of advice does the speaker give about reading?', offset: 826 },
    { id: 'instruction19b', text: 'Choose TWO letters, A-E.', offset: 890 },
    // Reading advice options
    { id: 'ra_A', text: 'Encourage children to write down new vocabulary.', offset: 915 },
    { id: 'ra_B', text: 'Allow children to listen to audio books.', offset: 964 },
    { id: 'ra_C', text: 'Get recommendations from librarians.', offset: 1005 },
    { id: 'ra_D', text: 'Give children a choice about what they read.', offset: 1042 },
    { id: 'ra_E', text: 'Only read aloud to children until they can read independently.', offset: 1087 },
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
        part: 'Part 2',
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
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-violet-500 to-purple-600">
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
                            <h2 class="text-base font-bold tracking-widest text-violet-700 uppercase">
                                <span data-segment-id="header" v-html="getHighlightedSegment('PART 2', 'header')"></span>
                            </h2>
                            <h1 class="text-base font-bold text-gray-900">
                                <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 11 - 20', 'questions')"></span>
                            </h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions 11-16: Matching -->
            <div class="animate-slideUp mb-8 pt-6" style="animation-delay: 0.1s">
                <div class="mb-4">
                    <div class="mb-2 inline-block rounded-lg bg-violet-100 px-3 py-1">
                        <span class="text-sm font-semibold text-violet-700">Questions 11-16</span>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-gray-800">
                        <span data-segment-id="title" v-html="getHighlightedSegment('Festival workshops', 'title')"></span>
                    </h3>
                    <p class="mb-1 text-sm text-gray-600">
                        <span
                            data-segment-id="instruction11"
                            v-html="
                                getHighlightedSegment('What information is given about each of the following festival workshops?', 'instruction11')
                            "
                        ></span>
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-violet-100 to-purple-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="instruction11b"
                            class="text-sm font-semibold text-violet-800"
                            v-html="
                                getHighlightedSegment(
                                    'Choose SIX answers from the box and write the correct letter, A-H, next to Questions 11-16.',
                                    'instruction11b',
                                )
                            "
                        ></span>
                    </div>
                </div>

                <div class="rounded-2xl border border-violet-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <!-- Information Box -->
                    <div class="mb-6 rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-purple-50 p-5">
                        <h3 class="mb-4 text-center font-bold text-violet-800">
                            <span data-segment-id="infoTitle" v-html="getHighlightedSegment('Information', 'infoTitle')"></span>
                        </h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div
                                class="flex items-start gap-3 rounded-lg border border-violet-100 bg-white p-3 shadow-sm transition-colors hover:border-violet-300"
                            >
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-md"
                                    >A</span
                                >
                                <span
                                    data-segment-id="optA"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('involves painting and drawing', 'optA')"
                                ></span>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-lg border border-violet-100 bg-white p-3 shadow-sm transition-colors hover:border-violet-300"
                            >
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-md"
                                    >B</span
                                >
                                <span
                                    data-segment-id="optB"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('will be led by a prize-winning author', 'optB')"
                                ></span>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-lg border border-violet-100 bg-white p-3 shadow-sm transition-colors hover:border-violet-300"
                            >
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-md"
                                    >C</span
                                >
                                <span
                                    data-segment-id="optC"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('is aimed at children with a disability', 'optC')"
                                ></span>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-lg border border-violet-100 bg-white p-3 shadow-sm transition-colors hover:border-violet-300"
                            >
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-md"
                                    >D</span
                                >
                                <span
                                    data-segment-id="optD"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('involves a drama activity', 'optD')"
                                ></span>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-lg border border-violet-100 bg-white p-3 shadow-sm transition-colors hover:border-violet-300"
                            >
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-md"
                                    >E</span
                                >
                                <span
                                    data-segment-id="optE"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('focuses on new relationships', 'optE')"
                                ></span>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-lg border border-violet-100 bg-white p-3 shadow-sm transition-colors hover:border-violet-300"
                            >
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-md"
                                    >F</span
                                >
                                <span
                                    data-segment-id="optF"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('is aimed at a specific age group', 'optF')"
                                ></span>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-lg border border-violet-100 bg-white p-3 shadow-sm transition-colors hover:border-violet-300"
                            >
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-md"
                                    >G</span
                                >
                                <span
                                    data-segment-id="optG"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('explores an unhappy feeling', 'optG')"
                                ></span>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-lg border border-violet-100 bg-white p-3 shadow-sm transition-colors hover:border-violet-300"
                            >
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-md"
                                    >H</span
                                >
                                <span
                                    data-segment-id="optH"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('raises awareness of a particular culture', 'optH')"
                                ></span>
                            </div>
                        </div>
                    </div>

                    <!-- Workshops -->
                    <h3 class="mb-4 text-center text-lg font-bold text-gray-800">
                        <span data-segment-id="workshopsTitle" v-html="getHighlightedSegment('Festival workshops', 'workshopsTitle')"></span>
                    </h3>
                    <div class="space-y-3">
                        <div
                            :data-question="11"
                            class="group flex items-center gap-4 rounded-xl bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-4 transition-all hover:from-violet-100/80 hover:to-purple-100/80"
                        >
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >11</span
                            >
                            <span
                                data-segment-id="w11"
                                class="flex-1 font-semibold text-gray-800"
                                v-html="getHighlightedSegment('Superheroes', 'w11')"
                            ></span>
                            <select
                                v-model="answers.q11"
                                class="w-20 cursor-pointer rounded-xl border-2 border-violet-300 bg-white px-3 py-2.5 text-center font-bold text-violet-700 transition-all focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in matchingOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                        </div>
                        <div
                            :data-question="12"
                            class="group flex items-center gap-4 rounded-xl bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-4 transition-all hover:from-violet-100/80 hover:to-purple-100/80"
                        >
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >12</span
                            >
                            <span
                                data-segment-id="w12"
                                class="flex-1 font-semibold text-gray-800"
                                v-html="getHighlightedSegment('Just do it', 'w12')"
                            ></span>
                            <select
                                v-model="answers.q12"
                                class="w-20 cursor-pointer rounded-xl border-2 border-violet-300 bg-white px-3 py-2.5 text-center font-bold text-violet-700 transition-all focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in matchingOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                        </div>
                        <div
                            :data-question="13"
                            class="group flex items-center gap-4 rounded-xl bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-4 transition-all hover:from-violet-100/80 hover:to-purple-100/80"
                        >
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >13</span
                            >
                            <span
                                data-segment-id="w13"
                                class="flex-1 font-semibold text-gray-800"
                                v-html="getHighlightedSegment('Count on me', 'w13')"
                            ></span>
                            <select
                                v-model="answers.q13"
                                class="w-20 cursor-pointer rounded-xl border-2 border-violet-300 bg-white px-3 py-2.5 text-center font-bold text-violet-700 transition-all focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in matchingOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                        </div>
                        <div
                            :data-question="14"
                            class="group flex items-center gap-4 rounded-xl bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-4 transition-all hover:from-violet-100/80 hover:to-purple-100/80"
                        >
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >14</span
                            >
                            <span
                                data-segment-id="w14"
                                class="flex-1 font-semibold text-gray-800"
                                v-html="getHighlightedSegment('Speak up', 'w14')"
                            ></span>
                            <select
                                v-model="answers.q14"
                                class="w-20 cursor-pointer rounded-xl border-2 border-violet-300 bg-white px-3 py-2.5 text-center font-bold text-violet-700 transition-all focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in matchingOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                        </div>
                        <div
                            :data-question="15"
                            class="group flex items-center gap-4 rounded-xl bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-4 transition-all hover:from-violet-100/80 hover:to-purple-100/80"
                        >
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >15</span
                            >
                            <span
                                data-segment-id="w15"
                                class="flex-1 font-semibold text-gray-800"
                                v-html="getHighlightedSegment('Jump for joy', 'w15')"
                            ></span>
                            <select
                                v-model="answers.q15"
                                class="w-20 cursor-pointer rounded-xl border-2 border-violet-300 bg-white px-3 py-2.5 text-center font-bold text-violet-700 transition-all focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in matchingOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                        </div>
                        <div
                            :data-question="16"
                            class="group flex items-center gap-4 rounded-xl bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-4 transition-all hover:from-violet-100/80 hover:to-purple-100/80"
                        >
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >16</span
                            >
                            <span
                                data-segment-id="w16"
                                class="flex-1 font-semibold text-gray-800"
                                v-html="getHighlightedSegment('Sticks and stones', 'w16')"
                            ></span>
                            <select
                                v-model="answers.q16"
                                class="w-20 cursor-pointer rounded-xl border-2 border-violet-300 bg-white px-3 py-2.5 text-center font-bold text-violet-700 transition-all focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none"
                            >
                                <option value="">...</option>
                                <option v-for="opt in matchingOptions" :key="opt.letter" :value="opt.letter">{{ opt.letter }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions 17-18: Choose TWO -->
            <div class="animate-slideUp mb-8 border-t-2 border-dashed border-violet-200 pt-8" style="animation-delay: 0.2s">
                <div class="mb-4">
                    <div class="mb-2 inline-block rounded-lg bg-purple-100 px-3 py-1">
                        <span class="text-sm font-semibold text-purple-700">Questions 17 and 18</span>
                    </div>
                    <p class="mb-2 text-gray-700">
                        <span
                            data-segment-id="instruction17"
                            v-html="
                                getHighlightedSegment('Which TWO reasons does the speaker give for recommending Alive and Kicking?', 'instruction17')
                            "
                        ></span>
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-100 to-violet-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="instruction17b"
                            class="text-sm font-semibold text-purple-800"
                            v-html="getHighlightedSegment('Choose TWO letters, A-E.', 'instruction17b')"
                        ></span>
                    </div>
                </div>

                <div class="rounded-2xl border border-purple-100 bg-white/80 p-6 shadow-lg">
                    <div data-question="17" class="space-y-3">
                        <div
                            @click="toggleMultipleAnswer('q17_18', 'A')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q17_18', 'A')
                                    ? 'border-2 border-purple-400 bg-gradient-to-r from-purple-100 to-violet-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-purple-50 hover:to-violet-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q17_18', 'A')
                                        ? 'scale-110 bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">A</span>
                            </div>
                            <span
                                data-segment-id="ak_A"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q17_18', 'A') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('It will appeal to both boys and girls.', 'ak_A')"
                            ></span>
                            <div
                                v-if="isSelected('q17_18', 'A')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            @click="toggleMultipleAnswer('q17_18', 'B')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q17_18', 'B')
                                    ? 'border-2 border-purple-400 bg-gradient-to-r from-purple-100 to-violet-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-purple-50 hover:to-violet-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q17_18', 'B')
                                        ? 'scale-110 bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">B</span>
                            </div>
                            <span
                                data-segment-id="ak_B"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q17_18', 'B') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('The author is well known.', 'ak_B')"
                            ></span>
                            <div
                                v-if="isSelected('q17_18', 'B')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            @click="toggleMultipleAnswer('q17_18', 'C')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q17_18', 'C')
                                    ? 'border-2 border-purple-400 bg-gradient-to-r from-purple-100 to-violet-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-purple-50 hover:to-violet-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q17_18', 'C')
                                        ? 'scale-110 bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">C</span>
                            </div>
                            <span
                                data-segment-id="ak_C"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q17_18', 'C') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('It has colourful illustrations.', 'ak_C')"
                            ></span>
                            <div
                                v-if="isSelected('q17_18', 'C')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            @click="toggleMultipleAnswer('q17_18', 'D')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q17_18', 'D')
                                    ? 'border-2 border-purple-400 bg-gradient-to-r from-purple-100 to-violet-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-purple-50 hover:to-violet-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q17_18', 'D')
                                        ? 'scale-110 bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">D</span>
                            </div>
                            <span
                                data-segment-id="ak_D"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q17_18', 'D') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('It is funny.', 'ak_D')"
                            ></span>
                            <div
                                v-if="isSelected('q17_18', 'D')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            @click="toggleMultipleAnswer('q17_18', 'E')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q17_18', 'E')
                                    ? 'border-2 border-purple-400 bg-gradient-to-r from-purple-100 to-violet-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-purple-50 hover:to-violet-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q17_18', 'E')
                                        ? 'scale-110 bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">E</span>
                            </div>
                            <span
                                data-segment-id="ak_E"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q17_18', 'E') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('It deals with an important topic.', 'ak_E')"
                            ></span>
                            <div
                                v-if="isSelected('q17_18', 'E')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-center text-sm text-gray-500">
                        Selected: <span class="font-bold text-purple-600">{{ multipleAnswers.q17_18.length }}/2</span>
                    </p>
                </div>
            </div>

            <!-- Questions 19-20: Choose TWO -->
            <div class="animate-slideUp border-t-2 border-dashed border-violet-200 pt-8" style="animation-delay: 0.3s">
                <div class="mb-4">
                    <div class="mb-2 inline-block rounded-lg bg-violet-100 px-3 py-1">
                        <span class="text-sm font-semibold text-violet-700">Questions 19 and 20</span>
                    </div>
                    <p class="mb-2 text-gray-700">
                        <span
                            data-segment-id="instruction19"
                            v-html="getHighlightedSegment('Which TWO pieces of advice does the speaker give about reading?', 'instruction19')"
                        ></span>
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-violet-100 to-purple-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="instruction19b"
                            class="text-sm font-semibold text-violet-800"
                            v-html="getHighlightedSegment('Choose TWO letters, A-E.', 'instruction19b')"
                        ></span>
                    </div>
                </div>

                <div class="rounded-2xl border border-violet-100 bg-white/80 p-6 shadow-lg">
                    <div data-question="19" class="space-y-3">
                        <div
                            @click="toggleMultipleAnswer('q19_20', 'A')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q19_20', 'A')
                                    ? 'border-2 border-violet-400 bg-gradient-to-r from-violet-100 to-purple-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-violet-50 hover:to-purple-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q19_20', 'A')
                                        ? 'scale-110 bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">A</span>
                            </div>
                            <span
                                data-segment-id="ra_A"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q19_20', 'A') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('Encourage children to write down new vocabulary.', 'ra_A')"
                            ></span>
                            <div
                                v-if="isSelected('q19_20', 'A')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            @click="toggleMultipleAnswer('q19_20', 'B')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q19_20', 'B')
                                    ? 'border-2 border-violet-400 bg-gradient-to-r from-violet-100 to-purple-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-violet-50 hover:to-purple-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q19_20', 'B')
                                        ? 'scale-110 bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">B</span>
                            </div>
                            <span
                                data-segment-id="ra_B"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q19_20', 'B') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('Allow children to listen to audio books.', 'ra_B')"
                            ></span>
                            <div
                                v-if="isSelected('q19_20', 'B')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            @click="toggleMultipleAnswer('q19_20', 'C')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q19_20', 'C')
                                    ? 'border-2 border-violet-400 bg-gradient-to-r from-violet-100 to-purple-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-violet-50 hover:to-purple-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q19_20', 'C')
                                        ? 'scale-110 bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">C</span>
                            </div>
                            <span
                                data-segment-id="ra_C"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q19_20', 'C') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('Get recommendations from librarians.', 'ra_C')"
                            ></span>
                            <div
                                v-if="isSelected('q19_20', 'C')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            @click="toggleMultipleAnswer('q19_20', 'D')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q19_20', 'D')
                                    ? 'border-2 border-violet-400 bg-gradient-to-r from-violet-100 to-purple-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-violet-50 hover:to-purple-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q19_20', 'D')
                                        ? 'scale-110 bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">D</span>
                            </div>
                            <span
                                data-segment-id="ra_D"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q19_20', 'D') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('Give children a choice about what they read.', 'ra_D')"
                            ></span>
                            <div
                                v-if="isSelected('q19_20', 'D')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            @click="toggleMultipleAnswer('q19_20', 'E')"
                            class="flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all duration-300"
                            :class="
                                isSelected('q19_20', 'E')
                                    ? 'border-2 border-violet-400 bg-gradient-to-r from-violet-100 to-purple-100 shadow-md'
                                    : 'border-2 border-transparent bg-gradient-to-r from-gray-50 to-gray-100 hover:from-violet-50 hover:to-purple-50'
                            "
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300"
                                :class="
                                    isSelected('q19_20', 'E')
                                        ? 'scale-110 bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                <span class="font-bold">E</span>
                            </div>
                            <span
                                data-segment-id="ra_E"
                                class="flex-1 text-gray-800"
                                :class="isSelected('q19_20', 'E') ? 'font-semibold' : ''"
                                v-html="getHighlightedSegment('Only read aloud to children until they can read independently.', 'ra_E')"
                            ></span>
                            <div
                                v-if="isSelected('q19_20', 'E')"
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-center text-sm text-gray-500">
                        Selected: <span class="font-bold text-violet-600">{{ multipleAnswers.q19_20.length }}/2</span>
                    </p>
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
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-violet-200 bg-violet-50 transition-all duration-200 hover:scale-110 hover:bg-violet-100 hover:shadow-md"
                                title="Add Note"
                            >
                                <svg class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-violet-300 bg-white p-4 shadow-2xl"
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
                            class="note-input-field w-full rounded-lg border-2 border-violet-200 px-4 py-3 text-sm transition-all focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none"
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
                            class="rounded-lg bg-gradient-to-r from-violet-600 to-purple-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-violet-500/20 transition-all hover:from-violet-700 hover:to-purple-700"
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
