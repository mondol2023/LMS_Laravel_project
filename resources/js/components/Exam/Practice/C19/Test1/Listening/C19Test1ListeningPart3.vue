<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

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

// Multiple answer handling using reactive (like reference)
const multipleAnswers = reactive({
    q21_22: [] as string[],
    q23_24: [] as string[],
});

// Sync multiple answers to individual answer slots
watch(
    multipleAnswers,
    (newVal) => {
        answers.value.q21 = newVal.q21_22[0] || '';
        answers.value.q22 = newVal.q21_22[1] || '';
        answers.value.q23 = newVal.q23_24[0] || '';
        answers.value.q24 = newVal.q23_24[1] || '';
    },
    { deep: true },
);

// Highlighting & Notes
const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();
const contentTextRef = ref<HTMLElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Text segments with cumulative offsets (using data-segment-text pattern)
const textSegments = ref([
    { text: 'PART 3', offset: 0 },
    { text: 'Questions 21 and 22', offset: 7 },
    { text: 'Choose TWO letters, A-E.', offset: 27 },
    { text: 'Which TWO things did Colin find most satisfying about his bread reuse project?', offset: 52 },
    { text: 'A receiving support from local restaurants', offset: 131 },
    { text: 'B finding a good way to prevent waste', offset: 174 },
    { text: 'C overcoming problems in a basic process', offset: 212 },
    { text: 'D experimenting with designs and colours', offset: 253 },
    { text: 'E learning how to apply 3-D printing', offset: 294 },
    { text: 'Questions 23 and 24', offset: 331 },
    { text: 'Which TWO ways do the students agree that touch-sensitive sensors for food labels could be developed in future?', offset: 351 },
    { text: 'A for use on medical products', offset: 463 },
    { text: 'B to show that food is no longer fit to eat', offset: 493 },
    { text: 'C for use with drinks as well as foods', offset: 537 },
    { text: 'D to provide applications for blind people', offset: 576 },
    { text: 'E to indicate the weight of certain foods', offset: 619 },
    { text: 'Questions 25-30', offset: 661 },
    { text: "What is the students' opinion about each of the following food trends?", offset: 677 },
    { text: 'Choose SIX answers from the box and write the correct answer, A-H, next to Questions 25-30.', offset: 748 },
    { text: 'Opinions', offset: 840 },
    { text: 'A This is only relevant to young people.', offset: 849 },
    { text: 'B This may have disappointing results.', offset: 890 },
    { text: 'C This already seems to be widespread.', offset: 929 },
    { text: 'D Retailers should do more to encourage this.', offset: 968 },
    { text: 'E More financial support is needed for this.', offset: 1014 },
    { text: 'F Most people know little about this.', offset: 1059 },
    { text: 'G There should be stricter regulations about this.', offset: 1097 },
    { text: 'H This could be dangerous.', offset: 1148 },
    { text: 'Food trends', offset: 1175 },
    { text: 'Use of local products', offset: 1187 },
    { text: 'Reduction in unnecessary packaging', offset: 1209 },
    { text: 'Gluten-free and lactose-free food', offset: 1244 },
    { text: 'Use of branded products related to celebrity chefs', offset: 1278 },
    { text: "Development of 'ghost kitchens' for takeaway food", offset: 1329 },
    { text: 'Use of mushrooms for common health concerns', offset: 1379 },
]);

// Helper to get highlighted segment (using data-segment-text pattern like reference)
const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
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

// Handle text selection for highlighting (using data-segment-text like reference)
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            showContextMenu.value = false;
            return;
        }

        const selected = selection.toString().trim();
        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) {
                container = container.parentNode as Node;
            }
            const segmentEl = (container as Element).closest('[data-segment-text]');

            if (!segmentEl) {
                return null;
            }

            const segmentText = segmentEl.getAttribute('data-segment-text');
            const segment = textSegments.value.find((s) => s.text === segmentText);
            if (!segment) {
                return null;
            }

            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
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

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.bottom + 5;
            const viewportWidth = window.innerWidth;
            const menuWidth = 250;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2), viewportWidth - menuWidth / 2),
                y: menuY,
            };
            showContextMenu.value = true;
            selectedText.value = selection.toString();
            selectionRange.value = { start: startAbsOffset, end: endAbsOffset };
        } else {
            showContextMenu.value = false;
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };
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

