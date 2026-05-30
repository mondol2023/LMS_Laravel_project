<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Answers State
const answers = ref({
    q31: '',
    q32: '',
    q33: '',
    q34: '',
    q35: '',
    q36: '',
    q37: '',
    q38: '',
    q39: '',
    q40: '',
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

// Text segments for multiline highlighting
const textSegments = ref([
    { id: 'header', text: 'PART 4', offset: 0 },
    { id: 'questions', text: 'Questions 31 - 40', offset: 7 },
    { id: 'instruction1', text: 'Complete the notes below.', offset: 25 },
    { id: 'instruction2', text: 'Write ONE WORD ONLY for each answer.', offset: 51 },
    { id: 'title', text: 'Tardigrades', offset: 88 },
    { id: 'intro1', text: 'more than 1,000 species, 0.05–1.2 millimetres long', offset: 100 },
    { id: 'intro2', text: "also known as water 'bears' (due to how they", offset: 151 },
    { id: 'intro3', text: ") and 'moss piglets'", offset: 196 },
    { id: 'section1', text: 'Physical appearance', offset: 217 },
    { id: 'physical1', text: 'a', offset: 237 },
    { id: 'physical2', text: 'round body and four pairs of legs', offset: 239 },
    { id: 'physical3', text: 'claws or', offset: 273 },
    { id: 'physical4', text: 'for gripping', offset: 282 },
    { id: 'physical5', text: 'absence of respiratory organs', offset: 295 },
    { id: 'physical6', text: 'body filled with a liquid that carries both', offset: 326 },
    { id: 'physical7', text: 'and blood', offset: 370 },
    { id: 'physical8', text: 'mouth shaped like a', offset: 380 },
    { id: 'physical9', text: 'with teeth called stylets', offset: 400 },
    { id: 'section2', text: 'Habitat', offset: 426 },
    { id: 'habitat1', text: 'often found at the bottom of a lake or on plants', offset: 434 },
    { id: 'habitat2', text: 'very resilient and can exist in very low or high', offset: 483 },
    { id: 'section3', text: 'Cryptobiosis', offset: 532 },
    { id: 'crypto1', text: "In dry conditions, they roll into a ball called a 'tun'.", offset: 545 },
    { id: 'crypto2', text: 'They stay alive with a much lower metabolism than usual.', offset: 602 },
    { id: 'crypto3', text: 'A type of', offset: 659 },
    { id: 'crypto4', text: 'ensures their DNA is not damaged.', offset: 669 },
    { id: 'crypto5', text: 'Research is underway to find out how many days they can stay alive in', offset: 703 },
    { id: 'section4', text: 'Feeding', offset: 773 },
    { id: 'feeding1', text: 'consume liquids, e.g., those found in moss or', offset: 781 },
    { id: 'feeding2', text: 'may eat other tardigrades', offset: 827 },
    { id: 'section5', text: 'Conservation status', offset: 853 },
    { id: 'conservation1', text: 'They are not considered to be', offset: 873 },
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
        part: 'Part 4',
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
        inputEl.classList.add('ring-4', 'ring-orange-400', 'ring-offset-2');
        setTimeout(() => inputEl.classList.remove('ring-4', 'ring-orange-400', 'ring-offset-2'), 2000);
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
            class="w-full rounded-2xl border border-orange-100/50 bg-gradient-to-br from-white via-orange-50/30 to-amber-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-orange-200/50 bg-gradient-to-r from-orange-50 to-amber-50 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 animate-pulse items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 shadow-lg shadow-orange-500/30"
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
                        <h2 class="text-lg font-bold tracking-widest text-orange-700 uppercase">
                            <span data-segment-id="header" v-html="getHighlightedSegment('PART 4', 'header')"></span>
                        </h2>
                        <h1 class="text-base font-semibold text-gray-700">
                            <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 31 - 40', 'questions')"></span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="mb-6 border-b-2 border-dashed border-orange-200 pb-6">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-orange-100 to-amber-100 px-4 py-2 shadow-sm">
                    <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        ></path>
                    </svg>
                    <span
                        data-segment-id="instruction1"
                        class="text-sm text-orange-800"
                        v-html="getHighlightedSegment('Complete the notes below.', 'instruction1')"
                    ></span>
                </div>
                <p class="font-bold text-orange-800">
                    <span
                        data-segment-id="instruction2"
                        v-html="getHighlightedSegment('Write ONE WORD ONLY for each answer.', 'instruction2')"
                    ></span>
                </p>
            </div>

            <!-- Title -->
            <h3 class="mb-6 bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-center text-2xl font-bold text-transparent">
                <span data-segment-id="title" v-html="getHighlightedSegment('Tardigrades', 'title')"></span>
            </h3>

            <!-- Intro -->
            <div class="mb-6 rounded-xl border border-orange-100 bg-orange-50/50 p-4">
                <ul class="space-y-2 text-gray-700">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-orange-600">●</span>
                        <span
                            data-segment-id="intro1"
                            v-html="getHighlightedSegment('more than 1,000 species, 0.05–1.2 millimetres long', 'intro1')"
                        ></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-orange-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                data-segment-id="intro2"
                                v-html="getHighlightedSegment('also known as water \'bears\' (due to how they', 'intro2')"
                            ></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >31</span
                            >
                            <input
                                v-model="answers.q31"
                                data-question="31"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                            <span data-segment-id="intro3" v-html="getHighlightedSegment(') and \'moss piglets\'', 'intro3')"></span>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Physical appearance Section -->
            <div class="animate-slideUp mb-6 rounded-xl bg-amber-50/50 p-5">
                <h4 class="mb-4 border-b border-amber-200 pb-2 text-lg font-bold text-amber-800">
                    <span data-segment-id="section1" v-html="getHighlightedSegment('Physical appearance', 'section1')"></span>
                </h4>
                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-amber-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="physical1" v-html="getHighlightedSegment('a', 'physical1')"></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >32</span
                            >
                            <input
                                v-model="answers.q32"
                                data-question="32"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                            <span data-segment-id="physical2" v-html="getHighlightedSegment('round body and four pairs of legs', 'physical2')"></span>
                        </div>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-amber-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="physical3" v-html="getHighlightedSegment('claws or', 'physical3')"></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >33</span
                            >
                            <input
                                v-model="answers.q33"
                                data-question="33"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                            <span data-segment-id="physical4" v-html="getHighlightedSegment('for gripping', 'physical4')"></span>
                        </div>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-amber-600">●</span>
                        <span data-segment-id="physical5" v-html="getHighlightedSegment('absence of respiratory organs', 'physical5')"></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-amber-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                data-segment-id="physical6"
                                v-html="getHighlightedSegment('body filled with a liquid that carries both', 'physical6')"
                            ></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >34</span
                            >
                            <input
                                v-model="answers.q34"
                                data-question="34"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                            <span data-segment-id="physical7" v-html="getHighlightedSegment('and blood', 'physical7')"></span>
                        </div>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-amber-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="physical8" v-html="getHighlightedSegment('mouth shaped like a', 'physical8')"></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >35</span
                            >
                            <input
                                v-model="answers.q35"
                                data-question="35"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                            <span data-segment-id="physical9" v-html="getHighlightedSegment('with teeth called stylets', 'physical9')"></span>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Habitat Section -->
            <div class="animate-slideUp mb-6 rounded-xl bg-yellow-50/50 p-5" style="animation-delay: 0.1s">
                <h4 class="mb-4 border-b border-yellow-200 pb-2 text-lg font-bold text-yellow-800">
                    <span data-segment-id="section2" v-html="getHighlightedSegment('Habitat', 'section2')"></span>
                </h4>
                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-yellow-600">●</span>
                        <span
                            data-segment-id="habitat1"
                            v-html="getHighlightedSegment('often found at the bottom of a lake or on plants', 'habitat1')"
                        ></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-yellow-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                data-segment-id="habitat2"
                                v-html="getHighlightedSegment('very resilient and can exist in very low or high', 'habitat2')"
                            ></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >36</span
                            >
                            <input
                                v-model="answers.q36"
                                data-question="36"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Cryptobiosis Section -->
            <div class="animate-slideUp mb-6 rounded-xl bg-red-50/50 p-5" style="animation-delay: 0.2s">
                <h4 class="mb-4 border-b border-red-200 pb-2 text-lg font-bold text-red-800">
                    <span data-segment-id="section3" v-html="getHighlightedSegment('Cryptobiosis', 'section3')"></span>
                </h4>
                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-red-600">●</span>
                        <span
                            data-segment-id="crypto1"
                            v-html="getHighlightedSegment('In dry conditions, they roll into a ball called a \'tun\'.', 'crypto1')"
                        ></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-red-600">●</span>
                        <span
                            data-segment-id="crypto2"
                            v-html="getHighlightedSegment('They stay alive with a much lower metabolism than usual.', 'crypto2')"
                        ></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-red-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="crypto3" v-html="getHighlightedSegment('A type of', 'crypto3')"></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >37</span
                            >
                            <input
                                v-model="answers.q37"
                                data-question="37"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                            <span data-segment-id="crypto4" v-html="getHighlightedSegment('ensures their DNA is not damaged.', 'crypto4')"></span>
                        </div>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-red-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                data-segment-id="crypto5"
                                v-html="getHighlightedSegment('Research is underway to find out how many days they can stay alive in', 'crypto5')"
                            ></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >38</span
                            >
                            <input
                                v-model="answers.q38"
                                data-question="38"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                            <span>.</span>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Feeding Section -->
            <div class="animate-slideUp mb-6 rounded-xl bg-rose-50/50 p-5" style="animation-delay: 0.3s">
                <h4 class="mb-4 border-b border-rose-200 pb-2 text-lg font-bold text-rose-800">
                    <span data-segment-id="section4" v-html="getHighlightedSegment('Feeding', 'section4')"></span>
                </h4>
                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-rose-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                data-segment-id="feeding1"
                                v-html="getHighlightedSegment('consume liquids, e.g., those found in moss or', 'feeding1')"
                            ></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >39</span
                            >
                            <input
                                v-model="answers.q39"
                                data-question="39"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                        </div>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-rose-600">●</span>
                        <span data-segment-id="feeding2" v-html="getHighlightedSegment('may eat other tardigrades', 'feeding2')"></span>
                    </li>
                </ul>
            </div>

            <!-- Conservation status Section -->
            <div class="animate-slideUp rounded-xl bg-pink-50/50 p-5" style="animation-delay: 0.4s">
                <h4 class="mb-4 border-b border-pink-200 pb-2 text-lg font-bold text-pink-800">
                    <span data-segment-id="section5" v-html="getHighlightedSegment('Conservation status', 'section5')"></span>
                </h4>
                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-pink-600">●</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                data-segment-id="conservation1"
                                v-html="getHighlightedSegment('They are not considered to be', 'conservation1')"
                            ></span>
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-xs font-bold text-white shadow-md"
                                >40</span
                            >
                            <input
                                v-model="answers.q40"
                                data-question="40"
                                type="text"
                                class="w-24 rounded-lg border-2 border-orange-300 p-2 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200"
                                placeholder="......"
                            />
                            <span>.</span>
                        </div>
                    </li>
                </ul>
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
                        class="context-menu pointer-events-auto rounded-xl border border-orange-200 bg-white/95 p-3 shadow-2xl backdrop-blur-sm"
                        @click.stop
                    >
                        <div class="flex items-center gap-2">
                            <button
                                v-for="color in colors"
                                :key="color.name"
                                @click="applyHighlight(color.name)"
                                :class="[
                                    color.class,
                                    'h-9 w-9 rounded-lg border-2 border-gray-200 transition-all duration-200 hover:scale-110 hover:border-orange-400 hover:shadow-md',
                                ]"
                                :title="`Highlight ${color.name}`"
                            ></button>
                            <div class="mx-1 h-6 w-px bg-gray-200"></div>
                            <button
                                @click="openNoteInput"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-orange-200 bg-orange-50 transition-all duration-200 hover:scale-110 hover:bg-orange-100 hover:shadow-md"
                                title="Add Note"
                            >
                                <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-orange-300 bg-white p-4 shadow-2xl"
                    :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="mb-3">
                        <p class="mb-3 line-clamp-2 rounded-lg border border-orange-200 bg-orange-50 p-3 text-sm text-gray-600 italic">
                            "{{ selectedText }}"
                        </p>
                        <input
                            v-model="noteInputText"
                            type="text"
                            placeholder="Write your note here..."
                            class="note-input-field w-full rounded-lg border-2 border-orange-200 px-4 py-3 text-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none"
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
                            class="rounded-lg bg-gradient-to-r from-orange-600 to-amber-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-orange-500/20 transition-all hover:from-orange-700 hover:to-amber-700"
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
