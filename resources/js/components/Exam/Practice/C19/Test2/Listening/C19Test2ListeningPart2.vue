<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

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

// Multiple answer handling
const multipleAnswers = reactive({
    q17_18: [] as string[],
    q19_20: [] as string[],
});

// Sync multiple answers to individual answer slots
watch(
    multipleAnswers,
    (newVal) => {
        answers.value.q17 = newVal.q17_18[0] || '';
        answers.value.q18 = newVal.q17_18[1] || '';
        answers.value.q19 = newVal.q19_20[0] || '';
        answers.value.q20 = newVal.q19_20[1] || '';
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

// Text segments with continuous offsets
const textSegments = ref([
    { id: 'header', text: 'PART 2', offset: 0 },
    { id: 'questions1', text: 'Questions 11 - 16', offset: 7 },
    { id: 'instruction1', text: 'Choose the correct letter, A, B or C.', offset: 25 },
    { id: 'title1', text: 'Working as a lifeboat volunteer', offset: 64 },
    { id: 'q11', text: 'What made David leave London and move to Northsea?', offset: 96 },
    { id: 'q11a', text: 'He was eager to develop a hobby.', offset: 147 },
    { id: 'q11b', text: 'He wanted to work shorter hours.', offset: 180 },
    { id: 'q11c', text: 'He found his job in website design unsatisfying.', offset: 213 },
    { id: 'q12', text: 'The Lifeboat Institution in Northsea was built with money provided by', offset: 262 },
    { id: 'q12a', text: 'a local organisation.', offset: 332 },
    { id: 'q12b', text: 'a local resident.', offset: 354 },
    { id: 'q12c', text: 'the local council.', offset: 372 },
    { id: 'q13', text: 'In his health assessment, the doctor was concerned about the fact that David', offset: 391 },
    { id: 'q13a', text: 'might be colour blind.', offset: 468 },
    { id: 'q13b', text: 'was rather short-sighted.', offset: 491 },
    { id: 'q13c', text: 'had undergone eye surgery.', offset: 517 },
    { id: 'q14', text: 'After arriving at the lifeboat station, they aim to launch the boat within', offset: 544 },
    { id: 'q14a', text: 'five minutes.', offset: 619 },
    { id: 'q14b', text: 'six to eight minutes.', offset: 633 },
    { id: 'q14c', text: 'eight and a half minutes.', offset: 655 },
    { id: 'q15', text: "As a 'helmsman', David has the responsibility of deciding", offset: 681 },
    { id: 'q15a', text: 'who will be the members of his crew.', offset: 739 },
    { id: 'q15b', text: 'what equipment it will be necessary to take.', offset: 776 },
    { id: 'q15c', text: 'if the lifeboat should be launched.', offset: 821 },
    { id: 'q16', text: 'As well as going out on the lifeboat, David', offset: 857 },
    { id: 'q16a', text: 'gives talks on safety at sea.', offset: 901 },
    { id: 'q16b', text: 'helps with fundraising.', offset: 931 },
    { id: 'q16c', text: 'recruits new volunteers.', offset: 955 },
    { id: 'questions2', text: 'Questions 17 and 18', offset: 980 },
    { id: 'instruction2', text: 'Choose TWO letters, A-E.', offset: 1000 },
    { id: 'q17_18', text: 'Which TWO things does David say about the lifeboat volunteer training?', offset: 1025 },
    { id: 'q17a', text: 'The residential course developed his leadership skills.', offset: 1096 },
    { id: 'q17b', text: 'The training in use of ropes and knots was quite brief.', offset: 1152 },
    { id: 'q17c', text: 'The training exercises have built up his mental strength.', offset: 1208 },
    { id: 'q17d', text: 'The casualty care activities were particularly challenging for him.', offset: 1266 },
    { id: 'q17e', text: 'The wave tank activities provided practice in survival techniques.', offset: 1334 },
    { id: 'questions3', text: 'Questions 19 and 20', offset: 1401 },
    { id: 'instruction3', text: 'Choose TWO letters, A-E.', offset: 1421 },
    { id: 'q19_20', text: 'Which TWO things does David find most motivating about the work he does?', offset: 1446 },
    { id: 'q19a', text: 'working as part of a team', offset: 1519 },
    { id: 'q19b', text: 'experiences when working in winter', offset: 1545 },
    { id: 'q19c', text: 'being thanked by those he has helped', offset: 1580 },
    { id: 'q19d', text: 'the fact that it keeps him fit', offset: 1617 },
    { id: 'q19e', text: 'the chance to develop new equipment', offset: 1648 },
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
        el.classList.add('ring-4', 'ring-blue-400', 'ring-offset-2');
        setTimeout(() => el.classList.remove('ring-4', 'ring-blue-400', 'ring-offset-2'), 2000);
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
            class="w-full rounded-2xl border border-blue-100/50 bg-gradient-to-br from-white via-blue-50/30 to-indigo-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Animated Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-blue-200/50 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 animate-pulse items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/30"
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
                        <h2 class="text-lg font-bold tracking-widest text-blue-700 uppercase">
                            <span data-segment-id="header" v-html="getHighlightedSegment('PART 2', 'header')"></span>
                        </h2>
                        <h1 class="text-base font-semibold text-gray-700">
                            <span data-segment-id="questions1" v-html="getHighlightedSegment('Questions 11 - 16', 'questions1')"></span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Section 1: Multiple Choice Questions 11-16 -->
            <div class="mb-8">
                <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-blue-100 to-indigo-100 px-4 py-2 shadow-sm">
                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                    <span
                        data-segment-id="instruction1"
                        class="text-sm font-semibold text-blue-800"
                        v-html="getHighlightedSegment('Choose the correct letter, A, B or C.', 'instruction1')"
                    ></span>
                </div>

                <div class="mb-6 text-center">
                    <h3
                        class="inline-block border-b-2 border-blue-300 bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text pb-2 text-xl font-bold text-transparent"
                    >
                        <span data-segment-id="title1" v-html="getHighlightedSegment('Working as a lifeboat volunteer', 'title1')"></span>
                    </h3>
                </div>

                <div class="space-y-5">
                    <!-- Question 11 -->
                    <div
                        data-question="11"
                        class="group animate-slideUp rounded-xl border border-blue-100/50 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 p-5 shadow-sm transition-all duration-300 hover:border-blue-200 hover:shadow-md"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-lg shadow-blue-500/20"
                                >11</span
                            >
                            <span
                                data-segment-id="q11"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('What made David leave London and move to Northsea?', 'q11')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q11"
                                    value="A"
                                    class="h-5 w-5 text-blue-600 transition-all focus:ring-blue-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-blue-700">A</span>
                                <span
                                    data-segment-id="q11a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('He was eager to develop a hobby.', 'q11a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q11"
                                    value="B"
                                    class="h-5 w-5 text-blue-600 transition-all focus:ring-blue-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-blue-700">B</span>
                                <span
                                    data-segment-id="q11b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('He wanted to work shorter hours.', 'q11b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input
                                    type="radio"
                                    v-model="answers.q11"
                                    value="C"
                                    class="h-5 w-5 text-blue-600 transition-all focus:ring-blue-500 focus:ring-offset-2"
                                />
                                <span class="w-6 font-bold text-blue-700">C</span>
                                <span
                                    data-segment-id="q11c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('He found his job in website design unsatisfying.', 'q11c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 12 -->
                    <div
                        data-question="12"
                        class="group animate-slideUp rounded-xl border border-blue-100/50 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 p-5 shadow-sm transition-all duration-300 hover:border-blue-200 hover:shadow-md"
                        style="animation-delay: 0.05s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-lg shadow-blue-500/20"
                                >12</span
                            >
                            <span
                                data-segment-id="q12"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('The Lifeboat Institution in Northsea was built with money provided by', 'q12')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q12" value="A" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">A</span>
                                <span
                                    data-segment-id="q12a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('a local organisation.', 'q12a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q12" value="B" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">B</span>
                                <span
                                    data-segment-id="q12b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('a local resident.', 'q12b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q12" value="C" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">C</span>
                                <span
                                    data-segment-id="q12c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('the local council.', 'q12c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 13 -->
                    <div
                        data-question="13"
                        class="group animate-slideUp rounded-xl border border-blue-100/50 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 p-5 shadow-sm transition-all duration-300 hover:border-blue-200 hover:shadow-md"
                        style="animation-delay: 0.1s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-lg shadow-blue-500/20"
                                >13</span
                            >
                            <span
                                data-segment-id="q13"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('In his health assessment, the doctor was concerned about the fact that David', 'q13')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q13" value="A" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">A</span>
                                <span
                                    data-segment-id="q13a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('might be colour blind.', 'q13a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q13" value="B" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">B</span>
                                <span
                                    data-segment-id="q13b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('was rather short-sighted.', 'q13b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q13" value="C" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">C</span>
                                <span
                                    data-segment-id="q13c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('had undergone eye surgery.', 'q13c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 14 -->
                    <div
                        data-question="14"
                        class="group animate-slideUp rounded-xl border border-blue-100/50 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 p-5 shadow-sm transition-all duration-300 hover:border-blue-200 hover:shadow-md"
                        style="animation-delay: 0.15s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-lg shadow-blue-500/20"
                                >14</span
                            >
                            <span
                                data-segment-id="q14"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('After arriving at the lifeboat station, they aim to launch the boat within', 'q14')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q14" value="A" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">A</span>
                                <span
                                    data-segment-id="q14a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('five minutes.', 'q14a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q14" value="B" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">B</span>
                                <span
                                    data-segment-id="q14b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('six to eight minutes.', 'q14b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q14" value="C" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">C</span>
                                <span
                                    data-segment-id="q14c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('eight and a half minutes.', 'q14c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 15 -->
                    <div
                        data-question="15"
                        class="group animate-slideUp rounded-xl border border-blue-100/50 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 p-5 shadow-sm transition-all duration-300 hover:border-blue-200 hover:shadow-md"
                        style="animation-delay: 0.2s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-lg shadow-blue-500/20"
                                >15</span
                            >
                            <span
                                data-segment-id="q15"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('As a \'helmsman\', David has the responsibility of deciding', 'q15')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q15" value="A" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">A</span>
                                <span
                                    data-segment-id="q15a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('who will be the members of his crew.', 'q15a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q15" value="B" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">B</span>
                                <span
                                    data-segment-id="q15b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('what equipment it will be necessary to take.', 'q15b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q15" value="C" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">C</span>
                                <span
                                    data-segment-id="q15c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('if the lifeboat should be launched.', 'q15c')"
                                ></span>
                            </label>
                        </div>
                    </div>

                    <!-- Question 16 -->
                    <div
                        data-question="16"
                        class="group animate-slideUp rounded-xl border border-blue-100/50 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 p-5 shadow-sm transition-all duration-300 hover:border-blue-200 hover:shadow-md"
                        style="animation-delay: 0.25s"
                    >
                        <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-lg shadow-blue-500/20"
                                >16</span
                            >
                            <span
                                data-segment-id="q16"
                                class="leading-relaxed"
                                v-html="getHighlightedSegment('As well as going out on the lifeboat, David', 'q16')"
                            ></span>
                        </p>
                        <div class="ml-11 space-y-2">
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q16" value="A" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">A</span>
                                <span
                                    data-segment-id="q16a"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('gives talks on safety at sea.', 'q16a')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q16" value="B" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">B</span>
                                <span
                                    data-segment-id="q16b"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('helps with fundraising.', 'q16b')"
                                ></span>
                            </label>
                            <label
                                class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-blue-100/50"
                            >
                                <input type="radio" v-model="answers.q16" value="C" class="h-5 w-5 text-blue-600 focus:ring-blue-500" />
                                <span class="w-6 font-bold text-blue-700">C</span>
                                <span
                                    data-segment-id="q16c"
                                    class="text-gray-700 group-hover/option:text-gray-900"
                                    v-html="getHighlightedSegment('recruits new volunteers.', 'q16c')"
                                ></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Questions 17-18 -->
            <div class="mb-8 border-t-2 border-dashed border-indigo-200 pt-8">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-16 items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 shadow-lg">
                        <span class="text-sm font-bold text-white">17-18</span>
                    </div>
                    <h3 class="bg-gradient-to-r from-indigo-700 to-purple-600 bg-clip-text text-lg font-bold text-transparent">
                        <span data-segment-id="questions2" v-html="getHighlightedSegment('Questions 17 and 18', 'questions2')"></span>
                    </h3>
                </div>

                <div
                    data-question="17"
                    class="animate-slideUp rounded-2xl border-2 border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-purple-50 p-5 shadow-lg"
                    style="animation-delay: 0.3s"
                >
                    <p class="mb-3 flex items-center gap-2 font-semibold text-indigo-800">
                        <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                            ></path>
                        </svg>
                        <span data-segment-id="instruction2" v-html="getHighlightedSegment('Choose TWO letters, A-E.', 'instruction2')"></span>
                    </p>

                    <p class="mb-5 text-base leading-relaxed text-gray-700">
                        <span
                            data-segment-id="q17_18"
                            v-html="getHighlightedSegment('Which TWO things does David say about the lifeboat volunteer training?', 'q17_18')"
                        ></span>
                    </p>

                    <div class="space-y-3">
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-indigo-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <input
                                type="checkbox"
                                value="A"
                                v-model="multipleAnswers.q17_18"
                                :disabled="multipleAnswers.q17_18.length === 2 && !multipleAnswers.q17_18.includes('A')"
                                class="mt-0.5 h-5 w-5 rounded border-indigo-300 text-indigo-600 transition-all focus:ring-indigo-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-indigo-800">
                                <span class="mr-2 font-bold text-indigo-600">A</span>
                                <span
                                    data-segment-id="q17a"
                                    v-html="getHighlightedSegment('The residential course developed his leadership skills.', 'q17a')"
                                ></span>
                            </span>
                        </label>
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-indigo-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <input
                                type="checkbox"
                                value="B"
                                v-model="multipleAnswers.q17_18"
                                :disabled="multipleAnswers.q17_18.length === 2 && !multipleAnswers.q17_18.includes('B')"
                                class="mt-0.5 h-5 w-5 rounded border-indigo-300 text-indigo-600 transition-all focus:ring-indigo-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-indigo-800">
                                <span class="mr-2 font-bold text-indigo-600">B</span>
                                <span
                                    data-segment-id="q17b"
                                    v-html="getHighlightedSegment('The training in use of ropes and knots was quite brief.', 'q17b')"
                                ></span>
                            </span>
                        </label>
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-indigo-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <input
                                type="checkbox"
                                value="C"
                                v-model="multipleAnswers.q17_18"
                                :disabled="multipleAnswers.q17_18.length === 2 && !multipleAnswers.q17_18.includes('C')"
                                class="mt-0.5 h-5 w-5 rounded border-indigo-300 text-indigo-600 transition-all focus:ring-indigo-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-indigo-800">
                                <span class="mr-2 font-bold text-indigo-600">C</span>
                                <span
                                    data-segment-id="q17c"
                                    v-html="getHighlightedSegment('The training exercises have built up his mental strength.', 'q17c')"
                                ></span>
                            </span>
                        </label>
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-indigo-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <input
                                type="checkbox"
                                value="D"
                                v-model="multipleAnswers.q17_18"
                                :disabled="multipleAnswers.q17_18.length === 2 && !multipleAnswers.q17_18.includes('D')"
                                class="mt-0.5 h-5 w-5 rounded border-indigo-300 text-indigo-600 transition-all focus:ring-indigo-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-indigo-800">
                                <span class="mr-2 font-bold text-indigo-600">D</span>
                                <span
                                    data-segment-id="q17d"
                                    v-html="getHighlightedSegment('The casualty care activities were particularly challenging for him.', 'q17d')"
                                ></span>
                            </span>
                        </label>
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-indigo-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <input
                                type="checkbox"
                                value="E"
                                v-model="multipleAnswers.q17_18"
                                :disabled="multipleAnswers.q17_18.length === 2 && !multipleAnswers.q17_18.includes('E')"
                                class="mt-0.5 h-5 w-5 rounded border-indigo-300 text-indigo-600 transition-all focus:ring-indigo-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-indigo-800">
                                <span class="mr-2 font-bold text-indigo-600">E</span>
                                <span
                                    data-segment-id="q17e"
                                    v-html="getHighlightedSegment('The wave tank activities provided practice in survival techniques.', 'q17e')"
                                ></span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-4 rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-100 to-purple-100 p-3 shadow-inner">
                        <p class="flex items-center gap-2 font-medium text-indigo-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            Selected: <span class="font-bold">{{ multipleAnswers.q17_18.length }}/2</span> answers
                            <span v-if="multipleAnswers.q17_18.length > 0" class="ml-2 text-sm">({{ multipleAnswers.q17_18.join(', ') }})</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Questions 19-20 -->
            <div class="border-t-2 border-dashed border-purple-200 pt-8">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-16 items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg">
                        <span class="text-sm font-bold text-white">19-20</span>
                    </div>
                    <h3 class="bg-gradient-to-r from-purple-700 to-pink-600 bg-clip-text text-lg font-bold text-transparent">
                        <span data-segment-id="questions3" v-html="getHighlightedSegment('Questions 19 and 20', 'questions3')"></span>
                    </h3>
                </div>

                <div
                    data-question="19"
                    class="animate-slideUp rounded-2xl border-2 border-purple-200 bg-gradient-to-br from-purple-50 via-white to-pink-50 p-5 shadow-lg"
                    style="animation-delay: 0.35s"
                >
                    <p class="mb-3 flex items-center gap-2 font-semibold text-purple-800">
                        <svg class="h-5 w-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                            ></path>
                        </svg>
                        <span data-segment-id="instruction3" v-html="getHighlightedSegment('Choose TWO letters, A-E.', 'instruction3')"></span>
                    </p>

                    <p class="mb-5 text-base leading-relaxed text-gray-700">
                        <span
                            data-segment-id="q19_20"
                            v-html="getHighlightedSegment('Which TWO things does David find most motivating about the work he does?', 'q19_20')"
                        ></span>
                    </p>

                    <div class="space-y-3">
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                        >
                            <input
                                type="checkbox"
                                value="A"
                                v-model="multipleAnswers.q19_20"
                                :disabled="multipleAnswers.q19_20.length === 2 && !multipleAnswers.q19_20.includes('A')"
                                class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                <span class="mr-2 font-bold text-purple-600">A</span>
                                <span data-segment-id="q19a" v-html="getHighlightedSegment('working as part of a team', 'q19a')"></span>
                            </span>
                        </label>
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                        >
                            <input
                                type="checkbox"
                                value="B"
                                v-model="multipleAnswers.q19_20"
                                :disabled="multipleAnswers.q19_20.length === 2 && !multipleAnswers.q19_20.includes('B')"
                                class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                <span class="mr-2 font-bold text-purple-600">B</span>
                                <span data-segment-id="q19b" v-html="getHighlightedSegment('experiences when working in winter', 'q19b')"></span>
                            </span>
                        </label>
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                        >
                            <input
                                type="checkbox"
                                value="C"
                                v-model="multipleAnswers.q19_20"
                                :disabled="multipleAnswers.q19_20.length === 2 && !multipleAnswers.q19_20.includes('C')"
                                class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                <span class="mr-2 font-bold text-purple-600">C</span>
                                <span data-segment-id="q19c" v-html="getHighlightedSegment('being thanked by those he has helped', 'q19c')"></span>
                            </span>
                        </label>
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                        >
                            <input
                                type="checkbox"
                                value="D"
                                v-model="multipleAnswers.q19_20"
                                :disabled="multipleAnswers.q19_20.length === 2 && !multipleAnswers.q19_20.includes('D')"
                                class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                <span class="mr-2 font-bold text-purple-600">D</span>
                                <span data-segment-id="q19d" v-html="getHighlightedSegment('the fact that it keeps him fit', 'q19d')"></span>
                            </span>
                        </label>
                        <label
                            class="group flex cursor-pointer items-start gap-3 rounded-xl border-2 border-purple-100 bg-white p-4 shadow-sm transition-all duration-200 hover:border-purple-300 hover:bg-purple-50"
                        >
                            <input
                                type="checkbox"
                                value="E"
                                v-model="multipleAnswers.q19_20"
                                :disabled="multipleAnswers.q19_20.length === 2 && !multipleAnswers.q19_20.includes('E')"
                                class="mt-0.5 h-5 w-5 rounded border-purple-300 text-purple-600 transition-all focus:ring-purple-500"
                            />
                            <span class="font-medium text-gray-700 transition-colors group-hover:text-purple-800">
                                <span class="mr-2 font-bold text-purple-600">E</span>
                                <span data-segment-id="q19e" v-html="getHighlightedSegment('the chance to develop new equipment', 'q19e')"></span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-4 rounded-xl border border-purple-200 bg-gradient-to-r from-purple-100 to-pink-100 p-3 shadow-inner">
                        <p class="flex items-center gap-2 font-medium text-purple-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            Selected: <span class="font-bold">{{ multipleAnswers.q19_20.length }}/2</span> answers
                            <span v-if="multipleAnswers.q19_20.length > 0" class="ml-2 text-sm">({{ multipleAnswers.q19_20.join(', ') }})</span>
                        </p>
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
                        class="context-menu pointer-events-auto rounded-xl border border-blue-200 bg-white/95 p-3 shadow-2xl backdrop-blur-sm"
                        @click.stop
                    >
                        <div class="flex items-center gap-2">
                            <button
                                v-for="color in colors"
                                :key="color.name"
                                @click="applyHighlight(color.name)"
                                :class="[
                                    color.class,
                                    'h-9 w-9 rounded-lg border-2 border-gray-200 transition-all duration-200 hover:scale-110 hover:border-blue-400 hover:shadow-md',
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
                        <p class="mb-3 line-clamp-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-gray-600 italic">
                            "{{ selectedText }}"
                        </p>
                        <input
                            v-model="noteInputText"
                            type="text"
                            placeholder="Write your note here..."
                            class="note-input-field w-full rounded-lg border-2 border-blue-200 px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none"
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
    border-color: #3b82f6;
    background: #3b82f6;
    box-shadow: inset 0 0 0 3px white;
}
input[type='radio']:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
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
