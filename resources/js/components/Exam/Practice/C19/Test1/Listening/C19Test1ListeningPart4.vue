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
    { id: 'title', text: 'Céide Fields', offset: 88 },
    { id: 'intro', text: 'an important Neolithic archaeological site in the northwest of Ireland', offset: 101 },
    { id: 'discovery', text: 'Discovery', offset: 172 },
    { id: 'teacher', text: 'In the 1930s, a local teacher realised that stones beneath the bog surface were once', offset: 182 },
    { id: 'son', text: 'His', offset: 266 },
    { id: 'archaeologist', text: 'became an archaeologist and undertook an investigation of the site:', offset: 270 },
    { id: 'method', text: 'a traditional method used by local people to dig for', offset: 338 },
    { id: 'identify', text: 'was used to identify where stones were located', offset: 391 },
    { id: 'carbon', text: 'carbon dating later proved the site was Neolithic.', offset: 438 },
    { id: 'preserved', text: 'Items are well preserved in the bog because of a lack of', offset: 489 },
    { id: 'farmers', text: 'Neolithic farmers', offset: 546 },
    { id: 'houses', text: 'Houses were', offset: 564 },
    { id: 'shape', text: 'in shape and had a hole in the roof.', offset: 576 },
    { id: 'innovations', text: 'Neolithic innovations include:', offset: 613 },
    { id: 'cooking', text: 'cooking indoors', offset: 644 },
    { id: 'pots', text: 'pots used for storage and to make', offset: 660 },
    { id: 'field', text: 'Each field at Céide was large enough to support a big', offset: 694 },
    {
        id: 'grazing',
        text: 'The fields were probably used to restrict the grazing of animals – no evidence of structures to house them during',
        offset: 748,
    },
    { id: 'decline', text: 'Reasons for the decline in farming', offset: 862 },
    { id: 'quality', text: 'a decline in', offset: 897 },
    { id: 'quality2', text: 'quality', offset: 910 },
    { id: 'increase', text: 'an increase in', offset: 918 },
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
            class="w-full rounded-2xl border border-orange-100/50 bg-gradient-to-br from-white via-orange-50/30 to-amber-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Header -->
            <div class="flex-shrink-0 border-b border-gray-200 bg-white p-4 lg:p-6">
                <div class="flex items-center space-x-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-orange-500 to-red-600">
                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold tracking-widest text-orange-700 uppercase">
                            <span data-segment-id="header" v-html="getHighlightedSegment('PART 4', 'header')"></span>
                        </h2>
                        <h1 class="text-base font-bold text-gray-900">
                            <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 31 - 40', 'questions')"></span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="mb-6 border-b-2 border-dashed border-gray-300 pt-4 pb-6">
                <p class="text-sm text-gray-700">
                    <span data-segment-id="instruction1" v-html="getHighlightedSegment('Complete the notes below.', 'instruction1')"></span>
                </p>
                <p class="font-bold text-orange-800">
                    <span
                        data-segment-id="instruction2"
                        v-html="getHighlightedSegment('Write ONE WORD ONLY for each answer.', 'instruction2')"
                    ></span>
                </p>
            </div>

            <!-- Title -->
            <h3 class="mb-2 text-center text-2xl font-bold text-orange-900">
                <span data-segment-id="title" v-html="getHighlightedSegment('Céide Fields', 'title')"></span>
            </h3>
            <p class="mb-6 text-center text-gray-600 italic">
                <span
                    data-segment-id="intro"
                    v-html="getHighlightedSegment('an important Neolithic archaeological site in the northwest of Ireland', 'intro')"
                ></span>
            </p>

            <!-- Content Sections -->
            <div class="space-y-6">
                <!-- Discovery Section -->
                <div class="rounded-lg bg-amber-50/50 p-5">
                    <h4 class="mb-4 border-b border-amber-200 pb-2 text-lg font-bold text-amber-800">
                        <span data-segment-id="discovery" v-html="getHighlightedSegment('Discovery', 'discovery')"></span>
                    </h4>

                    <ul class="space-y-4 text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-amber-600">●</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="teacher"
                                    v-html="
                                        getHighlightedSegment(
                                            'In the 1930s, a local teacher realised that stones beneath the bog surface were once',
                                            'teacher',
                                        )
                                    "
                                ></span>
                                <span data-question="31" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >31</span
                                    >
                                    <input
                                        v-model="answers.q31"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                                <span>.</span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-amber-600">●</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span data-segment-id="son" v-html="getHighlightedSegment('His', 'son')"></span>
                                <span data-question="32" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >32</span
                                    >
                                    <input
                                        v-model="answers.q32"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                                <span
                                    data-segment-id="archaeologist"
                                    v-html="
                                        getHighlightedSegment('became an archaeologist and undertook an investigation of the site:', 'archaeologist')
                                    "
                                ></span>
                            </div>
                        </li>

                        <li class="ml-6 flex items-start gap-2">
                            <span class="mt-1 text-amber-500">–</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="method"
                                    v-html="getHighlightedSegment('a traditional method used by local people to dig for', 'method')"
                                ></span>
                                <span data-question="33" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >33</span
                                    >
                                    <input
                                        v-model="answers.q33"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                                <span
                                    data-segment-id="identify"
                                    v-html="getHighlightedSegment('was used to identify where stones were located', 'identify')"
                                ></span>
                            </div>
                        </li>

                        <li class="ml-6 flex items-start gap-2">
                            <span class="mt-1 text-amber-500">–</span>
                            <span
                                data-segment-id="carbon"
                                v-html="getHighlightedSegment('carbon dating later proved the site was Neolithic.', 'carbon')"
                            ></span>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-amber-600">●</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="preserved"
                                    v-html="getHighlightedSegment('Items are well preserved in the bog because of a lack of', 'preserved')"
                                ></span>
                                <span data-question="34" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >34</span
                                    >
                                    <input
                                        v-model="answers.q34"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                                <span>.</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Neolithic farmers Section -->
                <div class="rounded-lg bg-red-50/50 p-5">
                    <h4 class="mb-4 border-b border-red-200 pb-2 text-lg font-bold text-red-800">
                        <span data-segment-id="farmers" v-html="getHighlightedSegment('Neolithic farmers', 'farmers')"></span>
                    </h4>

                    <ul class="space-y-4 text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-red-600">●</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span data-segment-id="houses" v-html="getHighlightedSegment('Houses were', 'houses')"></span>
                                <span data-question="35" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >35</span
                                    >
                                    <input
                                        v-model="answers.q35"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                                <span data-segment-id="shape" v-html="getHighlightedSegment('in shape and had a hole in the roof.', 'shape')"></span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-red-600">●</span>
                            <span
                                data-segment-id="innovations"
                                v-html="getHighlightedSegment('Neolithic innovations include:', 'innovations')"
                            ></span>
                        </li>

                        <li class="ml-6 flex items-start gap-2">
                            <span class="mt-1 text-red-500">–</span>
                            <span data-segment-id="cooking" v-html="getHighlightedSegment('cooking indoors', 'cooking')"></span>
                        </li>

                        <li class="ml-6 flex items-start gap-2">
                            <span class="mt-1 text-red-500">–</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span data-segment-id="pots" v-html="getHighlightedSegment('pots used for storage and to make', 'pots')"></span>
                                <span data-question="36" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >36</span
                                    >
                                    <input
                                        v-model="answers.q36"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                                <span>.</span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-red-600">●</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="field"
                                    v-html="getHighlightedSegment('Each field at Céide was large enough to support a big', 'field')"
                                ></span>
                                <span data-question="37" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >37</span
                                    >
                                    <input
                                        v-model="answers.q37"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                                <span>.</span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-red-600">●</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    data-segment-id="grazing"
                                    v-html="
                                        getHighlightedSegment(
                                            'The fields were probably used to restrict the grazing of animals – no evidence of structures to house them during',
                                            'grazing',
                                        )
                                    "
                                ></span>
                                <span data-question="38" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >38</span
                                    >
                                    <input
                                        v-model="answers.q38"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                                <span>.</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Reasons for decline Section -->
                <div class="rounded-lg bg-rose-50/50 p-5">
                    <h4 class="mb-4 border-b border-rose-200 pb-2 text-lg font-bold text-rose-800">
                        <span data-segment-id="decline" v-html="getHighlightedSegment('Reasons for the decline in farming', 'decline')"></span>
                    </h4>

                    <ul class="space-y-4 text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-rose-600">●</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span data-segment-id="quality" v-html="getHighlightedSegment('a decline in', 'quality')"></span>
                                <span data-question="39" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >39</span
                                    >
                                    <input
                                        v-model="answers.q39"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                                <span data-segment-id="quality2" v-html="getHighlightedSegment('quality', 'quality2')"></span>
                            </div>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-rose-600">●</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span data-segment-id="increase" v-html="getHighlightedSegment('an increase in', 'increase')"></span>
                                <span data-question="40" class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-600 text-xs font-bold text-white"
                                        >40</span
                                    >
                                    <input
                                        v-model="answers.q40"
                                        type="text"
                                        class="w-24 rounded-md border border-orange-400 p-2 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="......"
                                    />
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Context Menu & Note Input -->
        <Teleport to="body">
            <div
                v-if="showContextMenu"
                class="pointer-events-none fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px` }"
            >
                <div class="context-menu pointer-events-auto rounded-lg border border-gray-200 bg-white p-2 shadow-xl" @click.stop>
                    <div class="flex items-center gap-2">
                        <button
                            v-for="color in colors"
                            :key="color.name"
                            @click="applyHighlight(color.name)"
                            :class="[color.class, 'h-8 w-8 rounded-md border border-gray-300 transition-transform hover:scale-110']"
                            :title="`Highlight ${color.name}`"
                        ></button>
                        <button
                            @click="openNoteInput"
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-blue-50 transition-all hover:scale-110 hover:bg-blue-100"
                            title="Add Note"
                        >
                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="fixed z-[9999] w-80 rounded-lg border-2 border-blue-300 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        placeholder="Write your note here..."
                        class="note-input-field w-full rounded-lg border-2 border-blue-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">
                        Cancel
                    </button>
                    <button @click="saveNote" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                        Save Note
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
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
</style>
