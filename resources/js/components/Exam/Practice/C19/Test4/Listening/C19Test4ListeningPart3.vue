<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Answers State
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

// Highlighting & Notes
const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();
const contentTextRef = ref<HTMLElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Text segments
const textSegments = ref([
    { id: 'header', text: 'PART 3', offset: 0 },
    { id: 'questions', text: 'Questions 21 - 30', offset: 7 },
    { id: 'q21title', text: 'Questions 21-25', offset: 25 },
    { id: 'q21instruction', text: 'Choose the correct letter, A, B or C.', offset: 41 },
    { id: 'q21question', text: "Kieran thinks the packing advice given by Jane's grandfather is", offset: 79 },
    { id: 'q21a', text: 'common sense.', offset: 143 },
    { id: 'q21b', text: 'hard to follow.', offset: 157 },
    { id: 'q21c', text: 'over-protective.', offset: 173 },
    { id: 'q22question', text: 'How does Jane feel about the books her grandfather has given her?', offset: 190 },
    { id: 'q22a', text: 'They are not worth keeping.', offset: 256 },
    { id: 'q22b', text: 'They should go to a collector.', offset: 284 },
    { id: 'q22c', text: 'They have sentimental value for her.', offset: 315 },
    { id: 'q23question', text: 'Jane and Kieran agree that hardback books should be', offset: 352 },
    { id: 'q23a', text: 'put out on display.', offset: 404 },
    { id: 'q23b', text: 'given as gifts to visitors.', offset: 424 },
    { id: 'q23c', text: 'more attractively designed.', offset: 452 },
    { id: 'q24question', text: 'While talking about taking a book from a shelf, Jane', offset: 480 },
    { id: 'q24a', text: 'describes the mistakes other people make doing it.', offset: 533 },
    { id: 'q24b', text: 'reflects on a significant childhood experience.', offset: 584 },
    { id: 'q24c', text: 'explains why some books are easier to remove than others.', offset: 632 },
    { id: 'q25question', text: 'What do Jane and Kieran suggest about new books?', offset: 690 },
    { id: 'q25a', text: 'Their parents liked buying them as presents.', offset: 739 },
    { id: 'q25b', text: 'They would like to buy more of them.', offset: 784 },
    { id: 'q25c', text: 'Not everyone can afford them.', offset: 821 },
    { id: 'q26title', text: 'Questions 26-30', offset: 851 },
    { id: 'q26question', text: "Where does Jane's grandfather keep each of the following types of books in his shop?", offset: 867 },
    { id: 'q26instruction', text: 'Choose FIVE answers from the box and write the correct letter, A-G, next to Questions 26-30.', offset: 952 },
    { id: 'locations', text: 'Location of books', offset: 1045 },
    { id: 'locA', text: 'near the entrance', offset: 1063 },
    { id: 'locB', text: 'in the attic', offset: 1081 },
    { id: 'locC', text: 'at the back of the shop', offset: 1094 },
    { id: 'locD', text: 'on a high shelf', offset: 1118 },
    { id: 'locE', text: 'near the stairs', offset: 1134 },
    { id: 'locF', text: 'in a specially designed space', offset: 1150 },
    { id: 'locG', text: 'within the café', offset: 1180 },
    { id: 'types', text: 'Types of books', offset: 1196 },
    { id: 'rare', text: 'rare books', offset: 1211 },
    { id: 'children', text: "children's books", offset: 1222 },
    { id: 'unwanted', text: 'unwanted books', offset: 1239 },
    { id: 'requested', text: 'requested books', offset: 1254 },
    { id: 'coursebooks', text: 'coursebooks', offset: 1270 },
]);

