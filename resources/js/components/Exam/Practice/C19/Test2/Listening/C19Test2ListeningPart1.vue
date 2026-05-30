<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Props
interface Props {
    correctAnswers?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    correctAnswers: () => ({}),
});

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
    { id: 'instruction1', text: 'Complete the form below.', offset: 24 },
    { id: 'instruction2', text: 'Write ONE WORD AND/OR A NUMBER for each answer.', offset: 49 },
    { id: 'title', text: 'Guitar Group', offset: 97 },
    { id: 'coordinator', text: 'Coordinator:', offset: 110 },
    { id: 'gary', text: 'Gary', offset: 123 },
    { id: 'level', text: 'Level:', offset: 128 },
    { id: 'place', text: 'Place:', offset: 135 },
    { id: 'the', text: 'the', offset: 142 },
    { id: 'street', text: 'Street', offset: 146 },
    { id: 'floor', text: 'First floor, Room T347', offset: 153 },
    { id: 'time', text: 'Time:', offset: 176 },
    { id: 'thursday', text: 'Thursday morning at', offset: 182 },
    { id: 'website', text: 'Recommended website:', offset: 202 },
    { id: 'perfect', text: "'The perfect", offset: 223 },
    { id: 'tableTitle', text: 'A typical 45-minute guitar lesson', offset: 236 },
    { id: 'timeCol', text: 'Time', offset: 270 },
    { id: 'activityCol', text: 'Activity', offset: 275 },
    { id: 'notesCol', text: 'Notes', offset: 284 },
    { id: 'time1', text: '5 minutes', offset: 290 },
    { id: 'activity1', text: 'tuning guitars', offset: 300 },
    { id: 'notes1', text: 'using an app or by', offset: 315 },
    { id: 'time2', text: '10 minutes', offset: 334 },
    { id: 'activity2', text: 'strumming chords using our thumbs', offset: 345 },
    { id: 'notes2', text: 'keeping time while the teacher is', offset: 379 },
    { id: 'time3', text: '15 minutes', offset: 413 },
    { id: 'activity3', text: 'playing songs', offset: 424 },
    { id: 'notes3', text: 'often listening to a', offset: 438 },
    { id: 'notes3b', text: 'of a song', offset: 459 },
    { id: 'time4', text: '10 minutes', offset: 469 },
    { id: 'activity4', text: 'playing single notes and simple tunes', offset: 480 },
    { id: 'notes4', text: 'playing together, then', offset: 518 },
    { id: 'time5', text: '5 minutes', offset: 541 },
    { id: 'activity5', text: 'noting things to practise at home', offset: 551 },
]);

// Get highlighted segment with multiline support
const getHighlightedSegment = (segmentText: string, segmentId?: string) => {
    const segment = segmentId ? textSegments.value.find((s) => s.id === segmentId) : textSegments.value.find((s) => s.text === segmentText);

    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;

    // Find highlights that overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    if (overlappingHighlights.length === 0) {
        return segmentText;
    }

    // Sort highlights by start position (descending for safe replacement)
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

        // Calculate absolute offset across segments
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

// Scroll to question functionality
const scrollToQuestion = (questionNumber: number) => {
    const inputEl = document.querySelector(`input[data-question="${questionNumber}"]`);
    if (inputEl) {
        inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        (inputEl as HTMLInputElement).focus();
        inputEl.classList.add('ring-4', 'ring-cyan-400', 'ring-offset-2');
        setTimeout(() => inputEl.classList.remove('ring-4', 'ring-cyan-400', 'ring-offset-2'), 2000);
    }
};

// Lifecycle
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

