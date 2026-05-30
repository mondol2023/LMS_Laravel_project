<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Answers State
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

// Highlighting & Notes
const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();
const contentTextRef = ref<HTMLElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Text segments with continuous offsets for multiline selection
const textSegments = ref([
    { id: 'header', text: 'PART 2', offset: 0 },
    { id: 'questions1', text: 'Questions 11 - 15', offset: 7 },
    { id: 'instruction1', text: 'Choose the correct letter, A, B or C.', offset: 25 },
    { id: 'title1', text: 'Stanthorpe Twinning Association', offset: 64 },
    { id: 'q11', text: 'During the visit to Malatte, in France, members especially enjoyed', offset: 97 },
    { id: 'q11a', text: 'going to a theme park.', offset: 165 },
    { id: 'q11b', text: 'experiencing a river trip.', offset: 188 },
    { id: 'q11c', text: 'visiting a cheese factory.', offset: 215 },
    { id: 'q12', text: 'What will happen in Stanthorpe to mark the 25th anniversary of the Twinning Association?', offset: 242 },
    { id: 'q12a', text: 'A tree will be planted.', offset: 332 },
    { id: 'q12b', text: 'A garden seat will be bought.', offset: 356 },
    { id: 'q12c', text: 'A footbridge will be built.', offset: 386 },
    { id: 'q13', text: 'Which event raised most funds this year?', offset: 414 },
    { id: 'q13a', text: 'the film show', offset: 455 },
    { id: 'q13b', text: 'the pancake evening', offset: 469 },
    { id: 'q13c', text: 'the cookery demonstration', offset: 489 },
    { id: 'q14', text: 'For the first evening with the French visitors host families are advised to', offset: 515 },
    { id: 'q14a', text: 'take them for a walk round the town.', offset: 591 },
    { id: 'q14b', text: 'go to a local restaurant.', offset: 628 },
    { id: 'q14c', text: 'have a meal at home.', offset: 654 },
    { id: 'q15', text: 'On Saturday evening there will be the chance to', offset: 675 },
    { id: 'q15a', text: 'listen to a concert.', offset: 723 },
    { id: 'q15b', text: 'watch a match.', offset: 744 },
    { id: 'q15c', text: 'take part in a competition.', offset: 759 },
    { id: 'questions2', text: 'Questions 16 - 20', offset: 787 },
    { id: 'instruction2', text: 'Label the map below.', offset: 805 },
    { id: 'instruction3', text: 'Write the correct letter, A-H, next to Questions 16-20.', offset: 826 },
    { id: 'title2', text: 'Farley House', offset: 882 },
    { id: 'q16', text: 'Farm shop', offset: 895 },
    { id: 'q17', text: 'Disabled entry', offset: 905 },
    { id: 'q18', text: 'Adventure playground', offset: 920 },
    { id: 'q19', text: 'Kitchen gardens', offset: 941 },
    { id: 'q20', text: 'The Temple of the Four Winds', offset: 957 },
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
    const el = document.querySelector(`[data-question="${questionNumber}"]`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('ring-4', 'ring-purple-400', 'ring-offset-2');
        setTimeout(() => el.classList.remove('ring-4', 'ring-purple-400', 'ring-offset-2'), 2000);
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
            class="w-full rounded-2xl border border-pink-100/50 bg-gradient-to-br from-white via-pink-50/30 to-purple-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Animated Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-pink-200/50 bg-gradient-to-r from-pink-50 to-purple-50 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 animate-pulse items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 shadow-lg shadow-pink-500/30"
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
                        <h2 class="text-lg font-bold tracking-widest text-pink-700 uppercase">
                            <span data-segment-id="header" v-html="getHighlightedSegment('PART 2', 'header')"></span>
                        </h2>
                        <h1 class="text-base font-semibold text-gray-700">
                            <span data-segment-id="questions1" v-html="getHighlightedSegment('Questions 11 - 15', 'questions1')"></span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Section 1: Multiple Choice Questions 11-15 -->
            <div class="mb-8">
                <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-pink-100 to-purple-100 px-4 py-2 shadow-sm">
                    <svg class="h-4 w-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                    <span
                        data-segment-id="instruction1"
                        class="text-sm font-semibold text-pink-800"
                        v-html="getHighlightedSegment('Choose the correct letter, A, B or C.', 'instruction1')"
                    ></span>
                </div>

                <div class="mb-6 text-center">
                    <h3
                        class="inline-block border-b-2 border-pink-300 bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text pb-2 text-xl font-bold text-transparent"
                    >
                        <span data-segment-id="title1" v-html="getHighlightedSegment('Stanthorpe Twinning Association', 'title1')"></span>
                    </h3>
                </div>

                <div class="space-y-5">
                    <!-- Question 11 -->
                    <div
                        data-question="11"
                        class="group rounded-xl border border-pink-100/50 bg-gradient-to-r from-pink-50/80 to-purple-50/80 p-5 shadow-sm transition-all duration-300 hover:border-pink-200 hover:shadow-md"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-sm font-bold text-white shadow-lg shadow-pink-500/20"
                                >11</span
                            >
                            <span
                                data-segment-id="q11"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('During the visit to Malatte, in France, members especially enjoyed', 'q11')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q11"
                                    value="A"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">A</span>
                                <span
                                    data-segment-id="q11a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('going to a theme park.', 'q11a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q11"
                                    value="B"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">B</span>
                                <span
                                    data-segment-id="q11b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('experiencing a river trip.', 'q11b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q11"
                                    value="C"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">C</span>
                                <span
                                    data-segment-id="q11c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('visiting a cheese factory.', 'q11c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 12 -->
                    <div
                        data-question="12"
                        class="group rounded-xl border border-pink-100/50 bg-gradient-to-r from-pink-50/80 to-purple-50/80 p-5 shadow-sm transition-all duration-300 hover:border-pink-200 hover:shadow-md"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-sm font-bold text-white shadow-lg shadow-pink-500/20"
                                >12</span
                            >
                            <span
                                data-segment-id="q12"
                                class="leading-relaxed"
                                v-html="
                                    getHighlightedSegment(
                                        'What will happen in Stanthorpe to mark the 25th anniversary of the Twinning Association?',
                                        'q12',
                                    )
                                "
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q12"
                                    value="A"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">A</span>
                                <span
                                    data-segment-id="q12a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('A tree will be planted.', 'q12a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q12"
                                    value="B"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">B</span>
                                <span
                                    data-segment-id="q12b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('A garden seat will be bought.', 'q12b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q12"
                                    value="C"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">C</span>
                                <span
                                    data-segment-id="q12c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('A footbridge will be built.', 'q12c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 13 -->
                    <div
                        data-question="13"
                        class="group rounded-xl border border-pink-100/50 bg-gradient-to-r from-pink-50/80 to-purple-50/80 p-5 shadow-sm transition-all duration-300 hover:border-pink-200 hover:shadow-md"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-sm font-bold text-white shadow-lg shadow-pink-500/20"
                                >13</span
                            >
                            <span
                                data-segment-id="q13"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('Which event raised most funds this year?', 'q13')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q13"
                                    value="A"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">A</span>
                                <span
                                    data-segment-id="q13a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('the film show', 'q13a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q13"
                                    value="B"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">B</span>
                                <span
                                    data-segment-id="q13b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('the pancake evening', 'q13b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q13"
                                    value="C"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">C</span>
                                <span
                                    data-segment-id="q13c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('the cookery demonstration', 'q13c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 14 -->
                    <div
                        data-question="14"
                        class="group rounded-xl border border-pink-100/50 bg-gradient-to-r from-pink-50/80 to-purple-50/80 p-5 shadow-sm transition-all duration-300 hover:border-pink-200 hover:shadow-md"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-sm font-bold text-white shadow-lg shadow-pink-500/20"
                                >14</span
                            >
                            <span
                                data-segment-id="q14"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('For the first evening with the French visitors host families are advised to', 'q14')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q14"
                                    value="A"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">A</span>
                                <span
                                    data-segment-id="q14a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('take them for a walk round the town.', 'q14a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q14"
                                    value="B"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">B</span>
                                <span
                                    data-segment-id="q14b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('go to a local restaurant.', 'q14b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q14"
                                    value="C"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">C</span>
                                <span
                                    data-segment-id="q14c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('have a meal at home.', 'q14c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 15 -->
                    <div
                        data-question="15"
                        class="group rounded-xl border border-pink-100/50 bg-gradient-to-r from-pink-50/80 to-purple-50/80 p-5 shadow-sm transition-all duration-300 hover:border-pink-200 hover:shadow-md"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-sm font-bold text-white shadow-lg shadow-pink-500/20"
                                >15</span
                            >
                            <span
                                data-segment-id="q15"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('On Saturday evening there will be the chance to', 'q15')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q15"
                                    value="A"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">A</span>
                                <span
                                    data-segment-id="q15a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('listen to a concert.', 'q15a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q15"
                                    value="B"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">B</span>
                                <span
                                    data-segment-id="q15b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('watch a match.', 'q15b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-pink-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q15"
                                    value="C"
                                    class="h-5 w-5 text-pink-600 transition-all focus:ring-pink-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-pink-700">C</span>
                                <span
                                    data-segment-id="q15c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('take part in a competition.', 'q15c')"
                                ></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Map Labeling Questions 16-20 -->
            <div class="mt-8 border-t-2 border-dashed border-pink-200 pt-8">
                <div class="mb-6">
                    <h1 class="mb-2 text-lg font-bold text-gray-900">
                        <span data-segment-id="questions2" v-html="getHighlightedSegment('Questions 16 - 20', 'questions2')"></span>
                    </h1>
                    <p class="mb-1 text-sm text-gray-600">
                        <span data-segment-id="instruction2" v-html="getHighlightedSegment('Label the map below.', 'instruction2')"></span>
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-100 to-fuchsia-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="instruction3"
                            class="text-sm font-semibold text-purple-800"
                            v-html="getHighlightedSegment('Write the correct letter, A-H, next to Questions 16-20.', 'instruction3')"
                        ></span>
                    </div>
                </div>

                <div class="mb-6 text-center">
                    <h3
                        class="inline-block border-b-2 border-purple-300 bg-gradient-to-r from-purple-600 to-fuchsia-600 bg-clip-text pb-2 text-xl font-bold text-transparent"
                    >
                        <span data-segment-id="title2" v-html="getHighlightedSegment('Farley House', 'title2')"></span>
                    </h3>
                </div>

                <!-- Map Image -->
                <div class="mb-6 rounded-2xl border border-purple-100 bg-gradient-to-br from-gray-50 to-purple-50/30 p-6 shadow-inner">
                    <div class="relative mx-auto max-w-2xl">
                        <img
                            src="/images/Practice/C19P1.png"
                            alt="Farley House Map"
                            class="h-auto w-full rounded-xl shadow-lg shadow-purple-200/20"
                        />
                    </div>
                </div>

                <!-- Map Questions with Select Dropdowns -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div
                        data-question="16"
                        class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-fuchsia-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md"
                    >
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 font-bold text-white shadow-lg shadow-purple-500/20 transition-transform group-hover:scale-110"
                            >16</span
                        >
                        <span
                            data-segment-id="q16"
                            class="flex-1 font-medium text-gray-800"
                            v-html="getHighlightedSegment('Farm shop', 'q16')"
                        ></span>
                        <select
                            v-model="answers.q16"
                            class="h-11 w-20 cursor-pointer appearance-none rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                        >
                            <option value="" disabled>?</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                            <option value="G">G</option>
                            <option value="H">H</option>
                        </select>
                    </div>

                    <div
                        data-question="17"
                        class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-fuchsia-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md"
                    >
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 font-bold text-white shadow-lg shadow-purple-500/20 transition-transform group-hover:scale-110"
                            >17</span
                        >
                        <span
                            data-segment-id="q17"
                            class="flex-1 font-medium text-gray-800"
                            v-html="getHighlightedSegment('Disabled entry', 'q17')"
                        ></span>
                        <select
                            v-model="answers.q17"
                            class="h-11 w-20 cursor-pointer appearance-none rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                        >
                            <option value="" disabled>?</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                            <option value="G">G</option>
                            <option value="H">H</option>
                        </select>
                    </div>

                    <div
                        data-question="18"
                        class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-fuchsia-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md"
                    >
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 font-bold text-white shadow-lg shadow-purple-500/20 transition-transform group-hover:scale-110"
                            >18</span
                        >
                        <span
                            data-segment-id="q18"
                            class="flex-1 font-medium text-gray-800"
                            v-html="getHighlightedSegment('Adventure playground', 'q18')"
                        ></span>
                        <select
                            v-model="answers.q18"
                            class="h-11 w-20 cursor-pointer appearance-none rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                        >
                            <option value="" disabled>?</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                            <option value="G">G</option>
                            <option value="H">H</option>
                        </select>
                    </div>

                    <div
                        data-question="19"
                        class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-fuchsia-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md"
                    >
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 font-bold text-white shadow-lg shadow-purple-500/20 transition-transform group-hover:scale-110"
                            >19</span
                        >
                        <span
                            data-segment-id="q19"
                            class="flex-1 font-medium text-gray-800"
                            v-html="getHighlightedSegment('Kitchen gardens', 'q19')"
                        ></span>
                        <select
                            v-model="answers.q19"
                            class="h-11 w-20 cursor-pointer appearance-none rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                        >
                            <option value="" disabled>?</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                            <option value="G">G</option>
                            <option value="H">H</option>
                        </select>
                    </div>

                    <div
                        data-question="20"
                        class="group flex items-center gap-4 rounded-xl border border-purple-100 bg-gradient-to-r from-purple-50 to-fuchsia-50 p-4 shadow-sm transition-all duration-300 hover:border-purple-200 hover:shadow-md md:col-span-2 md:max-w-lg"
                    >
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 font-bold text-white shadow-lg shadow-purple-500/20 transition-transform group-hover:scale-110"
                            >20</span
                        >
                        <span
                            data-segment-id="q20"
                            class="flex-1 font-medium text-gray-800"
                            v-html="getHighlightedSegment('The Temple of the Four Winds', 'q20')"
                        ></span>
                        <select
                            v-model="answers.q20"
                            class="h-11 w-20 cursor-pointer appearance-none rounded-xl border-2 border-purple-300 bg-white text-center text-lg font-bold text-purple-700 shadow-sm transition-all hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
                        >
                            <option value="" disabled>?</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                            <option value="G">G</option>
                            <option value="H">H</option>
                        </select>
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
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-blue-200 bg-blue-50 transition-all duration-200 hover:scale-110 hover:bg-blue-100 hover:shadow-md"
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
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-blue-300 bg-white p-4 shadow-2xl"
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
                            class="note-input-field w-full rounded-lg border-2 border-blue-200 px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
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
                            class="rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-blue-500/20 transition-all hover:from-blue-700 hover:to-indigo-700"
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

/* Radio button custom styling */
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
    border-color: #a855f7;
    background: #a855f7;
    box-shadow: inset 0 0 0 3px white;
}
input[type='radio']:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
}

/* Custom select dropdown styling */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23a855f7' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

select:focus {
    outline: none;
}

select option {
    font-weight: 600;
    padding: 0.5rem;
}

/* Animation for select when value changes */
select:not(:invalid) {
    animation: selectPop 0.2s ease-out;
}

@keyframes selectPop {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}
</style>