const getHighlightedSegment = (segmentText: string, segmentId?: string) => {
    const segment = segmentId ? textSegments.value.find((s) => s.id === segmentId) : textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;
    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
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
        if (startAbsOffset > endAbsOffset) [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];
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
    const el = document.querySelector(`[data-question="${questionNumber}"]`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('ring-4', 'ring-violet-400', 'ring-offset-2');
        setTimeout(() => el.classList.remove('ring-4', 'ring-violet-400', 'ring-offset-2'), 2000);
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
    <div class="mx-auto mb-16 px-4 py-8">
        <div
            ref="contentTextRef"
            class="animate-fadeIn w-full rounded-2xl border border-violet-100/50 bg-gradient-to-br from-white via-violet-50/30 to-purple-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-violet-200/50 bg-gradient-to-r from-violet-50 to-purple-50 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="animate-pulse-slow flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 shadow-lg shadow-violet-500/30"
                    >
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <h2
                            class="bg-gradient-to-r from-violet-600 to-purple-600 bg-clip-text text-xl font-bold tracking-widest text-transparent uppercase"
                        >
                            <span data-segment-id="header" v-html="getHighlightedSegment('PART 3', 'header')"></span>
                        </h2>
                        <h1 class="text-base font-semibold text-gray-700">
                            <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 21 - 30', 'questions')"></span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Questions 21-25: Multiple Choice -->
            <div class="animate-slideUp mb-8" style="animation-delay: 0.1s">
                <div class="rounded-2xl border border-violet-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <h3 class="mb-2 text-lg font-bold text-violet-800">
                        <span data-segment-id="q21title" v-html="getHighlightedSegment('Questions 21-25', 'q21title')"></span>
                    </h3>
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-violet-100 to-purple-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="q21instruction"
                            class="text-sm font-semibold text-violet-800"
                            v-html="getHighlightedSegment('Choose the correct letter, A, B or C.', 'q21instruction')"
                        ></span>
                    </div>

                    <div class="space-y-6">
                        <!-- Question 21 -->
                        <div
                            data-question="21"
                            class="rounded-xl border border-violet-100 bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-5 transition-all duration-300 hover:shadow-md"
                        >
                            <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                                <span
                                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg"
                                    >21</span
                                >
                                <span
                                    data-segment-id="q21question"
                                    v-html="getHighlightedSegment('Kieran thinks the packing advice given by Jane\'s grandfather is', 'q21question')"
                                ></span>
                            </p>
                            <div class="ml-11 space-y-2">
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q21" value="A" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">A</span>
                                    <span data-segment-id="q21a" class="text-gray-700" v-html="getHighlightedSegment('common sense.', 'q21a')"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q21" value="B" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">B</span>
                                    <span
                                        data-segment-id="q21b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('hard to follow.', 'q21b')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q21" value="C" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">C</span>
                                    <span
                                        data-segment-id="q21c"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('over-protective.', 'q21c')"
                                    ></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 22 -->
                        <div
                            data-question="22"
                            class="rounded-xl border border-violet-100 bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-5 transition-all duration-300 hover:shadow-md"
                        >
                            <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                                <span
                                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg"
                                    >22</span
                                >
                                <span
                                    data-segment-id="q22question"
                                    v-html="getHighlightedSegment('How does Jane feel about the books her grandfather has given her?', 'q22question')"
                                ></span>
                            </p>
                            <div class="ml-11 space-y-2">
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q22" value="A" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">A</span>
                                    <span
                                        data-segment-id="q22a"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('They are not worth keeping.', 'q22a')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q22" value="B" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">B</span>
                                    <span
                                        data-segment-id="q22b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('They should go to a collector.', 'q22b')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q22" value="C" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">C</span>
                                    <span
                                        data-segment-id="q22c"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('They have sentimental value for her.', 'q22c')"
                                    ></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 23 -->
                        <div
                            data-question="23"
                            class="rounded-xl border border-violet-100 bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-5 transition-all duration-300 hover:shadow-md"
                        >
                            <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                                <span
                                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg"
                                    >23</span
                                >
                                <span
                                    data-segment-id="q23question"
                                    v-html="getHighlightedSegment('Jane and Kieran agree that hardback books should be', 'q23question')"
                                ></span>
                            </p>
                            <div class="ml-11 space-y-2">
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q23" value="A" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">A</span>
                                    <span
                                        data-segment-id="q23a"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('put out on display.', 'q23a')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q23" value="B" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">B</span>
                                    <span
                                        data-segment-id="q23b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('given as gifts to visitors.', 'q23b')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q23" value="C" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">C</span>
                                    <span
                                        data-segment-id="q23c"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('more attractively designed.', 'q23c')"
                                    ></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 24 -->
                        <div
                            data-question="24"
                            class="rounded-xl border border-violet-100 bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-5 transition-all duration-300 hover:shadow-md"
                        >
                            <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                                <span
                                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg"
                                    >24</span
                                >
                                <span
                                    data-segment-id="q24question"
                                    v-html="getHighlightedSegment('While talking about taking a book from a shelf, Jane', 'q24question')"
                                ></span>
                            </p>
                            <div class="ml-11 space-y-2">
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q24" value="A" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">A</span>
                                    <span
                                        data-segment-id="q24a"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('describes the mistakes other people make doing it.', 'q24a')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q24" value="B" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">B</span>
                                    <span
                                        data-segment-id="q24b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('reflects on a significant childhood experience.', 'q24b')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q24" value="C" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">C</span>
                                    <span
                                        data-segment-id="q24c"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('explains why some books are easier to remove than others.', 'q24c')"
                                    ></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 25 -->
                        <div
                            data-question="25"
                            class="rounded-xl border border-violet-100 bg-gradient-to-r from-violet-50/80 to-purple-50/80 p-5 transition-all duration-300 hover:shadow-md"
                        >
                            <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                                <span
                                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg"
                                    >25</span
                                >
                                <span
                                    data-segment-id="q25question"
                                    v-html="getHighlightedSegment('What do Jane and Kieran suggest about new books?', 'q25question')"
                                ></span>
                            </p>
                            <div class="ml-11 space-y-2">
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q25" value="A" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">A</span>
                                    <span
                                        data-segment-id="q25a"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('Their parents liked buying them as presents.', 'q25a')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q25" value="B" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">B</span>
                                    <span
                                        data-segment-id="q25b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('They would like to buy more of them.', 'q25b')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-violet-100/50"
                                >
                                    <input type="radio" v-model="answers.q25" value="C" class="h-5 w-5 text-violet-600" />
                                    <span class="w-6 font-bold text-violet-700">C</span>
                                    <span
                                        data-segment-id="q25c"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('Not everyone can afford them.', 'q25c')"
                                    ></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions 26-30: Matching -->
            <div class="animate-slideUp border-t-2 border-dashed border-violet-200 pt-8" style="animation-delay: 0.2s">
                <div class="rounded-2xl border border-violet-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <h3 class="mb-2 text-lg font-bold text-violet-800">
                        <span data-segment-id="q26title" v-html="getHighlightedSegment('Questions 26-30', 'q26title')"></span>
                    </h3>
                    <p class="mb-3 text-gray-700">
                        <span
                            data-segment-id="q26question"
                            v-html="
                                getHighlightedSegment(
                                    'Where does Jane\'s grandfather keep each of the following types of books in his shop?',
                                    'q26question',
                                )
                            "
                        ></span>
                    </p>
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-100 to-indigo-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="q26instruction"
                            class="text-sm font-semibold text-purple-800"
                            v-html="
                                getHighlightedSegment(
                                    'Choose FIVE answers from the box and write the correct letter, A-G, next to Questions 26-30.',
                                    'q26instruction',
                                )
                            "
                        ></span>
                    </div>

                    <!-- Locations Box -->
                    <div class="mb-6 rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-indigo-50 p-4">
                        <h4 class="mb-3 text-center font-bold text-purple-800">
                            <span data-segment-id="locations" v-html="getHighlightedSegment('Location of books', 'locations')"></span>
                        </h4>
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-purple-700">A</span>
                                <span data-segment-id="locA" class="text-gray-700" v-html="getHighlightedSegment('near the entrance', 'locA')"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-purple-700">B</span>
                                <span data-segment-id="locB" class="text-gray-700" v-html="getHighlightedSegment('in the attic', 'locB')"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-purple-700">C</span>
                                <span
                                    data-segment-id="locC"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('at the back of the shop', 'locC')"
                                ></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-purple-700">D</span>
                                <span data-segment-id="locD" class="text-gray-700" v-html="getHighlightedSegment('on a high shelf', 'locD')"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-purple-700">E</span>
                                <span data-segment-id="locE" class="text-gray-700" v-html="getHighlightedSegment('near the stairs', 'locE')"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-purple-700">F</span>
                                <span
                                    data-segment-id="locF"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('in a specially designed space', 'locF')"
                                ></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-purple-700">G</span>
                                <span data-segment-id="locG" class="text-gray-700" v-html="getHighlightedSegment('within the café', 'locG')"></span>
                            </div>
                        </div>
                    </div>

                    <h4 class="mb-4 font-bold text-gray-800">
                        <span data-segment-id="types" v-html="getHighlightedSegment('Types of books', 'types')"></span>
                    </h4>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div
                            data-question="26"
                            class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-indigo-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >26</span
                            >
                            <span
                                data-segment-id="rare"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('rare books', 'rare')"
                            ></span>
                            <select
                                v-model="answers.q26"
                                class="h-11 w-20 cursor-pointer rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                            >
                                <option value="" disabled>?</option>
                                <option v-for="letter in ['A', 'B', 'C', 'D', 'E', 'F', 'G']" :key="letter" :value="letter">{{ letter }}</option>
                            </select>
                        </div>

                        <div
                            data-question="27"
                            class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-indigo-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >27</span
                            >
                            <span
                                data-segment-id="children"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('children\'s books', 'children')"
                            ></span>
                            <select
                                v-model="answers.q27"
                                class="h-11 w-20 cursor-pointer rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                            >
                                <option value="" disabled>?</option>
                                <option v-for="letter in ['A', 'B', 'C', 'D', 'E', 'F', 'G']" :key="letter" :value="letter">{{ letter }}</option>
                            </select>
                        </div>

                        <div
                            data-question="28"
                            class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-indigo-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >28</span
                            >
                            <span
                                data-segment-id="unwanted"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('unwanted books', 'unwanted')"
                            ></span>
                            <select
                                v-model="answers.q28"
                                class="h-11 w-20 cursor-pointer rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                            >
                                <option value="" disabled>?</option>
                                <option v-for="letter in ['A', 'B', 'C', 'D', 'E', 'F', 'G']" :key="letter" :value="letter">{{ letter }}</option>
                            </select>
                        </div>

                        <div
                            data-question="29"
                            class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-indigo-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >29</span
                            >
                            <span
                                data-segment-id="requested"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('requested books', 'requested')"
                            ></span>
                            <select
                                v-model="answers.q29"
                                class="h-11 w-20 cursor-pointer rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                            >
                                <option value="" disabled>?</option>
                                <option v-for="letter in ['A', 'B', 'C', 'D', 'E', 'F', 'G']" :key="letter" :value="letter">{{ letter }}</option>
                            </select>
                        </div>

                        <div
                            data-question="30"
                            class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-indigo-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md md:col-span-2 md:max-w-lg"
                        >
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >30</span
                            >
                            <span
                                data-segment-id="coursebooks"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('coursebooks', 'coursebooks')"
                            ></span>
                            <select
                                v-model="answers.q30"
                                class="h-11 w-20 cursor-pointer rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                            >
                                <option value="" disabled>?</option>
                                <option v-for="letter in ['A', 'B', 'C', 'D', 'E', 'F', 'G']" :key="letter" :value="letter">{{ letter }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu & Note Input -->
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

input[type='radio'] {
    appearance: none;
    -webkit-appearance: none;
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
}
input[type='radio']:checked {
    border-color: #8b5cf6;
    background: #8b5cf6;
    box-shadow: inset 0 0 0 3px white;
}
input[type='radio']:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
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

@keyframes pulse-slow {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

.animate-fadeIn {
    animation: fadeIn 0.5s ease-out;
}

.animate-slideUp {
    animation: slideUp 0.5s ease-out both;
}

.animate-pulse-slow {
    animation: pulse-slow 3s ease-in-out infinite;
}
</style>
