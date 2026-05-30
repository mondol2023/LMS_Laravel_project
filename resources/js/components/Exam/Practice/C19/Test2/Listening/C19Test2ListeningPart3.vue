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
    { id: 'questions1', text: 'Questions 21 - 24', offset: 7 },
    { id: 'instruction1', text: 'Choose the correct letter, A, B or C.', offset: 25 },
    { id: 'q21', text: 'At first, Don thought the topic of recycling footwear might be too', offset: 64 },
    { id: 'q21a', text: 'limited in scope.', offset: 131 },
    { id: 'q21b', text: 'hard to research.', offset: 149 },
    { id: 'q21c', text: 'boring for listeners.', offset: 167 },
    { id: 'q22', text: 'When discussing trainers, Bella and Don disagree about', offset: 189 },
    { id: 'q22a', text: 'how popular they are among young people.', offset: 244 },
    { id: 'q22b', text: 'how suitable they are for school.', offset: 285 },
    { id: 'q22c', text: 'how quickly they wear out.', offset: 319 },
    { id: 'q23', text: 'Bella says that she sometimes recycles shoes because', offset: 346 },
    { id: 'q23a', text: 'they no longer fit.', offset: 399 },
    { id: 'q23b', text: 'she no longer likes them.', offset: 419 },
    { id: 'q23c', text: 'they are no longer in fashion.', offset: 445 },
    { id: 'q24', text: 'What did the article say that confused Don?', offset: 476 },
    { id: 'q24a', text: 'Public consumption of footwear has risen.', offset: 520 },
    { id: 'q24b', text: 'Less footwear is recycled now than in the past.', offset: 562 },
    { id: 'q24c', text: 'People dispose of more footwear than they used to.', offset: 610 },
    { id: 'questions2', text: 'Questions 25 - 28', offset: 661 },
    { id: 'instruction2', text: 'What reasons did the recycling manager give for rejecting footwear, according to the students?', offset: 679 },
    { id: 'instruction3', text: 'Choose FOUR answers from the box and write the correct letter, A-F, next to Questions 25-28.', offset: 774 },
    { id: 'reasonsTitle', text: 'Reasons', offset: 867 },
    { id: 'reasonA', text: 'one shoe was missing', offset: 875 },
    { id: 'reasonB', text: 'the colour of one shoe had faded', offset: 896 },
    { id: 'reasonC', text: 'one shoe had a hole in it', offset: 929 },
    { id: 'reasonD', text: 'the shoes were brand new', offset: 955 },
    { id: 'reasonE', text: 'the shoes were too dirty', offset: 980 },
    { id: 'reasonF', text: 'the stitching on the shoes was broken', offset: 1005 },
    { id: 'footwearTitle', text: 'Footwear', offset: 1043 },
    { id: 'footwear25', text: 'the high-heeled shoes', offset: 1052 },
    { id: 'footwear26', text: 'the ankle boots', offset: 1074 },
    { id: 'footwear27', text: 'the baby shoes', offset: 1090 },
    { id: 'footwear28', text: 'the trainers', offset: 1105 },
    { id: 'questions3', text: 'Questions 29 - 30', offset: 1118 },
    { id: 'q29', text: "Why did the project to make 'new' shoes out of old shoes fail?", offset: 1136 },
    { id: 'q29a', text: "People believed the 'new' pairs of shoes were unhygienic.", offset: 1199 },
    { id: 'q29b', text: 'There were not enough good parts to use in the old shoes.', offset: 1257 },
    { id: 'q29c', text: "The shoes in the 'new' pairs were not completely alike.", offset: 1315 },
    { id: 'q30', text: 'Bella and Don agree that they can present their topic', offset: 1371 },
    { id: 'q30a', text: 'from a new angle.', offset: 1425 },
    { id: 'q30b', text: 'with relevant images.', offset: 1443 },
    { id: 'q30c', text: 'in a straightforward way.', offset: 1465 },
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
            result = `${before}<mark class="highlight-${highlight.color}" data-highlight-id="${highlight.id}">${highlighted}</mark>${after}`;
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
        const rect = range.getBoundingClientRect();
        contextMenuPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 10 };
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
        el.classList.add('ring-4', 'ring-green-400', 'ring-offset-2');
        setTimeout(() => el.classList.remove('ring-4', 'ring-green-400', 'ring-offset-2'), 2000);
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
    <div class="animate-fadeIn mx-auto mb-16 px-4 py-8">
        <div
            ref="contentTextRef"
            class="w-full rounded-2xl border border-green-100/50 bg-gradient-to-br from-white via-green-50/30 to-emerald-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-green-200/50 bg-gradient-to-r from-green-50 to-emerald-50 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 animate-pulse items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 shadow-lg shadow-green-500/30"
                    >
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold tracking-widest text-green-700 uppercase">
                            <span data-segment-id="header" v-html="getHighlightedSegment('PART 3', 'header')"></span>
                        </h2>
                        <h1 class="text-base font-semibold text-gray-700">
                            <span data-segment-id="questions1" v-html="getHighlightedSegment('Questions 21 - 24', 'questions1')"></span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Section 1: Multiple Choice 21-24 -->
            <div class="mb-8">
                <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-green-100 to-emerald-100 px-4 py-2 shadow-sm">
                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                    <span
                        data-segment-id="instruction1"
                        class="text-sm font-semibold text-green-800"
                        v-html="getHighlightedSegment('Choose the correct letter, A, B or C.', 'instruction1')"
                    ></span>
                </div>

                <div class="space-y-5">
                    <!-- Question 21 -->
                    <div
                        data-question="21"
                        class="group animate-slideUp rounded-xl border border-green-100/50 bg-gradient-to-r from-green-50/80 to-emerald-50/80 p-5 shadow-sm transition-all duration-300 hover:border-green-200 hover:shadow-md"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-sm font-bold text-white shadow-lg"
                                >21</span
                            >
                            <span
                                data-segment-id="q21"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('At first, Don thought the topic of recycling footwear might be too', 'q21')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q21" value="A" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">A</span>
                                <span data-segment-id="q21a" class="text-gray-700" v-html="getHighlightedSegment('limited in scope.', 'q21a')"></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q21" value="B" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">B</span>
                                <span data-segment-id="q21b" class="text-gray-700" v-html="getHighlightedSegment('hard to research.', 'q21b')"></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q21" value="C" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">C</span>
                                <span
                                    data-segment-id="q21c"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('boring for listeners.', 'q21c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 22 -->
                    <div
                        data-question="22"
                        class="group animate-slideUp rounded-xl border border-green-100/50 bg-gradient-to-r from-green-50/80 to-emerald-50/80 p-5 shadow-sm transition-all duration-300 hover:border-green-200 hover:shadow-md"
                        style="animation-delay: 0.05s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-sm font-bold text-white shadow-lg"
                                >22</span
                            >
                            <span
                                data-segment-id="q22"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('When discussing trainers, Bella and Don disagree about', 'q22')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q22" value="A" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">A</span>
                                <span
                                    data-segment-id="q22a"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('how popular they are among young people.', 'q22a')"
                                ></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q22" value="B" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">B</span>
                                <span
                                    data-segment-id="q22b"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('how suitable they are for school.', 'q22b')"
                                ></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q22" value="C" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">C</span>
                                <span
                                    data-segment-id="q22c"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('how quickly they wear out.', 'q22c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 23 -->
                    <div
                        data-question="23"
                        class="group animate-slideUp rounded-xl border border-green-100/50 bg-gradient-to-r from-green-50/80 to-emerald-50/80 p-5 shadow-sm transition-all duration-300 hover:border-green-200 hover:shadow-md"
                        style="animation-delay: 0.1s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-sm font-bold text-white shadow-lg"
                                >23</span
                            >
                            <span
                                data-segment-id="q23"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('Bella says that she sometimes recycles shoes because', 'q23')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q23" value="A" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">A</span>
                                <span
                                    data-segment-id="q23a"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('they no longer fit.', 'q23a')"
                                ></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q23" value="B" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">B</span>
                                <span
                                    data-segment-id="q23b"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('she no longer likes them.', 'q23b')"
                                ></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q23" value="C" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">C</span>
                                <span
                                    data-segment-id="q23c"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('they are no longer in fashion.', 'q23c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 24 -->
                    <div
                        data-question="24"
                        class="group animate-slideUp rounded-xl border border-green-100/50 bg-gradient-to-r from-green-50/80 to-emerald-50/80 p-5 shadow-sm transition-all duration-300 hover:border-green-200 hover:shadow-md"
                        style="animation-delay: 0.15s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-sm font-bold text-white shadow-lg"
                                >24</span
                            >
                            <span
                                data-segment-id="q24"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('What did the article say that confused Don?', 'q24')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q24" value="A" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">A</span>
                                <span
                                    data-segment-id="q24a"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('Public consumption of footwear has risen.', 'q24a')"
                                ></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q24" value="B" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">B</span>
                                <span
                                    data-segment-id="q24b"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('Less footwear is recycled now than in the past.', 'q24b')"
                                ></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-green-100/50">
                                <input type="radio" v-model="answers.q24" value="C" class="h-5 w-5 text-green-600 focus:ring-green-500" />
                                <span class="w-6 font-bold text-green-700">C</span>
                                <span
                                    data-segment-id="q24c"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('People dispose of more footwear than they used to.', 'q24c')"
                                ></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Matching 25-28 -->
            <div class="mb-8 border-t-2 border-dashed border-emerald-200 pt-8">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-16 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 shadow-lg">
                        <span class="text-sm font-bold text-white">25-28</span>
                    </div>
                    <h3 class="bg-gradient-to-r from-emerald-700 to-teal-600 bg-clip-text text-lg font-bold text-transparent">
                        <span data-segment-id="questions2" v-html="getHighlightedSegment('Questions 25 - 28', 'questions2')"></span>
                    </h3>
                </div>

                <div
                    data-question="25"
                    class="animate-slideUp rounded-2xl border-2 border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-5 shadow-lg"
                    style="animation-delay: 0.2s"
                >
                    <p class="mb-3 text-gray-700">
                        <span
                            data-segment-id="instruction2"
                            v-html="
                                getHighlightedSegment(
                                    'What reasons did the recycling manager give for rejecting footwear, according to the students?',
                                    'instruction2',
                                )
                            "
                        ></span>
                    </p>
                    <p class="mb-5 font-semibold text-emerald-800">
                        <span
                            data-segment-id="instruction3"
                            v-html="
                                getHighlightedSegment(
                                    'Choose FOUR answers from the box and write the correct letter, A-F, next to Questions 25-28.',
                                    'instruction3',
                                )
                            "
                        ></span>
                    </p>

                    <!-- Reasons Box -->
                    <div
                        class="mb-6 rounded-2xl border-2 border-emerald-200 bg-gradient-to-br from-emerald-100 via-teal-50 to-green-100 p-5 shadow-inner"
                    >
                        <h4 class="mb-4 text-center text-lg font-bold text-emerald-900">
                            <span data-segment-id="reasonsTitle" v-html="getHighlightedSegment('Reasons', 'reasonsTitle')"></span>
                        </h4>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="flex items-start gap-2 rounded-xl border border-emerald-100 bg-white/80 p-3 shadow-sm">
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600"
                                    >A</span
                                >
                                <span
                                    data-segment-id="reasonA"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('one shoe was missing', 'reasonA')"
                                ></span>
                            </div>
                            <div class="flex items-start gap-2 rounded-xl border border-emerald-100 bg-white/80 p-3 shadow-sm">
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600"
                                    >B</span
                                >
                                <span
                                    data-segment-id="reasonB"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('the colour of one shoe had faded', 'reasonB')"
                                ></span>
                            </div>
                            <div class="flex items-start gap-2 rounded-xl border border-emerald-100 bg-white/80 p-3 shadow-sm">
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600"
                                    >C</span
                                >
                                <span
                                    data-segment-id="reasonC"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('one shoe had a hole in it', 'reasonC')"
                                ></span>
                            </div>
                            <div class="flex items-start gap-2 rounded-xl border border-emerald-100 bg-white/80 p-3 shadow-sm">
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600"
                                    >D</span
                                >
                                <span
                                    data-segment-id="reasonD"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('the shoes were brand new', 'reasonD')"
                                ></span>
                            </div>
                            <div class="flex items-start gap-2 rounded-xl border border-emerald-100 bg-white/80 p-3 shadow-sm">
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600"
                                    >E</span
                                >
                                <span
                                    data-segment-id="reasonE"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('the shoes were too dirty', 'reasonE')"
                                ></span>
                            </div>
                            <div class="flex items-start gap-2 rounded-xl border border-emerald-100 bg-white/80 p-3 shadow-sm">
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600"
                                    >F</span
                                >
                                <span
                                    data-segment-id="reasonF"
                                    class="text-sm text-gray-700"
                                    v-html="getHighlightedSegment('the stitching on the shoes was broken', 'reasonF')"
                                ></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footwear -->
                    <h4 class="mb-4 text-lg font-bold text-teal-900">
                        <span data-segment-id="footwearTitle" v-html="getHighlightedSegment('Footwear', 'footwearTitle')"></span>
                    </h4>
                    <div class="space-y-4">
                        <div
                            class="group flex items-center gap-4 rounded-xl border-2 border-teal-100 bg-gradient-to-r from-teal-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-teal-300 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                >25</span
                            >
                            <span
                                data-segment-id="footwear25"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('the high-heeled shoes', 'footwear25')"
                            ></span>
                            <input
                                v-model="answers.q25"
                                type="text"
                                maxlength="1"
                                class="h-12 w-14 rounded-xl border-2 border-teal-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                                placeholder="?"
                            />
                        </div>
                        <div
                            class="group flex items-center gap-4 rounded-xl border-2 border-teal-100 bg-gradient-to-r from-teal-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-teal-300 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                >26</span
                            >
                            <span
                                data-segment-id="footwear26"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('the ankle boots', 'footwear26')"
                            ></span>
                            <input
                                v-model="answers.q26"
                                type="text"
                                maxlength="1"
                                class="h-12 w-14 rounded-xl border-2 border-teal-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                                placeholder="?"
                            />
                        </div>
                        <div
                            class="group flex items-center gap-4 rounded-xl border-2 border-teal-100 bg-gradient-to-r from-teal-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-teal-300 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                >27</span
                            >
                            <span
                                data-segment-id="footwear27"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('the baby shoes', 'footwear27')"
                            ></span>
                            <input
                                v-model="answers.q27"
                                type="text"
                                maxlength="1"
                                class="h-12 w-14 rounded-xl border-2 border-teal-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                                placeholder="?"
                            />
                        </div>
                        <div
                            class="group flex items-center gap-4 rounded-xl border-2 border-teal-100 bg-gradient-to-r from-teal-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-teal-300 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                >28</span
                            >
                            <span
                                data-segment-id="footwear28"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('the trainers', 'footwear28')"
                            ></span>
                            <input
                                v-model="answers.q28"
                                type="text"
                                maxlength="1"
                                class="h-12 w-14 rounded-xl border-2 border-teal-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                                placeholder="?"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Questions 29-30 -->
            <div class="border-t-2 border-dashed border-teal-200 pt-8">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-16 items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 shadow-lg">
                        <span class="text-sm font-bold text-white">29-30</span>
                    </div>
                    <h3 class="bg-gradient-to-r from-teal-700 to-cyan-600 bg-clip-text text-lg font-bold text-transparent">
                        <span data-segment-id="questions3" v-html="getHighlightedSegment('Questions 29 - 30', 'questions3')"></span>
                    </h3>
                </div>

                <div class="space-y-5">
                    <!-- Question 29 -->
                    <div
                        data-question="29"
                        class="group animate-slideUp rounded-xl border border-teal-100/50 bg-gradient-to-r from-teal-50/80 to-cyan-50/80 p-5 shadow-sm transition-all duration-300 hover:border-teal-200 hover:shadow-md"
                        style="animation-delay: 0.25s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-sm font-bold text-white shadow-lg"
                                >29</span
                            >
                            <span
                                data-segment-id="q29"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('Why did the project to make \'new\' shoes out of old shoes fail?', 'q29')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-teal-100/50">
                                <input type="radio" v-model="answers.q29" value="A" class="h-5 w-5 text-teal-600 focus:ring-teal-500" />
                                <span class="w-6 font-bold text-teal-700">A</span>
                                <span
                                    data-segment-id="q29a"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('People believed the \'new\' pairs of shoes were unhygienic.', 'q29a')"
                                ></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-teal-100/50">
                                <input type="radio" v-model="answers.q29" value="B" class="h-5 w-5 text-teal-600 focus:ring-teal-500" />
                                <span class="w-6 font-bold text-teal-700">B</span>
                                <span
                                    data-segment-id="q29b"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('There were not enough good parts to use in the old shoes.', 'q29b')"
                                ></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-teal-100/50">
                                <input type="radio" v-model="answers.q29" value="C" class="h-5 w-5 text-teal-600 focus:ring-teal-500" />
                                <span class="w-6 font-bold text-teal-700">C</span>
                                <span
                                    data-segment-id="q29c"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('The shoes in the \'new\' pairs were not completely alike.', 'q29c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 30 -->
                    <div
                        data-question="30"
                        class="group animate-slideUp rounded-xl border border-teal-100/50 bg-gradient-to-r from-teal-50/80 to-cyan-50/80 p-5 shadow-sm transition-all duration-300 hover:border-teal-200 hover:shadow-md"
                        style="animation-delay: 0.3s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-sm font-bold text-white shadow-lg"
                                >30</span
                            >
                            <span
                                data-segment-id="q30"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('Bella and Don agree that they can present their topic', 'q30')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-teal-100/50">
                                <input type="radio" v-model="answers.q30" value="A" class="h-5 w-5 text-teal-600 focus:ring-teal-500" />
                                <span class="w-6 font-bold text-teal-700">A</span>
                                <span data-segment-id="q30a" class="text-gray-700" v-html="getHighlightedSegment('from a new angle.', 'q30a')"></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-teal-100/50">
                                <input type="radio" v-model="answers.q30" value="B" class="h-5 w-5 text-teal-600 focus:ring-teal-500" />
                                <span class="w-6 font-bold text-teal-700">B</span>
                                <span
                                    data-segment-id="q30b"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('with relevant images.', 'q30b')"
                                ></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-teal-100/50">
                                <input type="radio" v-model="answers.q30" value="C" class="h-5 w-5 text-teal-600 focus:ring-teal-500" />
                                <span class="w-6 font-bold text-teal-700">C</span>
                                <span
                                    data-segment-id="q30c"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('in a straightforward way.', 'q30c')"
                                ></span>
                            </label>
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
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                >
                    <div
                        class="context-menu pointer-events-auto rounded-xl border border-green-200 bg-white/95 p-3 shadow-2xl backdrop-blur-sm"
                        @click.stop
                    >
                        <div class="flex items-center gap-2">
                            <button
                                v-for="color in colors"
                                :key="color.name"
                                @click="applyHighlight(color.name)"
                                :class="[
                                    color.class,
                                    'h-9 w-9 rounded-lg border-2 border-gray-200 transition-all duration-200 hover:scale-110 hover:border-green-400 hover:shadow-md',
                                ]"
                                :title="`Highlight ${color.name}`"
                            ></button>
                            <div class="mx-1 h-6 w-px bg-gray-200"></div>
                            <button
                                @click="openNoteInput"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-green-200 bg-green-50 transition-all duration-200 hover:scale-110 hover:bg-green-100 hover:shadow-md"
                                title="Add Note"
                            >
                                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-green-300 bg-white p-4 shadow-2xl"
                    :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="mb-3">
                        <p class="mb-3 line-clamp-2 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-gray-600 italic">
                            "{{ selectedText }}"
                        </p>
                        <input
                            v-model="noteInputText"
                            type="text"
                            placeholder="Write your note here..."
                            class="note-input-field w-full rounded-lg border-2 border-green-200 px-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-200 focus:outline-none"
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
                            class="rounded-lg bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-green-500/20 transition-all hover:from-green-700 hover:to-emerald-700"
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
    border-color: #10b981;
    background: #10b981;
    box-shadow: inset 0 0 0 3px white;
}
input[type='radio']:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
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
    animation: fadeIn 0.5s ease-out forwards;
}

.animate-slideUp {
    opacity: 0;
    animation: slideUp 0.6s ease-out forwards;
}
</style>
