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
    { id: 'title', text: 'Tree planting', offset: 88 },
    { id: 'section1', text: 'Reforestation projects should:', offset: 102 },
    { id: 'bullet1', text: 'include a range of tree species', offset: 133 },
    { id: 'bullet2a', text: 'not include invasive species because of possible', offset: 165 },
    { id: 'bullet2b', text: 'with native species', offset: 214 },
    { id: 'bullet3a', text: 'aim to capture carbon, protect the environment and provide sustainable sources of', offset: 234 },
    { id: 'bullet3b', text: 'for local people', offset: 316 },
    { id: 'bullet4a', text: 'use tree seeds with a high genetic diversity to increase resistance to', offset: 333 },
    { id: 'bullet4b', text: 'and climate change', offset: 404 },
    {
        id: 'bullet5a',
        text: 'plant trees on previously forested land which is in a bad condition, not select land which is being used for',
        offset: 423,
    },
    { id: 'section2', text: 'Large-scale reforestation projects', offset: 532 },
    { id: 'bullet6a', text: 'Base planning decisions on information from accurate', offset: 567 },
    { id: 'bullet7a', text: 'Drones are useful for identifying areas in Brazil which are endangered by keeping', offset: 620 },
    { id: 'bullet7b', text: 'and illegal logging.', offset: 702 },
    { id: 'section3', text: 'Lampang Province, Northern Thailand', offset: 723 },
    { id: 'bullet8', text: 'A forest was restored in an area damaged by mining.', offset: 759 },
    { id: 'bullet9', text: 'A variety of native fig trees were planted, which are important for', offset: 811 },
    { id: 'subbullet1', text: 'supporting many wildlife species', offset: 879 },
    { id: 'subbullet2a', text: 'increasing the', offset: 912 },
    { id: 'subbullet2b', text: 'of recovery by attracting animals and birds,', offset: 927 },
    { id: 'subbullet3a', text: 'e.g.,', offset: 972 },
    { id: 'subbullet3b', text: 'were soon attracted to the area.', offset: 978 },
    { id: 'section4', text: 'Involving local communities', offset: 1011 },
    { id: 'bullet10a', text: 'Destruction of mangrove forests in Madagascar made it difficult for people to make a living from', offset: 1039 },
    { id: 'bullet11', text: 'The mangrove reforestation project:', offset: 1136 },
    { id: 'subbullet4', text: 'provided employment for local people', offset: 1172 },
    { id: 'subbullet5', text: 'restored a healthy ecosystem', offset: 1209 },
    { id: 'subbullet6a', text: 'protects against the higher risk of', offset: 1238 },
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
    const el = document.querySelector(`[data-question="${questionNumber}"]`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
            class="animate-fadeIn w-full rounded-2xl border border-emerald-100/50 bg-gradient-to-br from-white via-emerald-50/30 to-green-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-emerald-200/50 bg-gradient-to-r from-emerald-50 to-green-50 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="animate-pulse-slow flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 shadow-lg shadow-emerald-500/30"
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
                            class="bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-xl font-bold tracking-widest text-transparent uppercase"
                        >
                            <span data-segment-id="header" v-html="getHighlightedSegment('PART 4', 'header')"></span>
                        </h2>
                        <h1 class="text-base font-semibold text-gray-700">
                            <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 31 - 40', 'questions')"></span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="mb-6 border-b-2 border-dashed border-emerald-200 pt-4 pb-6">
                <p class="text-sm text-gray-700">
                    <span data-segment-id="instruction1" v-html="getHighlightedSegment('Complete the notes below.', 'instruction1')"></span>
                </p>
                <div class="mt-2 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-emerald-100 to-green-100 px-4 py-2 shadow-sm">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                        ></path>
                    </svg>
                    <span
                        data-segment-id="instruction2"
                        class="text-sm font-semibold text-emerald-800"
                        v-html="getHighlightedSegment('Write ONE WORD ONLY for each answer.', 'instruction2')"
                    ></span>
                </div>
            </div>

            <!-- Title -->
            <h3 class="mb-6 bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text pb-2 text-center text-2xl font-bold text-transparent">
                <span data-segment-id="title" v-html="getHighlightedSegment('Tree planting', 'title')"></span>
            </h3>

            <!-- Content Sections -->
            <div class="space-y-6">
                <!-- Reforestation projects should Section -->
                <div
                    class="animate-slideUp rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50/80 to-green-50/80 p-5 shadow-lg"
                    style="animation-delay: 0.1s"
                >
                    <h4 class="mb-4 border-b border-emerald-200 pb-2 text-lg font-bold text-emerald-800">
                        <span data-segment-id="section1" v-html="getHighlightedSegment('Reforestation projects should:', 'section1')"></span>
                    </h4>

                    <ul class="space-y-4 text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-emerald-600">*</span>
                            <span data-segment-id="bullet1" v-html="getHighlightedSegment('include a range of tree species', 'bullet1')"></span>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-emerald-600">*</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="bullet2a"
                                    v-html="getHighlightedSegment('not include invasive species because of possible', 'bullet2a')"
                                ></span>
                                <span data-question="31" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-green-600 text-xs font-bold text-white shadow-lg"
                                        >31</span
                                    >
                                    <input
                                        v-model="answers.q31"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-emerald-300 p-2.5 text-sm shadow-sm transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                                        placeholder="......"
                                    />
                                </span>
                                <span data-segment-id="bullet2b" v-html="getHighlightedSegment('with native species', 'bullet2b')"></span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-emerald-600">*</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="bullet3a"
                                    v-html="
                                        getHighlightedSegment(
                                            'aim to capture carbon, protect the environment and provide sustainable sources of',
                                            'bullet3a',
                                        )
                                    "
                                ></span>
                                <span data-question="32" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-green-600 text-xs font-bold text-white shadow-lg"
                                        >32</span
                                    >
                                    <input
                                        v-model="answers.q32"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-emerald-300 p-2.5 text-sm shadow-sm transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                                        placeholder="......"
                                    />
                                </span>
                                <span data-segment-id="bullet3b" v-html="getHighlightedSegment('for local people', 'bullet3b')"></span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-emerald-600">*</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="bullet4a"
                                    v-html="
                                        getHighlightedSegment('use tree seeds with a high genetic diversity to increase resistance to', 'bullet4a')
                                    "
                                ></span>
                                <span data-question="33" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-green-600 text-xs font-bold text-white shadow-lg"
                                        >33</span
                                    >
                                    <input
                                        v-model="answers.q33"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-emerald-300 p-2.5 text-sm shadow-sm transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                                        placeholder="......"
                                    />
                                </span>
                                <span data-segment-id="bullet4b" v-html="getHighlightedSegment('and climate change', 'bullet4b')"></span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-emerald-600">*</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="bullet5a"
                                    v-html="
                                        getHighlightedSegment(
                                            'plant trees on previously forested land which is in a bad condition, not select land which is being used for',
                                            'bullet5a',
                                        )
                                    "
                                ></span>
                                <span data-question="34" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-green-600 text-xs font-bold text-white shadow-lg"
                                        >34</span
                                    >
                                    <input
                                        v-model="answers.q34"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-emerald-300 p-2.5 text-sm shadow-sm transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                                        placeholder="......"
                                    />
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Large-scale reforestation projects Section -->
                <div
                    class="animate-slideUp rounded-2xl border border-teal-100 bg-gradient-to-br from-teal-50/80 to-cyan-50/80 p-5 shadow-lg"
                    style="animation-delay: 0.15s"
                >
                    <h4 class="mb-4 border-b border-teal-200 pb-2 text-lg font-bold text-teal-800">
                        <span data-segment-id="section2" v-html="getHighlightedSegment('Large-scale reforestation projects', 'section2')"></span>
                    </h4>

                    <ul class="space-y-4 text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-teal-600">*</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="bullet6a"
                                    v-html="getHighlightedSegment('Base planning decisions on information from accurate', 'bullet6a')"
                                ></span>
                                <span data-question="35" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-lg"
                                        >35</span
                                    >
                                    <input
                                        v-model="answers.q35"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-teal-300 p-2.5 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                        placeholder="......"
                                    />
                                </span>
                                <span>.</span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-teal-600">*</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="bullet7a"
                                    v-html="
                                        getHighlightedSegment(
                                            'Drones are useful for identifying areas in Brazil which are endangered by keeping',
                                            'bullet7a',
                                        )
                                    "
                                ></span>
                                <span data-question="36" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 text-xs font-bold text-white shadow-lg"
                                        >36</span
                                    >
                                    <input
                                        v-model="answers.q36"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-teal-300 p-2.5 text-sm shadow-sm transition-all focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                                        placeholder="......"
                                    />
                                </span>
                                <span data-segment-id="bullet7b" v-html="getHighlightedSegment('and illegal logging.', 'bullet7b')"></span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Lampang Province Section -->
                <div
                    class="animate-slideUp rounded-2xl border border-lime-100 bg-gradient-to-br from-lime-50/80 to-green-50/80 p-5 shadow-lg"
                    style="animation-delay: 0.2s"
                >
                    <h4 class="mb-4 border-b border-lime-200 pb-2 text-lg font-bold text-lime-800">
                        <span data-segment-id="section3" v-html="getHighlightedSegment('Lampang Province, Northern Thailand', 'section3')"></span>
                    </h4>

                    <ul class="space-y-4 text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-lime-600">*</span>
                            <span
                                data-segment-id="bullet8"
                                v-html="getHighlightedSegment('A forest was restored in an area damaged by mining.', 'bullet8')"
                            ></span>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-lime-600">*</span>
                            <span
                                data-segment-id="bullet9"
                                v-html="getHighlightedSegment('A variety of native fig trees were planted, which are important for', 'bullet9')"
                            ></span>
                        </li>

                        <li class="ml-8 flex items-start gap-2">
                            <span class="mt-1 text-lime-500">-</span>
                            <span
                                data-segment-id="subbullet1"
                                v-html="getHighlightedSegment('supporting many wildlife species', 'subbullet1')"
                            ></span>
                        </li>

                        <li class="ml-8 flex items-start gap-2">
                            <span class="mt-1 text-lime-500">-</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span data-segment-id="subbullet2a" v-html="getHighlightedSegment('increasing the', 'subbullet2a')"></span>
                                <span data-question="37" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-lime-500 to-green-600 text-xs font-bold text-white shadow-lg"
                                        >37</span
                                    >
                                    <input
                                        v-model="answers.q37"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-lime-300 p-2.5 text-sm shadow-sm transition-all focus:border-lime-500 focus:ring-2 focus:ring-lime-500/20"
                                        placeholder="......"
                                    />
                                </span>
                                <span
                                    data-segment-id="subbullet2b"
                                    v-html="getHighlightedSegment('of recovery by attracting animals and birds,', 'subbullet2b')"
                                ></span>
                            </div>
                        </li>

                        <li class="ml-12 flex items-start gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span data-segment-id="subbullet3a" v-html="getHighlightedSegment('e.g.,', 'subbullet3a')"></span>
                                <span data-question="38" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-lime-500 to-green-600 text-xs font-bold text-white shadow-lg"
                                        >38</span
                                    >
                                    <input
                                        v-model="answers.q38"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-lime-300 p-2.5 text-sm shadow-sm transition-all focus:border-lime-500 focus:ring-2 focus:ring-lime-500/20"
                                        placeholder="......"
                                    />
                                </span>
                                <span
                                    data-segment-id="subbullet3b"
                                    v-html="getHighlightedSegment('were soon attracted to the area.', 'subbullet3b')"
                                ></span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Involving local communities Section -->
                <div
                    class="animate-slideUp rounded-2xl border border-green-100 bg-gradient-to-br from-green-50/80 to-emerald-50/80 p-5 shadow-lg"
                    style="animation-delay: 0.25s"
                >
                    <h4 class="mb-4 border-b border-green-200 pb-2 text-lg font-bold text-green-800">
                        <span data-segment-id="section4" v-html="getHighlightedSegment('Involving local communities', 'section4')"></span>
                    </h4>

                    <ul class="space-y-4 text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-green-600">*</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="bullet10a"
                                    v-html="
                                        getHighlightedSegment(
                                            'Destruction of mangrove forests in Madagascar made it difficult for people to make a living from',
                                            'bullet10a',
                                        )
                                    "
                                ></span>
                                <span data-question="39" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-xs font-bold text-white shadow-lg"
                                        >39</span
                                    >
                                    <input
                                        v-model="answers.q39"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-green-300 p-2.5 text-sm shadow-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20"
                                        placeholder="......"
                                    />
                                </span>
                                <span>.</span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-xl text-green-600">*</span>
                            <span data-segment-id="bullet11" v-html="getHighlightedSegment('The mangrove reforestation project:', 'bullet11')"></span>
                        </li>

                        <li class="ml-8 flex items-start gap-2">
                            <span class="mt-1 text-green-500">-</span>
                            <span
                                data-segment-id="subbullet4"
                                v-html="getHighlightedSegment('provided employment for local people', 'subbullet4')"
                            ></span>
                        </li>

                        <li class="ml-8 flex items-start gap-2">
                            <span class="mt-1 text-green-500">-</span>
                            <span data-segment-id="subbullet5" v-html="getHighlightedSegment('restored a healthy ecosystem', 'subbullet5')"></span>
                        </li>

                        <li class="ml-8 flex items-start gap-2">
                            <span class="mt-1 text-green-500">-</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="subbullet6a"
                                    v-html="getHighlightedSegment('protects against the higher risk of', 'subbullet6a')"
                                ></span>
                                <span data-question="40" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-xs font-bold text-white shadow-lg"
                                        >40</span
                                    >
                                    <input
                                        v-model="answers.q40"
                                        type="text"
                                        class="w-28 rounded-xl border-2 border-green-300 p-2.5 text-sm shadow-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20"
                                        placeholder="......"
                                    />
                                </span>
                                <span>.</span>
                            </div>
                        </li>
                    </ul>
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
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-emerald-200 bg-emerald-50 transition-all duration-200 hover:scale-110 hover:bg-emerald-100 hover:shadow-md"
                                title="Add Note"
                            >
                                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-emerald-300 bg-white p-4 shadow-2xl"
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
                            class="note-input-field w-full rounded-lg border-2 border-emerald-200 px-4 py-3 text-sm transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
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
                            class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-emerald-500/20 transition-all hover:from-emerald-700 hover:to-green-700"
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