// Expose
defineExpose({
    getAnswers: () => answers.value,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" class="animate-fadeIn mx-auto mb-16 px-4 py-8">
        <div
            class="w-full rounded-2xl border border-cyan-100/50 bg-gradient-to-br from-white via-cyan-50/30 to-teal-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-cyan-200/50 bg-gradient-to-r from-cyan-50 to-teal-50 p-4 lg:p-6">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center space-x-3">
                        <div
                            class="flex h-10 w-10 animate-pulse items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500 to-teal-600 shadow-lg shadow-cyan-500/30"
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
                            <h2 class="text-lg font-bold tracking-widest text-cyan-700 uppercase">
                                <span data-segment-id="header" v-html="getHighlightedSegment('PART 1', 'header')"></span>
                            </h2>
                            <h1 class="text-base font-semibold text-gray-700">
                                <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 1 - 10', 'questions')"></span>
                            </h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="mb-6 border-b-2 border-dashed border-cyan-200 pb-6">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-cyan-100 to-teal-100 px-4 py-2 shadow-sm">
                    <svg class="h-4 w-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        ></path>
                    </svg>
                    <span
                        data-segment-id="instruction1"
                        class="text-sm text-cyan-800"
                        v-html="getHighlightedSegment('Complete the form below.', 'instruction1')"
                    ></span>
                </div>
                <p class="font-bold text-cyan-800">
                    <span
                        data-segment-id="instruction2"
                        v-html="getHighlightedSegment('Write ONE WORD AND/OR A NUMBER for each answer.', 'instruction2')"
                    ></span>
                </p>
            </div>

            <!-- Title -->
            <h3 class="mb-6 bg-gradient-to-r from-cyan-600 to-teal-600 bg-clip-text text-center text-2xl font-bold text-transparent">
                <span data-segment-id="title" v-html="getHighlightedSegment('Guitar Group', 'title')"></span>
            </h3>

            <!-- Form Section -->
            <div class="animate-slideUp mb-8 rounded-xl border border-cyan-100 bg-white/80 p-6 shadow-lg">
                <div class="space-y-5">
                    <!-- Coordinator -->
                    <div class="flex flex-wrap items-center gap-3 rounded-lg p-3 transition-colors hover:bg-cyan-50/50">
                        <span
                            data-segment-id="coordinator"
                            class="min-w-32 font-semibold text-gray-700"
                            v-html="getHighlightedSegment('Coordinator:', 'coordinator')"
                        ></span>
                        <span data-segment-id="gary" class="text-gray-700" v-html="getHighlightedSegment('Gary', 'gary')"></span>
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white shadow-md"
                            >1</span
                        >
                        <input
                            v-model="answers.q1"
                            data-question="1"
                            type="text"
                            class="w-32 rounded-lg border-2 border-cyan-300 p-2 text-sm shadow-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                            placeholder="......"
                        />
                    </div>

                    <!-- Level -->
                    <div class="flex flex-wrap items-center gap-3 rounded-lg p-3 transition-colors hover:bg-cyan-50/50">
                        <span
                            data-segment-id="level"
                            class="min-w-32 font-semibold text-gray-700"
                            v-html="getHighlightedSegment('Level:', 'level')"
                        ></span>
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white shadow-md"
                            >2</span
                        >
                        <input
                            v-model="answers.q2"
                            data-question="2"
                            type="text"
                            class="w-32 rounded-lg border-2 border-cyan-300 p-2 text-sm shadow-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                            placeholder="......"
                        />
                    </div>

                    <!-- Place -->
                    <div class="flex flex-wrap items-center gap-3 rounded-lg p-3 transition-colors hover:bg-cyan-50/50">
                        <span
                            data-segment-id="place"
                            class="min-w-32 font-semibold text-gray-700"
                            v-html="getHighlightedSegment('Place:', 'place')"
                        ></span>
                        <div class="flex flex-col gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span data-segment-id="the" class="text-gray-700" v-html="getHighlightedSegment('the', 'the')"></span>
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white shadow-md"
                                    >3</span
                                >
                                <input
                                    v-model="answers.q3"
                                    data-question="3"
                                    type="text"
                                    class="w-32 rounded-lg border-2 border-cyan-300 p-2 text-sm shadow-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                                    placeholder="......"
                                />
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white shadow-md"
                                    >4</span
                                >
                                <input
                                    v-model="answers.q4"
                                    data-question="4"
                                    type="text"
                                    class="w-32 rounded-lg border-2 border-cyan-300 p-2 text-sm shadow-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                                    placeholder="......"
                                />
                                <span data-segment-id="street" class="text-gray-700" v-html="getHighlightedSegment('Street', 'street')"></span>
                            </div>
                            <span
                                data-segment-id="floor"
                                class="ml-2 text-sm text-gray-600"
                                v-html="getHighlightedSegment('First floor, Room T347', 'floor')"
                            ></span>
                        </div>
                    </div>

                    <!-- Time -->
                    <div class="flex flex-wrap items-center gap-3 rounded-lg p-3 transition-colors hover:bg-cyan-50/50">
                        <span
                            data-segment-id="time"
                            class="min-w-32 font-semibold text-gray-700"
                            v-html="getHighlightedSegment('Time:', 'time')"
                        ></span>
                        <span
                            data-segment-id="thursday"
                            class="text-gray-700"
                            v-html="getHighlightedSegment('Thursday morning at', 'thursday')"
                        ></span>
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white shadow-md"
                            >5</span
                        >
                        <input
                            v-model="answers.q5"
                            data-question="5"
                            type="text"
                            class="w-24 rounded-lg border-2 border-cyan-300 p-2 text-sm shadow-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                            placeholder="......"
                        />
                    </div>

                    <!-- Website -->
                    <div class="flex flex-wrap items-center gap-3 rounded-lg p-3 transition-colors hover:bg-cyan-50/50">
                        <span
                            data-segment-id="website"
                            class="min-w-32 font-semibold text-gray-700"
                            v-html="getHighlightedSegment('Recommended website:', 'website')"
                        ></span>
                        <span data-segment-id="perfect" class="text-gray-700" v-html="getHighlightedSegment('\'The perfect', 'perfect')"></span>
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white shadow-md"
                            >6</span
                        >
                        <input
                            v-model="answers.q6"
                            data-question="6"
                            type="text"
                            class="w-32 rounded-lg border-2 border-cyan-300 p-2 text-sm shadow-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200"
                            placeholder="......"
                        />
                        <span class="text-gray-700">'</span>
                    </div>
                </div>
            </div>

            <!-- Questions 7-10: Table Section -->
            <div class="mb-6 border-t-2 border-dashed border-cyan-200 pt-6">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-100 to-cyan-100 px-4 py-2 shadow-sm">
                    <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        ></path>
                    </svg>
                    <span class="text-sm font-medium text-teal-800">Complete the table below.</span>
                </div>
                <p class="mb-4 font-bold text-teal-800">Write ONE WORD ONLY for each answer.</p>
            </div>

            <h4 class="mb-4 bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-center text-lg font-bold text-transparent">
                <span data-segment-id="tableTitle" v-html="getHighlightedSegment('A typical 45-minute guitar lesson', 'tableTitle')"></span>
            </h4>

            <!-- Table -->
            <div class="animate-slideUp overflow-x-auto rounded-xl border-2 border-teal-200 shadow-lg" style="animation-delay: 0.2s">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-teal-500 to-cyan-500 text-white">
                            <th class="px-4 py-3 text-left font-bold">
                                <span data-segment-id="timeCol" v-html="getHighlightedSegment('Time', 'timeCol')"></span>
                            </th>
                            <th class="px-4 py-3 text-left font-bold">
                                <span data-segment-id="activityCol" v-html="getHighlightedSegment('Activity', 'activityCol')"></span>
                            </th>
                            <th class="px-4 py-3 text-left font-bold">
                                <span data-segment-id="notesCol" v-html="getHighlightedSegment('Notes', 'notesCol')"></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Row 1 -->
                        <tr class="border-b border-teal-100 bg-white transition-colors hover:bg-teal-50/50">
                            <td class="px-4 py-4 font-medium text-gray-700">
                                <span data-segment-id="time1" v-html="getHighlightedSegment('5 minutes', 'time1')"></span>
                            </td>
                            <td class="px-4 py-4 text-gray-700">
                                <span data-segment-id="activity1" v-html="getHighlightedSegment('tuning guitars', 'activity1')"></span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        data-segment-id="notes1"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('using an app or by', 'notes1')"
                                    ></span>
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-md"
                                        >7</span
                                    >
                                    <input
                                        v-model="answers.q7"
                                        data-question="7"
                                        type="text"
                                        class="w-28 rounded-lg border-2 border-teal-300 p-2 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                                        placeholder="......"
                                    />
                                </div>
                            </td>
                        </tr>
                        <!-- Row 2 -->
                        <tr class="border-b border-teal-100 bg-teal-50/30 transition-colors hover:bg-teal-50">
                            <td class="px-4 py-4 font-medium text-gray-700">
                                <span data-segment-id="time2" v-html="getHighlightedSegment('10 minutes', 'time2')"></span>
                            </td>
                            <td class="px-4 py-4 text-gray-700">
                                <span
                                    data-segment-id="activity2"
                                    v-html="getHighlightedSegment('strumming chords using our thumbs', 'activity2')"
                                ></span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        data-segment-id="notes2"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('keeping time while the teacher is', 'notes2')"
                                    ></span>
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-md"
                                        >8</span
                                    >
                                    <input
                                        v-model="answers.q8"
                                        data-question="8"
                                        type="text"
                                        class="w-28 rounded-lg border-2 border-teal-300 p-2 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                                        placeholder="......"
                                    />
                                </div>
                            </td>
                        </tr>
                        <!-- Row 3 -->
                        <tr class="border-b border-teal-100 bg-white transition-colors hover:bg-teal-50/50">
                            <td class="px-4 py-4 font-medium text-gray-700">
                                <span data-segment-id="time3" v-html="getHighlightedSegment('15 minutes', 'time3')"></span>
                            </td>
                            <td class="px-4 py-4 text-gray-700">
                                <span data-segment-id="activity3" v-html="getHighlightedSegment('playing songs', 'activity3')"></span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        data-segment-id="notes3"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('often listening to a', 'notes3')"
                                    ></span>
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-md"
                                        >9</span
                                    >
                                    <input
                                        v-model="answers.q9"
                                        data-question="9"
                                        type="text"
                                        class="w-28 rounded-lg border-2 border-teal-300 p-2 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                                        placeholder="......"
                                    />
                                    <span
                                        data-segment-id="notes3b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('of a song', 'notes3b')"
                                    ></span>
                                </div>
                            </td>
                        </tr>
                        <!-- Row 4 -->
                        <tr class="border-b border-teal-100 bg-teal-50/30 transition-colors hover:bg-teal-50">
                            <td class="px-4 py-4 font-medium text-gray-700">
                                <span data-segment-id="time4" v-html="getHighlightedSegment('10 minutes', 'time4')"></span>
                            </td>
                            <td class="px-4 py-4 text-gray-700">
                                <span
                                    data-segment-id="activity4"
                                    v-html="getHighlightedSegment('playing single notes and simple tunes', 'activity4')"
                                ></span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        data-segment-id="notes4"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('playing together, then', 'notes4')"
                                    ></span>
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-md"
                                        >10</span
                                    >
                                    <input
                                        v-model="answers.q10"
                                        data-question="10"
                                        type="text"
                                        class="w-28 rounded-lg border-2 border-teal-300 p-2 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-200"
                                        placeholder="......"
                                    />
                                </div>
                            </td>
                        </tr>
                        <!-- Row 5 -->
                        <tr class="bg-white transition-colors hover:bg-teal-50/50">
                            <td class="px-4 py-4 font-medium text-gray-700">
                                <span data-segment-id="time5" v-html="getHighlightedSegment('5 minutes', 'time5')"></span>
                            </td>
                            <td class="px-4 py-4 text-gray-700" colspan="2">
                                <span
                                    data-segment-id="activity5"
                                    v-html="getHighlightedSegment('noting things to practise at home', 'activity5')"
                                ></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                >
                    <div
                        class="context-menu pointer-events-auto rounded-xl border border-cyan-200 bg-white/95 p-3 shadow-2xl backdrop-blur-sm"
                        @click.stop
                    >
                        <div class="flex items-center gap-2">
                            <button
                                v-for="color in colors"
                                :key="color.name"
                                @click="applyHighlight(color.name)"
                                :class="[
                                    color.class,
                                    'h-9 w-9 rounded-lg border-2 border-gray-200 transition-all duration-200 hover:scale-110 hover:border-cyan-400 hover:shadow-md',
                                ]"
                                :title="`Highlight ${color.name}`"
                            ></button>
                            <div class="mx-1 h-6 w-px bg-gray-200"></div>
                            <button
                                @click="openNoteInput"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-cyan-200 bg-cyan-50 transition-all duration-200 hover:scale-110 hover:bg-cyan-100 hover:shadow-md"
                                title="Add Note"
                            >
                                <svg class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-cyan-300 bg-white p-4 shadow-2xl"
                    :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="mb-3">
                        <p class="mb-3 line-clamp-2 rounded-lg border border-cyan-200 bg-cyan-50 p-3 text-sm text-gray-600 italic">
                            "{{ selectedText }}"
                        </p>
                        <input
                            v-model="noteInputText"
                            type="text"
                            placeholder="Write your note here..."
                            class="note-input-field w-full rounded-lg border-2 border-cyan-200 px-4 py-3 text-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 focus:outline-none"
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
                            class="rounded-lg bg-gradient-to-r from-cyan-600 to-teal-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-cyan-500/20 transition-all hover:from-cyan-700 hover:to-teal-700"
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
    animation: fadeIn 0.5s ease-out forwards;
}

.animate-slideUp {
    opacity: 0;
    animation: slideUp 0.6s ease-out forwards;
}
</style>
