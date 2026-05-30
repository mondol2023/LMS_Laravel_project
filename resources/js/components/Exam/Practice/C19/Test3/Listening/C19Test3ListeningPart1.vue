<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    phone?: string | null;
    examId?: string;
}

defineProps<Props>();

// Answers State
const answers = ref({
    q1: '',
    q2: '',
    q3: '',
    q4: '',
    q5: '',
    q6: '',
    q7: '',
    q8: '',
    q9: '',
    q10: '',
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

// Text segments with continuous offsets for multiline support
const textSegments = ref([
    { id: 'header', text: 'PART 1', offset: 0 },
    { id: 'questions', text: 'Questions 1 - 10', offset: 7 },
    { id: 'instruction1', text: 'Complete the notes below.', offset: 24 },
    { id: 'instruction2', text: 'Write ONE WORD AND/OR A NUMBER for each answer.', offset: 50 },
    { id: 'title', text: 'Local food shops', offset: 98 },
    { id: 'whereToGo', text: 'Where to go', offset: 115 },
    { id: 'kitePlaceLabel', text: 'Kite Place – near the', offset: 127 },
    { id: 'fishMarketTitle', text: 'Fish market', offset: 149 },
    { id: 'fishMarket1', text: 'cross the', offset: 161 },
    { id: 'fishMarket1b', text: 'and turn right', offset: 179 },
    { id: 'fishMarket2', text: 'best to go before', offset: 194 },
    { id: 'fishMarket2b', text: 'pm, earlier than closing time', offset: 219 },
    { id: 'organicTitle', text: 'Organic shop', offset: 249 },
    { id: 'organic1', text: 'called', offset: 262 },
    { id: 'organic2', text: 'below a restaurant in the large, grey building', offset: 279 },
    { id: 'organic3', text: 'look for the large', offset: 326 },
    { id: 'organic3b', text: 'outside', offset: 353 },
    { id: 'supermarketTitle', text: 'Supermarket', offset: 361 },
    { id: 'supermarket1', text: 'take a', offset: 373 },
    { id: 'supermarket1b', text: 'minibus, number 289', offset: 388 },
    { id: 'shoppingTitle', text: 'Shopping', offset: 408 },
    { id: 'toBuyCol', text: 'To buy', offset: 417 },
    { id: 'otherIdeasCol', text: 'Other ideas', offset: 424 },
    { id: 'fishMarketRow', text: 'Fish market', offset: 436 },
    { id: 'prawns', text: 'a dozen prawns', offset: 448 },
    { id: 'seaweed', text: 'a handful of', offset: 463 },
    { id: 'seaweedType', text: '(type of seaweed)', offset: 483 },
    { id: 'organicRow', text: 'Organic shop', offset: 501 },
    { id: 'beans', text: 'beans and a', offset: 514 },
    { id: 'dessert', text: 'for dessert', offset: 533 },
    { id: 'spices', text: 'spices and', offset: 545 },
    { id: 'bakeryRow', text: 'Bakery', offset: 556 },
    { id: 'brownLoaf', text: 'a brown loaf', offset: 563 },
    { id: 'tart', text: 'tart', offset: 583 },
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

    if (overlappingHighlights.length === 0) {
        return segmentText;
    }

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

// Handle text selection for highlighting
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

    // Add highlight for the note
    addHighlight(selectedText.value, 'yellow', selectionRange.value.start, selectionRange.value.end);

    notes.value.push({
        id: Date.now(),
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: selectionRange.value.start,
        end: selectionRange.value.end,
        part: 'Part 1',
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
    const inputEl = document.querySelector(`input[data-question="${questionNumber}"]`);
    if (inputEl) {
        inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        (inputEl as HTMLInputElement).focus();
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
    <div ref="contentTextRef" class="mx-auto mt-10 mb-16 px-4 py-8">
        <div
            class="animate-fadeIn w-full rounded-2xl border border-rose-100/50 bg-gradient-to-br from-white via-rose-50/30 to-pink-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-rose-200/50 bg-gradient-to-r from-rose-50 to-pink-50 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="animate-pulse-slow flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-rose-500 to-pink-600 shadow-lg shadow-rose-500/30"
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
                            class="bg-gradient-to-r from-rose-600 to-pink-600 bg-clip-text text-xl font-bold tracking-widest text-transparent uppercase"
                        >
                            <span data-segment-id="header" v-html="getHighlightedSegment('PART 1', 'header')"></span>
                        </h2>
                        <h1 class="text-base font-semibold text-gray-700">
                            <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 1 - 10', 'questions')"></span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Questions 1-6 Section -->
            <div class="animate-slideUp mb-8" style="animation-delay: 0.1s">
                <div class="mb-4">
                    <div class="mb-2 inline-block rounded-lg bg-rose-100 px-3 py-1">
                        <span class="text-sm font-semibold text-rose-700">Questions 1-6</span>
                    </div>
                    <p class="mb-1 text-sm text-gray-600">
                        <span data-segment-id="instruction1" v-html="getHighlightedSegment('Complete the notes below.', 'instruction1')"></span>
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-rose-100 to-pink-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="instruction2"
                            class="text-sm font-semibold text-rose-800"
                            v-html="getHighlightedSegment('Write ONE WORD AND/OR A NUMBER for each answer.', 'instruction2')"
                        ></span>
                    </div>
                </div>

                <!-- Title -->
                <div class="rounded-2xl border border-rose-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <h3
                        class="mb-6 border-b-2 border-rose-200 bg-gradient-to-r from-rose-600 to-pink-600 bg-clip-text pb-2 text-center text-2xl font-bold text-transparent"
                    >
                        <span data-segment-id="title" v-html="getHighlightedSegment('Local food shops', 'title')"></span>
                    </h3>

                    <!-- Where to go -->
                    <h4 class="mb-4 text-lg font-semibold text-rose-800">
                        <span data-segment-id="whereToGo" v-html="getHighlightedSegment('Where to go', 'whereToGo')"></span>
                    </h4>

                    <div class="space-y-4">
                        <!-- Question 1: Kite Place -->
                        <div
                            data-question="1"
                            class="group flex items-center gap-3 rounded-xl bg-gradient-to-r from-rose-50/80 to-pink-50/80 p-4 transition-all duration-300 hover:from-rose-100/80 hover:to-pink-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >1</span
                            >
                            <span
                                data-segment-id="kitePlaceLabel"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Kite Place – near the', 'kitePlaceLabel')"
                            ></span>
                            <input
                                v-model="answers.q1"
                                data-question="1"
                                type="text"
                                class="w-32 rounded-xl border-2 border-rose-300 p-2.5 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                placeholder="......"
                            />
                        </div>

                        <!-- Fish market -->
                        <div class="ml-2">
                            <h5 class="mb-3 font-semibold text-gray-800">
                                <span data-segment-id="fishMarketTitle" v-html="getHighlightedSegment('Fish market', 'fishMarketTitle')"></span>
                            </h5>
                            <div class="ml-4 space-y-3">
                                <!-- Question 2 -->
                                <div
                                    data-question="2"
                                    class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-rose-50/80 to-pink-50/80 p-4 transition-all duration-300 hover:from-rose-100/80 hover:to-pink-100/80"
                                >
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                        >2</span
                                    >
                                    <span
                                        data-segment-id="fishMarket1"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('cross the', 'fishMarket1')"
                                    ></span>
                                    <input
                                        v-model="answers.q2"
                                        data-question="2"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-rose-300 p-2.5 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                        placeholder="......"
                                    />
                                    <span
                                        data-segment-id="fishMarket1b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('and turn right', 'fishMarket1b')"
                                    ></span>
                                </div>
                                <!-- Question 3 -->
                                <div
                                    data-question="3"
                                    class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-rose-50/80 to-pink-50/80 p-4 transition-all duration-300 hover:from-rose-100/80 hover:to-pink-100/80"
                                >
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                        >3</span
                                    >
                                    <span
                                        data-segment-id="fishMarket2"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('best to go before', 'fishMarket2')"
                                    ></span>
                                    <input
                                        v-model="answers.q3"
                                        data-question="3"
                                        type="text"
                                        class="w-20 rounded-xl border-2 border-rose-300 p-2.5 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                        placeholder="......"
                                    />
                                    <span
                                        data-segment-id="fishMarket2b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('pm, earlier than closing time', 'fishMarket2b')"
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <!-- Organic shop -->
                        <div class="ml-2">
                            <h5 class="mb-3 font-semibold text-gray-800">
                                <span data-segment-id="organicTitle" v-html="getHighlightedSegment('Organic shop', 'organicTitle')"></span>
                            </h5>
                            <div class="ml-4 space-y-3">
                                <!-- Question 4 -->
                                <div
                                    data-question="4"
                                    class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-rose-50/80 to-pink-50/80 p-4 transition-all duration-300 hover:from-rose-100/80 hover:to-pink-100/80"
                                >
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                        >4</span
                                    >
                                    <span
                                        data-segment-id="organic1"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('called', 'organic1')"
                                    ></span>
                                    <span class="text-gray-500">'</span>
                                    <input
                                        v-model="answers.q4"
                                        data-question="4"
                                        type="text"
                                        class="w-32 rounded-xl border-2 border-rose-300 p-2.5 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                        placeholder="......"
                                    />
                                    <span class="text-gray-500">'</span>
                                </div>
                                <div class="ml-4 text-gray-600">
                                    <span
                                        data-segment-id="organic2"
                                        v-html="getHighlightedSegment('below a restaurant in the large, grey building', 'organic2')"
                                    ></span>
                                </div>
                                <!-- Question 5 -->
                                <div
                                    data-question="5"
                                    class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-rose-50/80 to-pink-50/80 p-4 transition-all duration-300 hover:from-rose-100/80 hover:to-pink-100/80"
                                >
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                        >5</span
                                    >
                                    <span
                                        data-segment-id="organic3"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('look for the large', 'organic3')"
                                    ></span>
                                    <input
                                        v-model="answers.q5"
                                        data-question="5"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-rose-300 p-2.5 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                        placeholder="......"
                                    />
                                    <span
                                        data-segment-id="organic3b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('outside', 'organic3b')"
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <!-- Supermarket -->
                        <div class="ml-2">
                            <h5 class="mb-3 font-semibold text-gray-800">
                                <span data-segment-id="supermarketTitle" v-html="getHighlightedSegment('Supermarket', 'supermarketTitle')"></span>
                            </h5>
                            <div class="ml-4">
                                <!-- Question 6 -->
                                <div
                                    data-question="6"
                                    class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-rose-50/80 to-pink-50/80 p-4 transition-all duration-300 hover:from-rose-100/80 hover:to-pink-100/80"
                                >
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                        >6</span
                                    >
                                    <span
                                        data-segment-id="supermarket1"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('take a', 'supermarket1')"
                                    ></span>
                                    <input
                                        v-model="answers.q6"
                                        data-question="6"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-rose-300 p-2.5 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                        placeholder="......"
                                    />
                                    <span
                                        data-segment-id="supermarket1b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('minibus, number 289', 'supermarket1b')"
                                    ></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions 7-10 Section (Table) -->
            <div class="animate-slideUp border-t-2 border-dashed border-rose-200 pt-8" style="animation-delay: 0.2s">
                <div class="mb-6">
                    <div class="mb-2 inline-block rounded-lg bg-rose-100 px-3 py-1">
                        <span class="text-sm font-semibold text-rose-700">Questions 7-10</span>
                    </div>
                    <p class="mb-1 text-sm text-gray-600">Complete the table below.</p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-pink-100 to-rose-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            ></path>
                        </svg>
                        <span class="text-sm font-semibold text-pink-800">Write ONE WORD ONLY for each answer.</span>
                    </div>
                </div>

                <div class="mb-6 text-center">
                    <h3
                        class="inline-block border-b-2 border-pink-300 bg-gradient-to-r from-pink-600 to-rose-600 bg-clip-text pb-2 text-2xl font-bold text-transparent"
                    >
                        <span data-segment-id="shoppingTitle" v-html="getHighlightedSegment('Shopping', 'shoppingTitle')"></span>
                    </h3>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-2xl border border-rose-200 shadow-lg">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-rose-500 to-pink-600 text-white">
                                <th class="rounded-tl-xl p-4 text-left font-bold">Shop</th>
                                <th class="p-4 text-left font-bold">
                                    <span data-segment-id="toBuyCol" v-html="getHighlightedSegment('To buy', 'toBuyCol')"></span>
                                </th>
                                <th class="rounded-tr-xl p-4 text-left font-bold">
                                    <span data-segment-id="otherIdeasCol" v-html="getHighlightedSegment('Other ideas', 'otherIdeasCol')"></span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Fish market row -->
                            <tr class="bg-white transition-colors hover:bg-rose-50/50">
                                <td class="border-b border-rose-100 p-4 font-semibold text-rose-800">
                                    <span data-segment-id="fishMarketRow" v-html="getHighlightedSegment('Fish market', 'fishMarketRow')"></span>
                                </td>
                                <td class="border-b border-rose-100 p-4 text-gray-700">
                                    <span data-segment-id="prawns" v-html="getHighlightedSegment('a dozen prawns', 'prawns')"></span>
                                </td>
                                <td data-question="7" class="border-b border-rose-100 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            data-segment-id="seaweed"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('a handful of', 'seaweed')"
                                        ></span>
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-xs font-bold text-white shadow-lg"
                                            >7</span
                                        >
                                        <input
                                            v-model="answers.q7"
                                            data-question="7"
                                            type="text"
                                            class="w-24 rounded-lg border-2 border-rose-300 p-2 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                            placeholder="......"
                                        />
                                        <span
                                            data-segment-id="seaweedType"
                                            class="text-sm text-gray-500"
                                            v-html="getHighlightedSegment('(type of seaweed)', 'seaweedType')"
                                        ></span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Organic shop row -->
                            <tr class="bg-pink-50/30 transition-colors hover:bg-rose-50/50">
                                <td class="border-b border-rose-100 p-4 font-semibold text-rose-800">
                                    <span data-segment-id="organicRow" v-html="getHighlightedSegment('Organic shop', 'organicRow')"></span>
                                </td>
                                <td data-question="8" class="border-b border-rose-100 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            data-segment-id="beans"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('beans and a', 'beans')"
                                        ></span>
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-xs font-bold text-white shadow-lg"
                                            >8</span
                                        >
                                        <input
                                            v-model="answers.q8"
                                            data-question="8"
                                            type="text"
                                            class="w-24 rounded-lg border-2 border-rose-300 p-2 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                            placeholder="......"
                                        />
                                        <span
                                            data-segment-id="dessert"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('for dessert', 'dessert')"
                                        ></span>
                                    </div>
                                </td>
                                <td data-question="9" class="border-b border-rose-100 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            data-segment-id="spices"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('spices and', 'spices')"
                                        ></span>
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-xs font-bold text-white shadow-lg"
                                            >9</span
                                        >
                                        <input
                                            v-model="answers.q9"
                                            data-question="9"
                                            type="text"
                                            class="w-24 rounded-lg border-2 border-rose-300 p-2 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                            placeholder="......"
                                        />
                                    </div>
                                </td>
                            </tr>

                            <!-- Bakery row -->
                            <tr class="bg-white transition-colors hover:bg-rose-50/50">
                                <td class="rounded-bl-xl p-4 font-semibold text-rose-800">
                                    <span data-segment-id="bakeryRow" v-html="getHighlightedSegment('Bakery', 'bakeryRow')"></span>
                                </td>
                                <td class="p-4 text-gray-700">
                                    <span data-segment-id="brownLoaf" v-html="getHighlightedSegment('a brown loaf', 'brownLoaf')"></span>
                                </td>
                                <td data-question="10" class="rounded-br-xl p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-gray-700">a</span>
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-xs font-bold text-white shadow-lg"
                                            >10</span
                                        >
                                        <input
                                            v-model="answers.q10"
                                            data-question="10"
                                            type="text"
                                            class="w-24 rounded-lg border-2 border-rose-300 p-2 text-sm shadow-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                            placeholder="......"
                                        />
                                        <span data-segment-id="tart" class="text-gray-700" v-html="getHighlightedSegment('tart', 'tart')"></span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Context Menu for Highlighting -->
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
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-rose-200 bg-rose-50 transition-all duration-200 hover:scale-110 hover:bg-rose-100 hover:shadow-md"
                                title="Add Note"
                            >
                                <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-rose-300 bg-white p-4 shadow-2xl"
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
                            class="note-input-field w-full rounded-lg border-2 border-rose-200 px-4 py-3 text-sm transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none"
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
                            class="rounded-lg bg-gradient-to-r from-rose-600 to-pink-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-rose-500/20 transition-all hover:from-rose-700 hover:to-pink-700"
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
