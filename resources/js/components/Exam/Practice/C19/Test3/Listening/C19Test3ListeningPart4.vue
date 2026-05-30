<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    phone?: string | null;
    examId?: string;
}

defineProps<Props>();

// Answers State
const answers = ref<Record<string, string>>({
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
const contentTextRef = ref<HTMLDivElement | null>(null);
const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Text segments
const textSegments = ref([
    { id: 'header', text: 'PART 4', offset: 0 },
    { id: 'questions', text: 'Questions 31 - 40', offset: 7 },
    { id: 'instruction', text: 'Complete the notes below.', offset: 25 },
    { id: 'instructionb', text: 'Write ONE WORD ONLY for each answer.', offset: 51 },
    { id: 'title', text: 'Microplastics', offset: 88 },
    { id: 'whereFrom', text: 'Where microplastics come from', offset: 102 },
    { id: 'fibres', text: 'fibres from some', offset: 132 },
    { id: 'duringWashing', text: 'during washing', offset: 157 },
    { id: 'breakdown', text: 'the breakdown of large pieces of plastic', offset: 172 },
    { id: 'wasteIndustry', text: 'waste from industry', offset: 213 },
    { id: 'tyres', text: 'the action of vehicle tyres on roads', offset: 233 },
    { id: 'effectsTitle', text: 'Effects of microplastics', offset: 270 },
    { id: 'injuries', text: 'They cause injuries to the', offset: 295 },
    { id: 'wildlife', text: 'of wildlife and affect their digestive systems.', offset: 330 },
    { id: 'foodChain', text: 'They enter the food chain, e.g., in bottled and tap water,', offset: 378 },
    { id: 'seafood', text: 'and seafood.', offset: 445 },
    { id: 'humanHealth', text: 'They may not affect human health, but they are already banned in skin cleaning products and', offset: 458 },
    { id: 'someCountries', text: 'in some countries.', offset: 560 },
    { id: 'soilEntry', text: 'Microplastics enter the soil through the air, rain and', offset: 579 },
    { id: 'studyTitle', text: 'Microplastics in the soil - a study by Anglia Ruskin University', offset: 645 },
    { id: 'earthworms', text: 'Earthworms are important because they add', offset: 709 },
    { id: 'toSoil', text: 'to the soil.', offset: 760 },
    { id: 'studyAimed', text: 'The study aimed to find whether microplastics in earthworms affect the', offset: 773 },
    { id: 'ofPlants', text: 'of plants.', offset: 853 },
    { id: 'studyFound', text: 'The study found that microplastics caused:', offset: 864 },
    { id: 'weightLoss', text: 'loss in earthworms', offset: 917 },
    { id: 'fewerSeeds', text: 'fewer seeds to germinate', offset: 936 },
    { id: 'riseLevel', text: 'a rise in the level of', offset: 961 },
    { id: 'inSoil', text: 'in the soil.', offset: 993 },
    { id: 'concluded', text: 'The study concluded:', offset: 1006 },
    { id: 'soilProcess', text: 'soil should be seen as an important natural process.', offset: 1027 },
    { id: 'changesToSoil', text: 'changes to soil damage both ecosystems and', offset: 1080 },
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

// Handle text selection
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
    addHighlight(selectedText.value, 'yellow', selectionRange.value.start, selectionRange.value.end);
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
            class="animate-fadeIn w-full rounded-2xl border border-amber-100/50 bg-gradient-to-br from-white via-amber-50/30 to-orange-50/50 p-6 shadow-2xl backdrop-blur-xl"
        >
            <!-- Header -->
            <div class="-m-6 mb-6 flex-shrink-0 rounded-t-xl border-b border-amber-200/50 bg-gradient-to-r from-amber-50 to-orange-50 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="animate-pulse-slow flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-lg shadow-amber-500/30"
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
                            class="bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-xl font-bold tracking-widest text-transparent uppercase"
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
            <div class="animate-slideUp mb-6" style="animation-delay: 0.1s">
                <div class="mb-2 inline-block rounded-lg bg-amber-100 px-3 py-1">
                    <span class="text-sm font-semibold text-amber-700">Questions 31-40</span>
                </div>
                <p class="mb-1 text-sm text-gray-600">
                    <span data-segment-id="instruction" v-html="getHighlightedSegment('Complete the notes below.', 'instruction')"></span>
                </p>
                <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-amber-100 to-orange-100 px-4 py-2 shadow-sm">
                    <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                        ></path>
                    </svg>
                    <span
                        data-segment-id="instructionb"
                        class="text-sm font-semibold text-amber-800"
                        v-html="getHighlightedSegment('Write ONE WORD ONLY for each answer.', 'instructionb')"
                    ></span>
                </div>
            </div>

            <!-- Main Content -->
            <div class="animate-slideUp rounded-2xl border border-amber-100 bg-white/80 p-6 shadow-lg" style="animation-delay: 0.2s">
                <!-- Title -->
                <h3
                    class="mb-6 border-b-2 border-amber-200 bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text pb-2 text-center text-2xl font-bold text-transparent"
                >
                    <span data-segment-id="title" v-html="getHighlightedSegment('Microplastics', 'title')"></span>
                </h3>

                <!-- Where microplastics come from -->
                <div class="mb-8">
                    <h4 class="mb-4 flex items-center gap-2 text-lg font-semibold text-amber-800">
                        <div class="h-2 w-2 rounded-full bg-amber-500"></div>
                        <span data-segment-id="whereFrom" v-html="getHighlightedSegment('Where microplastics come from', 'whereFrom')"></span>
                    </h4>

                    <div class="ml-4 space-y-4">
                        <!-- Question 31 -->
                        <div
                            data-question="31"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-amber-50/80 to-orange-50/80 p-4 transition-all duration-300 hover:from-amber-100/80 hover:to-orange-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >31</span
                            >
                            <span data-segment-id="fibres" class="text-gray-700" v-html="getHighlightedSegment('fibres from some', 'fibres')"></span>
                            <input
                                v-model="answers.q31"
                                data-question="31"
                                type="text"
                                class="w-32 rounded-xl border-2 border-amber-300 p-2.5 text-sm shadow-sm transition-all focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                placeholder="......"
                            />
                            <span
                                data-segment-id="duringWashing"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('during washing', 'duringWashing')"
                            ></span>
                        </div>

                        <div class="ml-8 text-sm text-gray-600">
                            <span
                                data-segment-id="breakdown"
                                v-html="getHighlightedSegment('the breakdown of large pieces of plastic', 'breakdown')"
                            ></span>
                        </div>
                        <div class="ml-8 text-sm text-gray-600">
                            <span data-segment-id="wasteIndustry" v-html="getHighlightedSegment('waste from industry', 'wasteIndustry')"></span>
                        </div>
                        <div class="ml-8 text-sm text-gray-600">
                            <span data-segment-id="tyres" v-html="getHighlightedSegment('the action of vehicle tyres on roads', 'tyres')"></span>
                        </div>
                    </div>
                </div>

                <!-- Effects of microplastics -->
                <div class="mb-8">
                    <h4 class="mb-4 flex items-center gap-2 text-lg font-semibold text-orange-800">
                        <div class="h-2 w-2 rounded-full bg-orange-500"></div>
                        <span data-segment-id="effectsTitle" v-html="getHighlightedSegment('Effects of microplastics', 'effectsTitle')"></span>
                    </h4>

                    <div class="ml-4 space-y-4">
                        <!-- Question 32 -->
                        <div
                            data-question="32"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-orange-50/80 to-amber-50/80 p-4 transition-all duration-300 hover:from-orange-100/80 hover:to-amber-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >32</span
                            >
                            <span
                                data-segment-id="injuries"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('They cause injuries to the', 'injuries')"
                            ></span>
                            <input
                                v-model="answers.q32"
                                data-question="32"
                                type="text"
                                class="w-28 rounded-xl border-2 border-orange-300 p-2.5 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                placeholder="......"
                            />
                            <span
                                data-segment-id="wildlife"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('of wildlife and affect their digestive systems.', 'wildlife')"
                            ></span>
                        </div>

                        <!-- Question 33 -->
                        <div
                            data-question="33"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-amber-50/80 to-orange-50/80 p-4 transition-all duration-300 hover:from-amber-100/80 hover:to-orange-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >33</span
                            >
                            <span
                                data-segment-id="foodChain"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('They enter the food chain, e.g., in bottled and tap water,', 'foodChain')"
                            ></span>
                            <input
                                v-model="answers.q33"
                                data-question="33"
                                type="text"
                                class="w-28 rounded-xl border-2 border-amber-300 p-2.5 text-sm shadow-sm transition-all focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                placeholder="......"
                            />
                            <span data-segment-id="seafood" class="text-gray-700" v-html="getHighlightedSegment('and seafood.', 'seafood')"></span>
                        </div>

                        <!-- Question 34 -->
                        <div
                            data-question="34"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-orange-50/80 to-amber-50/80 p-4 transition-all duration-300 hover:from-orange-100/80 hover:to-amber-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >34</span
                            >
                            <span
                                data-segment-id="humanHealth"
                                class="text-gray-700"
                                v-html="
                                    getHighlightedSegment(
                                        'They may not affect human health, but they are already banned in skin cleaning products and',
                                        'humanHealth',
                                    )
                                "
                            ></span>
                            <input
                                v-model="answers.q34"
                                data-question="34"
                                type="text"
                                class="w-28 rounded-xl border-2 border-orange-300 p-2.5 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                placeholder="......"
                            />
                            <span
                                data-segment-id="someCountries"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('in some countries.', 'someCountries')"
                            ></span>
                        </div>

                        <!-- Question 35 -->
                        <div
                            data-question="35"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-amber-50/80 to-orange-50/80 p-4 transition-all duration-300 hover:from-amber-100/80 hover:to-orange-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >35</span
                            >
                            <span
                                data-segment-id="soilEntry"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Microplastics enter the soil through the air, rain and', 'soilEntry')"
                            ></span>
                            <input
                                v-model="answers.q35"
                                data-question="35"
                                type="text"
                                class="w-28 rounded-xl border-2 border-amber-300 p-2.5 text-sm shadow-sm transition-all focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                placeholder="......"
                            />
                        </div>
                    </div>
                </div>

                <!-- Study section -->
                <div class="border-t-2 border-dashed border-amber-200 pt-6">
                    <h4 class="mb-4 flex items-center gap-2 text-lg font-semibold text-amber-800">
                        <div class="h-2 w-2 rounded-full bg-amber-500"></div>
                        <span
                            data-segment-id="studyTitle"
                            v-html="getHighlightedSegment('Microplastics in the soil - a study by Anglia Ruskin University', 'studyTitle')"
                        ></span>
                    </h4>

                    <div class="ml-4 space-y-4">
                        <!-- Question 36 -->
                        <div
                            data-question="36"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-amber-50/80 to-orange-50/80 p-4 transition-all duration-300 hover:from-amber-100/80 hover:to-orange-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >36</span
                            >
                            <span
                                data-segment-id="earthworms"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('Earthworms are important because they add', 'earthworms')"
                            ></span>
                            <input
                                v-model="answers.q36"
                                data-question="36"
                                type="text"
                                class="w-28 rounded-xl border-2 border-amber-300 p-2.5 text-sm shadow-sm transition-all focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                placeholder="......"
                            />
                            <span data-segment-id="toSoil" class="text-gray-700" v-html="getHighlightedSegment('to the soil.', 'toSoil')"></span>
                        </div>

                        <!-- Question 37 -->
                        <div
                            data-question="37"
                            class="group flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-orange-50/80 to-amber-50/80 p-4 transition-all duration-300 hover:from-orange-100/80 hover:to-amber-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >37</span
                            >
                            <span
                                data-segment-id="studyAimed"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('The study aimed to find whether microplastics in earthworms affect the', 'studyAimed')"
                            ></span>
                            <input
                                v-model="answers.q37"
                                data-question="37"
                                type="text"
                                class="w-28 rounded-xl border-2 border-orange-300 p-2.5 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                placeholder="......"
                            />
                            <span data-segment-id="ofPlants" class="text-gray-700" v-html="getHighlightedSegment('of plants.', 'ofPlants')"></span>
                        </div>

                        <div class="ml-8 font-medium text-gray-700">
                            <span
                                data-segment-id="studyFound"
                                v-html="getHighlightedSegment('The study found that microplastics caused:', 'studyFound')"
                            ></span>
                        </div>

                        <!-- Question 38 -->
                        <div
                            data-question="38"
                            class="group ml-8 flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-amber-50/80 to-orange-50/80 p-4 transition-all duration-300 hover:from-amber-100/80 hover:to-orange-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >38</span
                            >
                            <input
                                v-model="answers.q38"
                                data-question="38"
                                type="text"
                                class="w-28 rounded-xl border-2 border-amber-300 p-2.5 text-sm shadow-sm transition-all focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                placeholder="......"
                            />
                            <span
                                data-segment-id="weightLoss"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('loss in earthworms', 'weightLoss')"
                            ></span>
                        </div>

                        <div class="ml-8 text-sm text-gray-600">
                            <span data-segment-id="fewerSeeds" v-html="getHighlightedSegment('fewer seeds to germinate', 'fewerSeeds')"></span>
                        </div>

                        <!-- Question 39 -->
                        <div
                            data-question="39"
                            class="group ml-8 flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-orange-50/80 to-amber-50/80 p-4 transition-all duration-300 hover:from-orange-100/80 hover:to-amber-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >39</span
                            >
                            <span
                                data-segment-id="riseLevel"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('a rise in the level of', 'riseLevel')"
                            ></span>
                            <input
                                v-model="answers.q39"
                                data-question="39"
                                type="text"
                                class="w-28 rounded-xl border-2 border-orange-300 p-2.5 text-sm shadow-sm transition-all focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                placeholder="......"
                            />
                            <span data-segment-id="inSoil" class="text-gray-700" v-html="getHighlightedSegment('in the soil.', 'inSoil')"></span>
                        </div>

                        <div class="mt-4 ml-8 font-medium text-gray-700">
                            <span data-segment-id="concluded" v-html="getHighlightedSegment('The study concluded:', 'concluded')"></span>
                        </div>

                        <div class="ml-8 text-sm text-gray-600">
                            <span
                                data-segment-id="soilProcess"
                                v-html="getHighlightedSegment('soil should be seen as an important natural process.', 'soilProcess')"
                            ></span>
                        </div>

                        <!-- Question 40 -->
                        <div
                            data-question="40"
                            class="group ml-8 flex flex-wrap items-center gap-3 rounded-xl bg-gradient-to-r from-amber-50/80 to-orange-50/80 p-4 transition-all duration-300 hover:from-amber-100/80 hover:to-orange-100/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >40</span
                            >
                            <span
                                data-segment-id="changesToSoil"
                                class="text-gray-700"
                                v-html="getHighlightedSegment('changes to soil damage both ecosystems and', 'changesToSoil')"
                            ></span>
                            <input
                                v-model="answers.q40"
                                data-question="40"
                                type="text"
                                class="w-28 rounded-xl border-2 border-amber-300 p-2.5 text-sm shadow-sm transition-all focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                placeholder="......"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu -->
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
                                class="flex h-9 w-9 items-center justify-center rounded-lg border-2 border-amber-200 bg-amber-50 transition-all duration-200 hover:scale-110 hover:bg-amber-100 hover:shadow-md"
                                title="Add Note"
                            >
                                <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="fixed z-[9999] w-80 rounded-xl border-2 border-amber-300 bg-white p-4 shadow-2xl"
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
                            class="note-input-field w-full rounded-lg border-2 border-amber-200 px-4 py-3 text-sm transition-all focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
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
                            class="rounded-lg bg-gradient-to-r from-amber-600 to-orange-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-amber-500/20 transition-all hover:from-amber-700 hover:to-orange-700"
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