const handleClickOutside = () => {
    if (showContextMenu.value) {
        showContextMenu.value = false;
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
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// Format answers for export
const getAnswers = () => {
    return {
        q21: answers.value.q21,
        q22: answers.value.q22,
        q23: answers.value.q23,
        q24: answers.value.q24,
        q25: answers.value.q25,
        q26: answers.value.q26,
        q27: answers.value.q27,
        q28: answers.value.q28,
        q29: answers.value.q29,
        q30: answers.value.q30,
    };
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

defineExpose({
    getAnswers,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="container mx-auto px-2 py-4 pb-32 sm:px-4">
        <!-- Main Content Card -->
        <div class="animate-fadeIn flex min-h-screen w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex-shrink-0 border-b border-purple-200 bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div class="animate-pulse-slow flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 shadow-lg backdrop-blur-sm">
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
                        <h2 class="text-xl font-bold tracking-wide text-white">
                            <span :data-segment-text="'PART 3'" v-html="getHighlightedSegment('PART 3')"></span>
                        </h2>
                        <p class="text-sm text-purple-100">Discussion Questions</p>
                    </div>
                </div>
            </div>

            <!-- Content Sections -->
            <div class="space-y-8 p-4 sm:p-6 lg:p-8">
                <!-- Section 1: Questions 21-22 -->
                <section data-question="21" class="animate-slideUp" style="animation-delay: 0.1s">
                    <div
                        class="rounded-2xl border-2 border-purple-200 bg-gradient-to-br from-purple-50 via-white to-violet-50 p-4 shadow-lg transition-all duration-300 hover:shadow-xl sm:p-6"
                    >
                        <!-- Question Header -->
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-12 w-16 transform items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-violet-600 shadow-lg transition-transform hover:scale-105"
                            >
                                <span class="text-sm font-bold text-white">21-22</span>
                            </div>
                            <h3 class="bg-gradient-to-r from-purple-700 to-violet-600 bg-clip-text text-lg font-bold text-transparent">
                                <span :data-segment-text="'Questions 21 and 22'" v-html="getHighlightedSegment('Questions 21 and 22')"></span>
                            </h3>
                        </div>

                        <p class="mb-3 flex items-center gap-2 font-semibold text-purple-800">
                            <svg class="h-5 w-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                ></path>
                            </svg>
                            <span :data-segment-text="'Choose TWO letters, A-E.'" v-html="getHighlightedSegment('Choose TWO letters, A-E.')"></span>
                        </p>

                        <p class="mb-5 text-base leading-relaxed text-gray-700">
                            <span
                                :data-segment-text="'Which TWO things did Colin find most satisfying about his bread reuse project?'"
                                v-html="getHighlightedSegment('Which TWO things did Colin find most satisfying about his bread reuse project?')"
                            ></span>
                        </p>

                        <!-- Options -->
                        <div class="space-y-3">
                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="A"
                                    v-model="multipleAnswers.q21_22"
                                    :disabled="multipleAnswers.q21_22.length === 2 && !multipleAnswers.q21_22.includes('A')"
                                    class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                    <span
                                        :data-segment-text="'A receiving support from local restaurants'"
                                        v-html="getHighlightedSegment('A receiving support from local restaurants')"
                                    ></span>
                                </span>
                            </label>

                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="B"
                                    v-model="multipleAnswers.q21_22"
                                    :disabled="multipleAnswers.q21_22.length === 2 && !multipleAnswers.q21_22.includes('B')"
                                    class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                    <span
                                        :data-segment-text="'B finding a good way to prevent waste'"
                                        v-html="getHighlightedSegment('B finding a good way to prevent waste')"
                                    ></span>
                                </span>
                            </label>

                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="C"
                                    v-model="multipleAnswers.q21_22"
                                    :disabled="multipleAnswers.q21_22.length === 2 && !multipleAnswers.q21_22.includes('C')"
                                    class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                    <span
                                        :data-segment-text="'C overcoming problems in a basic process'"
                                        v-html="getHighlightedSegment('C overcoming problems in a basic process')"
                                    ></span>
                                </span>
                            </label>

                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="D"
                                    v-model="multipleAnswers.q21_22"
                                    :disabled="multipleAnswers.q21_22.length === 2 && !multipleAnswers.q21_22.includes('D')"
                                    class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                    <span
                                        :data-segment-text="'D experimenting with designs and colours'"
                                        v-html="getHighlightedSegment('D experimenting with designs and colours')"
                                    ></span>
                                </span>
                            </label>

                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="E"
                                    v-model="multipleAnswers.q21_22"
                                    :disabled="multipleAnswers.q21_22.length === 2 && !multipleAnswers.q21_22.includes('E')"
                                    class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                    <span
                                        :data-segment-text="'E learning how to apply 3-D printing'"
                                        v-html="getHighlightedSegment('E learning how to apply 3-D printing')"
                                    ></span>
                                </span>
                            </label>
                        </div>

                        <!-- Selection Counter -->
                        <div class="mt-4 rounded-xl border border-purple-200 bg-gradient-to-r from-purple-100 to-violet-100 p-3 shadow-inner">
                            <p class="flex items-center gap-2 font-medium text-purple-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                Selected: <span class="font-bold">{{ multipleAnswers.q21_22.length }}/2</span> answers
                                <span v-if="multipleAnswers.q21_22.length > 0" class="ml-2 text-sm">({{ multipleAnswers.q21_22.join(', ') }})</span>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Section 2: Questions 23-24 -->
                <section data-question="23" class="animate-slideUp" style="animation-delay: 0.2s">
                    <div
                        class="rounded-2xl border-2 border-violet-200 bg-gradient-to-br from-violet-50 via-white to-indigo-50 p-4 shadow-lg transition-all duration-300 hover:shadow-xl sm:p-6"
                    >
                        <!-- Question Header -->
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-12 w-16 transform items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 shadow-lg transition-transform hover:scale-105"
                            >
                                <span class="text-sm font-bold text-white">23-24</span>
                            </div>
                            <h3 class="bg-gradient-to-r from-violet-700 to-indigo-600 bg-clip-text text-lg font-bold text-transparent">
                                <span :data-segment-text="'Questions 23 and 24'" v-html="getHighlightedSegment('Questions 23 and 24')"></span>
                            </h3>
                        </div>

                        <p class="mb-3 flex items-center gap-2 font-semibold text-violet-800">
                            <svg class="h-5 w-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                ></path>
                            </svg>
                            <span :data-segment-text="'Choose TWO letters, A-E.'" v-html="getHighlightedSegment('Choose TWO letters, A-E.')"></span>
                        </p>

                        <p class="mb-5 text-base leading-relaxed text-gray-700">
                            <span
                                :data-segment-text="'Which TWO ways do the students agree that touch-sensitive sensors for food labels could be developed in future?'"
                                v-html="
                                    getHighlightedSegment(
                                        'Which TWO ways do the students agree that touch-sensitive sensors for food labels could be developed in future?',
                                    )
                                "
                            ></span>
                        </p>

                        <!-- Options -->
                        <div class="space-y-3">
                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-violet-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-violet-300 hover:bg-violet-50"
                            >
                                <input
                                    type="checkbox"
                                    value="A"
                                    v-model="multipleAnswers.q23_24"
                                    :disabled="multipleAnswers.q23_24.length === 2 && !multipleAnswers.q23_24.includes('A')"
                                    class="mt-0.5 h-5 w-5 rounded border-violet-300 text-violet-600 transition-all focus:ring-violet-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-violet-800">
                                    <span
                                        :data-segment-text="'A for use on medical products'"
                                        v-html="getHighlightedSegment('A for use on medical products')"
                                    ></span>
                                </span>
                            </label>

                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-violet-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-violet-300 hover:bg-violet-50"
                            >
                                <input
                                    type="checkbox"
                                    value="B"
                                    v-model="multipleAnswers.q23_24"
                                    :disabled="multipleAnswers.q23_24.length === 2 && !multipleAnswers.q23_24.includes('B')"
                                    class="mt-0.5 h-5 w-5 rounded border-violet-300 text-violet-600 transition-all focus:ring-violet-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-violet-800">
                                    <span
                                        :data-segment-text="'B to show that food is no longer fit to eat'"
                                        v-html="getHighlightedSegment('B to show that food is no longer fit to eat')"
                                    ></span>
                                </span>
                            </label>

                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-violet-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-violet-300 hover:bg-violet-50"
                            >
                                <input
                                    type="checkbox"
                                    value="C"
                                    v-model="multipleAnswers.q23_24"
                                    :disabled="multipleAnswers.q23_24.length === 2 && !multipleAnswers.q23_24.includes('C')"
                                    class="mt-0.5 h-5 w-5 rounded border-violet-300 text-violet-600 transition-all focus:ring-violet-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-violet-800">
                                    <span
                                        :data-segment-text="'C for use with drinks as well as foods'"
                                        v-html="getHighlightedSegment('C for use with drinks as well as foods')"
                                    ></span>
                                </span>
                            </label>

                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-violet-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-violet-300 hover:bg-violet-50"
                            >
                                <input
                                    type="checkbox"
                                    value="D"
                                    v-model="multipleAnswers.q23_24"
                                    :disabled="multipleAnswers.q23_24.length === 2 && !multipleAnswers.q23_24.includes('D')"
                                    class="mt-0.5 h-5 w-5 rounded border-violet-300 text-violet-600 transition-all focus:ring-violet-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-violet-800">
                                    <span
                                        :data-segment-text="'D to provide applications for blind people'"
                                        v-html="getHighlightedSegment('D to provide applications for blind people')"
                                    ></span>
                                </span>
                            </label>

                            <label
                                class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-violet-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-violet-300 hover:bg-violet-50"
                            >
                                <input
                                    type="checkbox"
                                    value="E"
                                    v-model="multipleAnswers.q23_24"
                                    :disabled="multipleAnswers.q23_24.length === 2 && !multipleAnswers.q23_24.includes('E')"
                                    class="mt-0.5 h-5 w-5 rounded border-violet-300 text-violet-600 transition-all focus:ring-violet-500"
                                />
                                <span class="font-medium text-gray-700 transition-colors group-hover:text-violet-800">
                                    <span
                                        :data-segment-text="'E to indicate the weight of certain foods'"
                                        v-html="getHighlightedSegment('E to indicate the weight of certain foods')"
                                    ></span>
                                </span>
                            </label>
                        </div>

                        <!-- Selection Counter -->
                        <div class="mt-4 rounded-xl border border-violet-200 bg-gradient-to-r from-violet-100 to-indigo-100 p-3 shadow-inner">
                            <p class="flex items-center gap-2 font-medium text-violet-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                Selected: <span class="font-bold">{{ multipleAnswers.q23_24.length }}/2</span> answers
                                <span v-if="multipleAnswers.q23_24.length > 0" class="ml-2 text-sm">({{ multipleAnswers.q23_24.join(', ') }})</span>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Section 3: Questions 25-30 Matching -->
                <section data-question="25" class="animate-slideUp" style="animation-delay: 0.3s">
                    <div class="rounded-2xl border-2 border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-purple-50 p-4 shadow-lg sm:p-6">
                        <!-- Question Header -->
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-12 w-16 transform items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 shadow-lg transition-transform hover:scale-105"
                            >
                                <span class="text-sm font-bold text-white">25-30</span>
                            </div>
                            <h3 class="bg-gradient-to-r from-indigo-700 to-purple-600 bg-clip-text text-lg font-bold text-transparent">
                                <span :data-segment-text="'Questions 25-30'" v-html="getHighlightedSegment('Questions 25-30')"></span>
                            </h3>
                        </div>

                        <p class="mb-2 text-gray-700">
                            <span
                                :data-segment-text="'What is the students\' opinion about each of the following food trends?'"
                                v-html="getHighlightedSegment('What is the students\' opinion about each of the following food trends?')"
                            ></span>
                        </p>
                        <p class="mb-6 font-semibold text-indigo-800">
                            <span
                                :data-segment-text="'Choose SIX answers from the box and write the correct answer, A-H, next to Questions 25-30.'"
                                v-html="
                                    getHighlightedSegment(
                                        'Choose SIX answers from the box and write the correct answer, A-H, next to Questions 25-30.',
                                    )
                                "
                            ></span>
                        </p>

                        <!-- Opinions Box -->
                        <div
                            class="mb-6 rounded-2xl border-2 border-indigo-200 bg-gradient-to-br from-indigo-100 via-purple-50 to-violet-100 p-5 shadow-inner"
                        >
                            <h4 class="mb-4 flex items-center justify-center gap-2 text-center text-lg font-bold text-indigo-900">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                                    ></path>
                                </svg>
                                <span :data-segment-text="'Opinions'" v-html="getHighlightedSegment('Opinions')"></span>
                            </h4>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div
                                    class="flex items-start gap-2 rounded-xl border border-indigo-100 bg-white/80 p-3 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600"
                                        >A</span
                                    >
                                    <span class="text-sm text-gray-700"
                                        ><span
                                            :data-segment-text="'A This is only relevant to young people.'"
                                            v-html="getHighlightedSegment('A This is only relevant to young people.')"
                                        ></span
                                    ></span>
                                </div>
                                <div
                                    class="flex items-start gap-2 rounded-xl border border-indigo-100 bg-white/80 p-3 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600"
                                        >B</span
                                    >
                                    <span class="text-sm text-gray-700"
                                        ><span
                                            :data-segment-text="'B This may have disappointing results.'"
                                            v-html="getHighlightedSegment('B This may have disappointing results.')"
                                        ></span
                                    ></span>
                                </div>
                                <div
                                    class="flex items-start gap-2 rounded-xl border border-indigo-100 bg-white/80 p-3 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600"
                                        >C</span
                                    >
                                    <span class="text-sm text-gray-700"
                                        ><span
                                            :data-segment-text="'C This already seems to be widespread.'"
                                            v-html="getHighlightedSegment('C This already seems to be widespread.')"
                                        ></span
                                    ></span>
                                </div>
                                <div
                                    class="flex items-start gap-2 rounded-xl border border-indigo-100 bg-white/80 p-3 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600"
                                        >D</span
                                    >
                                    <span class="text-sm text-gray-700"
                                        ><span
                                            :data-segment-text="'D Retailers should do more to encourage this.'"
                                            v-html="getHighlightedSegment('D Retailers should do more to encourage this.')"
                                        ></span
                                    ></span>
                                </div>
                                <div
                                    class="flex items-start gap-2 rounded-xl border border-indigo-100 bg-white/80 p-3 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600"
                                        >E</span
                                    >
                                    <span class="text-sm text-gray-700"
                                        ><span
                                            :data-segment-text="'E More financial support is needed for this.'"
                                            v-html="getHighlightedSegment('E More financial support is needed for this.')"
                                        ></span
                                    ></span>
                                </div>
                                <div
                                    class="flex items-start gap-2 rounded-xl border border-indigo-100 bg-white/80 p-3 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600"
                                        >F</span
                                    >
                                    <span class="text-sm text-gray-700"
                                        ><span
                                            :data-segment-text="'F Most people know little about this.'"
                                            v-html="getHighlightedSegment('F Most people know little about this.')"
                                        ></span
                                    ></span>
                                </div>
                                <div
                                    class="flex items-start gap-2 rounded-xl border border-indigo-100 bg-white/80 p-3 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600"
                                        >G</span
                                    >
                                    <span class="text-sm text-gray-700"
                                        ><span
                                            :data-segment-text="'G There should be stricter regulations about this.'"
                                            v-html="getHighlightedSegment('G There should be stricter regulations about this.')"
                                        ></span
                                    ></span>
                                </div>
                                <div
                                    class="flex items-start gap-2 rounded-xl border border-indigo-100 bg-white/80 p-3 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600"
                                        >H</span
                                    >
                                    <span class="text-sm text-gray-700"
                                        ><span
                                            :data-segment-text="'H This could be dangerous.'"
                                            v-html="getHighlightedSegment('H This could be dangerous.')"
                                        ></span
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <!-- Food Trends -->
                        <h4 class="mb-4 flex items-center gap-2 text-lg font-bold text-purple-900">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span :data-segment-text="'Food trends'" v-html="getHighlightedSegment('Food trends')"></span>
                        </h4>

                        <div class="space-y-4">
                            <div
                                class="group flex items-center gap-4 rounded-xl border-2 border-purple-100 bg-gradient-to-r from-purple-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:shadow-md"
                            >
                                <span
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-violet-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                    >25</span
                                >
                                <span class="flex-1 font-medium text-gray-800"
                                    ><span :data-segment-text="'Use of local products'" v-html="getHighlightedSegment('Use of local products')"></span
                                ></span>
                                <input
                                    v-model="answers.q25"
                                    type="text"
                                    maxlength="1"
                                    class="h-12 w-14 rounded-xl border-2 border-purple-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                                    placeholder="?"
                                />
                            </div>

                            <div
                                class="group flex items-center gap-4 rounded-xl border-2 border-purple-100 bg-gradient-to-r from-purple-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:shadow-md"
                            >
                                <span
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-violet-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                    >26</span
                                >
                                <span class="flex-1 font-medium text-gray-800"
                                    ><span
                                        :data-segment-text="'Reduction in unnecessary packaging'"
                                        v-html="getHighlightedSegment('Reduction in unnecessary packaging')"
                                    ></span
                                ></span>
                                <input
                                    v-model="answers.q26"
                                    type="text"
                                    maxlength="1"
                                    class="h-12 w-14 rounded-xl border-2 border-purple-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                                    placeholder="?"
                                />
                            </div>

                            <div
                                class="group flex items-center gap-4 rounded-xl border-2 border-purple-100 bg-gradient-to-r from-purple-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:shadow-md"
                            >
                                <span
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-violet-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                    >27</span
                                >
                                <span class="flex-1 font-medium text-gray-800"
                                    ><span
                                        :data-segment-text="'Gluten-free and lactose-free food'"
                                        v-html="getHighlightedSegment('Gluten-free and lactose-free food')"
                                    ></span
                                ></span>
                                <input
                                    v-model="answers.q27"
                                    type="text"
                                    maxlength="1"
                                    class="h-12 w-14 rounded-xl border-2 border-purple-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                                    placeholder="?"
                                />
                            </div>

                            <div
                                class="group flex items-center gap-4 rounded-xl border-2 border-purple-100 bg-gradient-to-r from-purple-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:shadow-md"
                            >
                                <span
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-violet-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                    >28</span
                                >
                                <span class="flex-1 font-medium text-gray-800"
                                    ><span
                                        :data-segment-text="'Use of branded products related to celebrity chefs'"
                                        v-html="getHighlightedSegment('Use of branded products related to celebrity chefs')"
                                    ></span
                                ></span>
                                <input
                                    v-model="answers.q28"
                                    type="text"
                                    maxlength="1"
                                    class="h-12 w-14 rounded-xl border-2 border-purple-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                                    placeholder="?"
                                />
                            </div>

                            <div
                                class="group flex items-center gap-4 rounded-xl border-2 border-purple-100 bg-gradient-to-r from-purple-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:shadow-md"
                            >
                                <span
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-violet-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                    >29</span
                                >
                                <span class="flex-1 font-medium text-gray-800"
                                    ><span
                                        :data-segment-text="'Development of \'ghost kitchens\' for takeaway food'"
                                        v-html="getHighlightedSegment('Development of \'ghost kitchens\' for takeaway food')"
                                    ></span
                                ></span>
                                <input
                                    v-model="answers.q29"
                                    type="text"
                                    maxlength="1"
                                    class="h-12 w-14 rounded-xl border-2 border-purple-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                                    placeholder="?"
                                />
                            </div>

                            <div
                                class="group flex items-center gap-4 rounded-xl border-2 border-purple-100 bg-gradient-to-r from-purple-50 to-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:shadow-md"
                            >
                                <span
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-violet-600 text-sm font-bold text-white shadow-md transition-transform group-hover:scale-110"
                                    >30</span
                                >
                                <span class="flex-1 font-medium text-gray-800"
                                    ><span
                                        :data-segment-text="'Use of mushrooms for common health concerns'"
                                        v-html="getHighlightedSegment('Use of mushrooms for common health concerns')"
                                    ></span
                                ></span>
                                <input
                                    v-model="answers.q30"
                                    type="text"
                                    maxlength="1"
                                    class="h-12 w-14 rounded-xl border-2 border-purple-300 p-2 text-center text-lg font-bold uppercase shadow-sm transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                                    placeholder="?"
                                />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Context Menu & Note Input -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="animate-scaleIn pointer-events-auto fixed z-[9999] rounded-xl border-2 border-purple-200 bg-white p-3 shadow-2xl"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="flex items-center gap-2">
                        <button
                            v-for="color in colors"
                            :key="color.name"
                            @click="applyHighlight(color.name)"
                            :class="[
                                color.class,
                                'h-9 w-9 rounded-lg border-2 border-gray-200 transition-all hover:scale-110 hover:border-purple-400 hover:shadow-md',
                            ]"
                            :title="`Highlight ${color.name}`"
                        ></button>
                        <div class="mx-1 h-8 w-px bg-gray-200"></div>
                        <button
                            @click="openNoteInput"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-gray-200 bg-blue-50 transition-all hover:scale-110 hover:border-blue-400 hover:bg-blue-100 hover:shadow-md"
                            title="Add Note"
                        >
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <div
                v-if="showNoteInput"
                class="animate-scaleIn fixed z-[9999] w-80 rounded-2xl border-2 border-blue-300 bg-white p-5 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="mb-4">
                    <p class="mb-3 rounded-xl border border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50 p-3 text-sm text-gray-600 italic">
                        "{{ selectedText }}"
                    </p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        placeholder="Write your note here..."
                        class="note-input-field w-full rounded-xl border-2 border-blue-200 px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-3">
                    <button
                        @click="cancelNote"
                        class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-gray-200 hover:shadow-sm"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveNote"
                        class="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-sm font-medium text-white transition-all hover:from-blue-700 hover:to-indigo-700 hover:shadow-md"
                    >
                        Save Note
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
/* Highlight Styles */
mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 3px;
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
    background-color: rgba(254, 240, 138, 0.7);
}
.highlight-green {
    background-color: rgba(187, 247, 208, 0.7);
}
.highlight-blue {
    background-color: rgba(191, 219, 254, 0.7);
}
.highlight-pink {
    background-color: rgba(251, 207, 232, 0.7);
}
.highlight-orange {
    background-color: rgba(254, 215, 170, 0.7);
}

/* Animation Classes */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
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

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: translateX(-50%) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) scale(1);
    }
}

@keyframes pulse-slow {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.animate-fadeIn {
    animation: fadeIn 0.5s ease-out forwards;
}

.animate-slideUp {
    opacity: 0;
    animation: slideUp 0.6s ease-out forwards;
}

.animate-scaleIn {
    animation: scaleIn 0.2s ease-out forwards;
}

.animate-pulse-slow {
    animation: pulse-slow 2s ease-in-out infinite;
}

/* Question Highlight Effect */
.highlight-question {
    background-color: rgba(139, 92, 246, 0.15);
    border-radius: 12px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(139, 92, 246, 0.15);
        transform: scale(1);
    }
    50% {
        background-color: rgba(139, 92, 246, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(139, 92, 246, 0.15);
        transform: scale(1);
    }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #8b5cf6, #6366f1);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #7c3aed, #4f46e5);
}

/* Input Focus Animation */
input:focus {
    transform: scale(1.02);
}

/* Checkbox Custom Styling */
input[type='checkbox']:checked {
    animation: checkPop 0.2s ease-out;
}

@keyframes checkPop {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}
</style>
