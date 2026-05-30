<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
    { id: 'instruction1', text: 'Questions 1-6', offset: 24 },
    { id: 'instruction2', text: 'Complete the notes below.', offset: 38 },
    { id: 'instruction3', text: 'Write ONE WORD AND/OR A NUMBER for each answer.', offset: 64 },
    { id: 'title', text: 'First day at work', offset: 112 },
    { id: 'supervisor', text: 'Name of supervisor:', offset: 130 },
    { id: 'coat', text: 'Where to leave coat and bag:', offset: 150 },
    { id: 'use', text: 'use', offset: 179 },
    { id: 'staffroom', text: 'in staffroom', offset: 183 },
    { id: 'tiffany', text: 'See Tiffany in HR:', offset: 196 },
    { id: 'togive', text: 'to give', offset: 215 },
    { id: 'number', text: 'number', offset: 223 },
    { id: 'tocollect', text: 'to collect', offset: 230 },
    { id: 'location', text: 'Location of HR office:', offset: 241 },
    { id: 'on', text: 'on', offset: 264 },
    { id: 'floor', text: 'floor', offset: 267 },
    { id: 'mobile', text: "Supervisor's mobile number:", offset: 273 },
    { id: 'q7title', text: 'Questions 7-10', offset: 301 },
    { id: 'tableinstruction', text: 'Complete the table below.', offset: 316 },
    { id: 'tableinstruction2', text: 'Write ONE WORD ONLY for each answer.', offset: 342 },
    { id: 'responsibilities', text: 'Responsibilities', offset: 379 },
    { id: 'bakery', text: 'Bakery section', offset: 396 },
    { id: 'bakerytask1', text: 'Check sell-by dates', offset: 411 },
    { id: 'bakerytask2', text: 'Change price labels', offset: 431 },
    { id: 'bakerynote', text: 'Use', offset: 451 },
    { id: 'bakerynote2', text: 'labels', offset: 455 },
    { id: 'sushi', text: 'Sushi takeaway counter', offset: 462 },
    { id: 'sushitask1', text: 'Re-stock with', offset: 485 },
    { id: 'sushitask1b', text: 'boxes if needed', offset: 499 },
    { id: 'sushitask2', text: 'Wipe preparation area and clean the sink', offset: 515 },
    { id: 'sushinote', text: 'Do not clean any knives', offset: 556 },
    { id: 'meat', text: 'Meat and fish counters', offset: 580 },
    { id: 'meattask1', text: 'Clean the serving area, including the weighing scales', offset: 603 },
    { id: 'meattask2', text: 'Collect', offset: 657 },
    { id: 'meattask2b', text: 'for the fish from the cold-room', offset: 665 },
    { id: 'meatnote', text: 'Must wear special', offset: 697 },
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

        const rect = range.getBoundingClientRect();
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
    <div ref="contentTextRef" class="mx-auto mb-16 px-4 py-8">
        <div
            class="animate-fadeIn w-full rounded-2xl border border-teal-100/50 bg-gradient-to-br from-white via-teal-50/30 to-cyan-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-teal-200/50 bg-gradient-to-r from-teal-50 to-cyan-50 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="animate-pulse-slow flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-cyan-600 shadow-lg shadow-teal-500/30"
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
                            class="bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-xl font-bold tracking-widest text-transparent uppercase"
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
                    <h3 class="mb-2 text-lg font-bold text-teal-800">
                        <span data-segment-id="instruction1" v-html="getHighlightedSegment('Questions 1-6', 'instruction1')"></span>
                    </h3>
                    <p class="mb-1 text-sm text-gray-600">
                        <span data-segment-id="instruction2" v-html="getHighlightedSegment('Complete the notes below.', 'instruction2')"></span>
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-100 to-cyan-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="instruction3"
                            class="text-sm font-semibold text-teal-800"
                            v-html="getHighlightedSegment('Write ONE WORD AND/OR A NUMBER for each answer.', 'instruction3')"
                        ></span>
                    </div>
                </div>

                <!-- First day at work Card -->
                <div class="rounded-2xl border border-teal-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <h3
                        class="mb-6 border-b-2 border-teal-200 bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text pb-2 text-center text-2xl font-bold text-transparent"
                    >
                        <span data-segment-id="title" v-html="getHighlightedSegment('First day at work', 'title')"></span>
                    </h3>

                    <div class="space-y-4">
                        <!-- Question 1 -->
                        <div
                            data-question="1"
                            class="group flex items-center gap-3 rounded-xl bg-gradient-to-r from-teal-50/80 to-cyan-50/80 p-4 transition-all duration-300 hover:from-teal-100/80 hover:to-cyan-100/80"
                        >
                            <span class="font-bold text-teal-600">*</span>
                            <span
                                data-segment-id="supervisor"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Name of supervisor:', 'supervisor')"
                            ></span>
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >1</span
                            >
                            <input
                                v-model="answers.q1"
                                data-question="1"
                                type="text"
                                class="max-w-40 flex-1 rounded-xl border-2 border-teal-300 p-2.5 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                placeholder="......"
                            />
                        </div>

                        <!-- Question 2 -->
                        <div
                            data-question="2"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-teal-50/80 to-cyan-50/80 p-4 transition-all duration-300 hover:from-teal-100/80 hover:to-cyan-100/80"
                        >
                            <span class="font-bold text-teal-600">*</span>
                            <span
                                data-segment-id="coat"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Where to leave coat and bag:', 'coat')"
                            ></span>
                            <span data-segment-id="use" class="text-gray-700" v-html="getHighlightedSegment('use', 'use')"></span>
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >2</span
                            >
                            <input
                                v-model="answers.q2"
                                data-question="2"
                                type="text"
                                class="w-28 rounded-xl border-2 border-teal-300 p-2.5 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                placeholder="......"
                            />
                            <span
                                data-segment-id="staffroom"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('in staffroom', 'staffroom')"
                            ></span>
                        </div>

                        <!-- Question 3 & 4 -->
                        <div
                            class="group rounded-xl bg-gradient-to-r from-teal-50/80 to-cyan-50/80 p-4 transition-all duration-300 hover:from-teal-100/80 hover:to-cyan-100/80"
                        >
                            <div class="mb-3 flex items-center gap-2">
                                <span class="font-bold text-teal-600">*</span>
                                <span
                                    data-segment-id="tiffany"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('See Tiffany in HR:', 'tiffany')"
                                ></span>
                            </div>
                            <div class="ml-6 space-y-3">
                                <div data-question="3" class="flex flex-wrap items-center gap-3">
                                    <span data-segment-id="togive" class="text-gray-600" v-html="getHighlightedSegment('to give', 'togive')"></span>
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                        >3</span
                                    >
                                    <input
                                        v-model="answers.q3"
                                        data-question="3"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-teal-300 p-2.5 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                        placeholder="......"
                                    />
                                    <span data-segment-id="number" class="text-gray-600" v-html="getHighlightedSegment('number', 'number')"></span>
                                </div>
                                <div data-question="4" class="flex flex-wrap items-center gap-3">
                                    <span
                                        data-segment-id="tocollect"
                                        class="text-gray-600"
                                        v-html="getHighlightedSegment('to collect', 'tocollect')"
                                    ></span>
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                        >4</span
                                    >
                                    <input
                                        v-model="answers.q4"
                                        data-question="4"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-teal-300 p-2.5 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                        placeholder="......"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Question 5 -->
                        <div
                            data-question="5"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-teal-50/80 to-cyan-50/80 p-4 transition-all duration-300 hover:from-teal-100/80 hover:to-cyan-100/80"
                        >
                            <span class="font-bold text-teal-600">*</span>
                            <span
                                data-segment-id="location"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Location of HR office:', 'location')"
                            ></span>
                            <span data-segment-id="on" class="text-gray-700" v-html="getHighlightedSegment('on', 'on')"></span>
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >5</span
                            >
                            <input
                                v-model="answers.q5"
                                data-question="5"
                                type="text"
                                class="w-28 rounded-xl border-2 border-teal-300 p-2.5 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                placeholder="......"
                            />
                            <span data-segment-id="floor" class="text-gray-700" v-html="getHighlightedSegment('floor', 'floor')"></span>
                        </div>

                        <!-- Question 6 -->
                        <div
                            data-question="6"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-teal-50/80 to-cyan-50/80 p-4 transition-all duration-300 hover:from-teal-100/80 hover:to-cyan-100/80"
                        >
                            <span class="font-bold text-teal-600">*</span>
                            <span
                                data-segment-id="mobile"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Supervisor\'s mobile number:', 'mobile')"
                            ></span>
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >6</span
                            >
                            <input
                                v-model="answers.q6"
                                data-question="6"
                                type="text"
                                class="max-w-48 flex-1 rounded-xl border-2 border-teal-300 p-2.5 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                placeholder="......"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions 7-10 Section -->
            <div class="animate-slideUp border-t-2 border-dashed border-teal-200 pt-8" style="animation-delay: 0.2s">
                <div class="mb-6">
                    <h3 class="mb-2 text-lg font-bold text-teal-800">
                        <span data-segment-id="q7title" v-html="getHighlightedSegment('Questions 7-10', 'q7title')"></span>
                    </h3>
                    <p class="mb-1 text-sm text-gray-600">
                        <span
                            data-segment-id="tableinstruction"
                            v-html="getHighlightedSegment('Complete the table below.', 'tableinstruction')"
                        ></span>
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-cyan-100 to-teal-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="tableinstruction2"
                            class="text-sm font-semibold text-cyan-800"
                            v-html="getHighlightedSegment('Write ONE WORD ONLY for each answer.', 'tableinstruction2')"
                        ></span>
                    </div>
                </div>

                <div class="mb-6 text-center">
                    <h3
                        class="inline-block border-b-2 border-cyan-300 bg-gradient-to-r from-cyan-600 to-teal-600 bg-clip-text pb-2 text-2xl font-bold text-transparent"
                    >
                        <span data-segment-id="responsibilities" v-html="getHighlightedSegment('Responsibilities', 'responsibilities')"></span>
                    </h3>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-2xl border border-teal-200 shadow-lg">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-teal-500 to-cyan-600 text-white">
                                <th class="rounded-tl-xl p-4 text-left font-bold">Section</th>
                                <th class="p-4 text-left font-bold">Task 1</th>
                                <th class="p-4 text-left font-bold">Task 2</th>
                                <th class="rounded-tr-xl p-4 text-left font-bold">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Bakery Section -->
                            <tr class="bg-white transition-colors hover:bg-teal-50/50">
                                <td class="border-b border-teal-100 p-4 font-semibold text-teal-800">
                                    <span data-segment-id="bakery" v-html="getHighlightedSegment('Bakery section', 'bakery')"></span>
                                </td>
                                <td class="border-b border-teal-100 p-4 text-gray-700">
                                    <span data-segment-id="bakerytask1" v-html="getHighlightedSegment('Check sell-by dates', 'bakerytask1')"></span>
                                </td>
                                <td class="border-b border-teal-100 p-4 text-gray-700">
                                    <span data-segment-id="bakerytask2" v-html="getHighlightedSegment('Change price labels', 'bakerytask2')"></span>
                                </td>
                                <td data-question="7" class="border-b border-teal-100 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            data-segment-id="bakerynote"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('Use', 'bakerynote')"
                                        ></span>
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-lg"
                                            >7</span
                                        >
                                        <input
                                            v-model="answers.q7"
                                            data-question="7"
                                            type="text"
                                            class="w-24 rounded-lg border-2 border-teal-300 p-2 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                            placeholder="......"
                                        />
                                        <span
                                            data-segment-id="bakerynote2"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('labels', 'bakerynote2')"
                                        ></span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Sushi Section -->
                            <tr class="bg-cyan-50/30 transition-colors hover:bg-teal-50/50">
                                <td class="border-b border-teal-100 p-4 font-semibold text-teal-800">
                                    <span data-segment-id="sushi" v-html="getHighlightedSegment('Sushi takeaway counter', 'sushi')"></span>
                                </td>
                                <td data-question="8" class="border-b border-teal-100 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            data-segment-id="sushitask1"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('Re-stock with', 'sushitask1')"
                                        ></span>
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-lg"
                                            >8</span
                                        >
                                        <input
                                            v-model="answers.q8"
                                            data-question="8"
                                            type="text"
                                            class="w-24 rounded-lg border-2 border-teal-300 p-2 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                            placeholder="......"
                                        />
                                        <span
                                            data-segment-id="sushitask1b"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('boxes if needed', 'sushitask1b')"
                                        ></span>
                                    </div>
                                </td>
                                <td class="border-b border-teal-100 p-4 text-gray-700">
                                    <span
                                        data-segment-id="sushitask2"
                                        v-html="getHighlightedSegment('Wipe preparation area and clean the sink', 'sushitask2')"
                                    ></span>
                                </td>
                                <td class="border-b border-teal-100 p-4 text-gray-600 italic">
                                    <span data-segment-id="sushinote" v-html="getHighlightedSegment('Do not clean any knives', 'sushinote')"></span>
                                </td>
                            </tr>

                            <!-- Meat and Fish Section -->
                            <tr class="bg-white transition-colors hover:bg-teal-50/50">
                                <td class="rounded-bl-xl p-4 font-semibold text-teal-800">
                                    <span data-segment-id="meat" v-html="getHighlightedSegment('Meat and fish counters', 'meat')"></span>
                                </td>
                                <td class="p-4 text-gray-700">
                                    <span
                                        data-segment-id="meattask1"
                                        v-html="getHighlightedSegment('Clean the serving area, including the weighing scales', 'meattask1')"
                                    ></span>
                                </td>
                                <td data-question="9" class="p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            data-segment-id="meattask2"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('Collect', 'meattask2')"
                                        ></span>
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-lg"
                                            >9</span
                                        >
                                        <input
                                            v-model="answers.q9"
                                            data-question="9"
                                            type="text"
                                            class="w-24 rounded-lg border-2 border-teal-300 p-2 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                            placeholder="......"
                                        />
                                        <span
                                            data-segment-id="meattask2b"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('for the fish from the cold-room', 'meattask2b')"
                                        ></span>
                                    </div>
                                </td>
                                <td data-question="10" class="rounded-br-xl p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            data-segment-id="meatnote"
                                            class="text-gray-700"
                                            v-html="getHighlightedSegment('Must wear special', 'meatnote')"
                                        ></span>
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-lg"
                                            >10</span
                                        >
                                        <input
                                            v-model="answers.q10"
                                            data-question="10"
                                            type="text"
                                            class="w-24 rounded-lg border-2 border-teal-300 p-2 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                            placeholder="......"
                                        />
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
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-teal-200 bg-teal-50 transition-all duration-200 hover:scale-110 hover:bg-teal-100 hover:shadow-md"
                                title="Add Note"
                            >
                                <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-teal-300 bg-white p-4 shadow-2xl"
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
                            class="note-input-field w-full rounded-lg border-2 border-teal-200 px-4 py-3 text-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
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
                            class="rounded-lg bg-gradient-to-r from-teal-600 to-cyan-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-teal-500/20 transition-all hover:from-teal-700 hover:to-cyan-700"
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
