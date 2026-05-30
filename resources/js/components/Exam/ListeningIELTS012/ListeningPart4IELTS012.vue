<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Listening Part 4 component
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

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

const textSegments = ref([
    { text: 'PART 4', offset: 0 },
    { text: 'Questions 31 – 40', offset: 7 },
    { text: 'Complete the notes below.', offset: 25 },
    { text: 'Write ONE WORD ONLY for each answer.', offset: 51 },
    { text: 'Stoicism', offset: 87 },
    { text: 'Stoicism is still relevant today because of its ', offset: 96 },
    { text: ' appeal', offset: 142 },
    { text: 'Ancient Stoics', offset: 150 },
    { text: 'Stoicism was founded over 2,000 years ago in Greece', offset: 165 },
    { text: 'The Stoics’ ideas are surprisingly well known, despite not being intended for ', offset: 218 },
    { text: '', offset: 294 },
    { text: 'Stoic principles', offset: 295 },
    { text: 'Happiness could be achieved by leading a virtuous life', offset: 312 },
    { text: 'Controlling emotions was essential', offset: 365 },
    { text: 'Epictetus said that external events cannot be controlled but the ', offset: 402 },
    { text: ' people make in response can be controlled', offset: 470 },
    { text: 'A Stoic is someone who has a different view on experiences which others would consider as ', offset: 516 },
    { text: '', offset: 601 },
    { text: 'The influence of Stoicism', offset: 602 },
    { text: 'George Washington organised a ', offset: 630 },
    { text: ' about Cato to motivate his men', offset: 665 },
    { text: 'The French artist Delacroix was a Stoic', offset: 697 },
    { text: 'Adam Smith’s ideas on ', offset: 738 },
    { text: ' were influenced by Stoicism', offset: 764 },
    { text: 'Some of today’s political leaders are inspired by the Stoics', offset: 794 },
    { text: 'Cognitive Behaviour Therapy (CBT)', offset: 856 },
    { text: '– the treatment for ', offset: 892 },
    { text: ' is based on ideas from Stoicism', offset: 914 },
    { text: '– people learn to base their thinking on ', offset: 949 },
    { text: '', offset: 991 },
    { text: 'In business, people benefit from Stoicism by identifying obstacles as ', offset: 992 },
    { text: '', offset: 1066 },
    { text: 'Relevance of Stoicism', offset: 1067 },
    { text: 'It requires a lot of ', offset: 1090 },
    { text: ', but Stoicism can help people to lead a good life', offset: 1114 },
    { text: 'It teaches people that having a strong character is more important than anything else', offset: 1169 },
]);

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
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

            result = `${before}<mark class="highlight-${highlight.color}" data-highlight-id="${highlight.id}">${highlighted}</mark>${after}`;
        }
    }

    return result;
};

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

const scrollToHighlight = async (highlightId: number) => {
    await nextTick();
    const element = document.querySelector(`mark[data-highlight-id="${highlightId}"]`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-flash');
        setTimeout(() => {
            element.classList.remove('highlight-flash');
        }, 2000);
    }
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0 || !selection.toString().trim()) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);
        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            const container = node.nodeType === Node.ELEMENT_NODE ? node : node.parentNode;
            const segmentEl = (container as Element).closest('[data-segment-text]');

            if (!segmentEl) return null;

            const segmentText = segmentEl.getAttribute('data-segment-text');
            const segment = textSegments.value.find((s) => s.text === segmentText);
            if (!segment) return null;

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
        if (rect.width > 0 || rect.height > 0) {
            contextMenuPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 5 };
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
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 60 };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
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
    });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => {
    noteInputText.value = '';
    showNoteInput.value = false;
};

const deleteNote = (id: number) => {
    notes.value = notes.value.filter((note) => note.id !== id);
};

const handleClickOutside = () => {
    if (showContextMenu.value) showContextMenu.value = false;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) showContextMenu.value = false;
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

defineExpose({ getAnswers, scrollToQuestion, scrollToHighlight, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="container mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
        <div class="mb-20 flex min-h-screen w-full flex-col rounded-2xl bg-white shadow-2xl">
            <div class="sticky top-0 z-10 flex-shrink-0 border-b border-gray-200 bg-white p-3 sm:p-4 md:p-5 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-orange-500 to-red-600 sm:h-9 sm:w-9 md:h-10 md:w-10"
                    >
                        <svg class="h-4 w-4 text-white sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-gray-700 uppercase sm:text-sm">
                            <span :data-segment-text="'PART 4'" v-html="getHighlightedSegment('PART 4')"></span>:
                            <span :data-segment-text="'Questions 31 – 40'" v-html="getHighlightedSegment('Questions 31 – 40')"></span>
                        </p>
                        <p class="text-xs text-gray-600">
                            <span :data-segment-text="'Complete the notes below.'" v-html="getHighlightedSegment('Complete the notes below.')"></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8">
                <div class="bg-opacity-50 rounded-2xl border-2 border-orange-200 bg-orange-50 p-4 shadow-lg sm:p-6">
                    <p
                        class="mb-4 text-center text-sm font-semibold tracking-wider text-orange-700 uppercase"
                        :data-segment-text="'Write ONE WORD ONLY for each answer.'"
                        v-html="getHighlightedSegment('Write ONE WORD ONLY for each answer.')"
                    ></p>
                    <h2
                        class="mb-6 text-center text-2xl font-bold text-orange-900"
                        :data-segment-text="'Stoicism'"
                        v-html="getHighlightedSegment('Stoicism')"
                    ></h2>

                    <div class="space-y-6 text-base text-gray-800">
                        <p class="flex flex-wrap items-center gap-2">
                            <span
                                :data-segment-text="'Stoicism is still relevant today because of its '"
                                v-html="getHighlightedSegment('Stoicism is still relevant today because of its ')"
                            ></span>
                            <span
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                style="
                                    box-shadow:
                                        0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                "
                                >31</span
                            >
                            <input
                                v-model="answers.q31"
                                id="question-31"
                                type="text"
                                class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <span :data-segment-text="' appeal'" v-html="getHighlightedSegment(' appeal')"></span>
                        </p>

                        <div class="space-y-3 rounded-lg border border-orange-200 bg-white p-4 shadow-inner">
                            <h3
                                class="text-lg font-semibold text-orange-800"
                                :data-segment-text="'Ancient Stoics'"
                                v-html="getHighlightedSegment('Ancient Stoics')"
                            ></h3>
                            <ul class="list-none space-y-2 pl-5">
                                <li>
                                    <span
                                        :data-segment-text="'Stoicism was founded over 2,000 years ago in Greece'"
                                        v-html="getHighlightedSegment('Stoicism was founded over 2,000 years ago in Greece')"
                                    ></span>
                                </li>
                                <li class="flex flex-wrap items-center">
                                    <span
                                        :data-segment-text="'The Stoics’ ideas are surprisingly well known, despite not being intended for '"
                                        v-html="
                                            getHighlightedSegment('The Stoics’ ideas are surprisingly well known, despite not being intended for ')
                                        "
                                    ></span>
                                    <span
                                        class="mx-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >32</span
                                    >
                                    <input
                                        v-model="answers.q32"
                                        id="question-32"
                                        type="text"
                                        class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-3 rounded-lg border border-orange-200 bg-white p-4 shadow-inner">
                            <h3
                                class="text-lg font-semibold text-orange-800"
                                :data-segment-text="'Stoic principles'"
                                v-html="getHighlightedSegment('Stoic principles')"
                            ></h3>
                            <ul class="list-none space-y-2 pl-5">
                                <li>
                                    <span
                                        :data-segment-text="'Happiness could be achieved by leading a virtuous life'"
                                        v-html="getHighlightedSegment('Happiness could be achieved by leading a virtuous life')"
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'Controlling emotions was essential'"
                                        v-html="getHighlightedSegment('Controlling emotions was essential')"
                                    ></span>
                                </li>
                                <li class="flex flex-wrap items-center">
                                    <span
                                        :data-segment-text="'Epictetus said that external events cannot be controlled but the '"
                                        v-html="getHighlightedSegment('Epictetus said that external events cannot be controlled but the ')"
                                    ></span>
                                    <span
                                        class="mx-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >33</span
                                    >
                                    <input
                                        v-model="answers.q33"
                                        id="question-33"
                                        type="text"
                                        class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <span
                                        :data-segment-text="' people make in response can be controlled'"
                                        v-html="getHighlightedSegment(' people make in response can be controlled')"
                                    ></span>
                                </li>
                                <li class="flex flex-wrap items-center">
                                    <span
                                        :data-segment-text="'A Stoic is someone who has a different view on experiences which others would consider as '"
                                        v-html="
                                            getHighlightedSegment(
                                                'A Stoic is someone who has a different view on experiences which others would consider as ',
                                            )
                                        "
                                    ></span>
                                    <span
                                        class="mx-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >34</span
                                    >
                                    <input
                                        v-model="answers.q34"
                                        id="question-34"
                                        type="text"
                                        class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-3 rounded-lg border border-orange-200 bg-white p-4 shadow-inner">
                            <h3
                                class="text-lg font-semibold text-orange-800"
                                :data-segment-text="'The influence of Stoicism'"
                                v-html="getHighlightedSegment('The influence of Stoicism')"
                            ></h3>
                            <ul class="list-none space-y-2 pl-5">
                                <li class="flex flex-wrap items-center">
                                    <span
                                        :data-segment-text="'George Washington organised a '"
                                        v-html="getHighlightedSegment('George Washington organised a ')"
                                    ></span>
                                    <span
                                        class="mx-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >35</span
                                    >
                                    <input
                                        v-model="answers.q35"
                                        id="question-35"
                                        type="text"
                                        class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <span
                                        :data-segment-text="' about Cato to motivate his men'"
                                        v-html="getHighlightedSegment(' about Cato to motivate his men')"
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'The French artist Delacroix was a Stoic'"
                                        v-html="getHighlightedSegment('The French artist Delacroix was a Stoic')"
                                    ></span>
                                </li>
                                <li class="flex flex-wrap items-center">
                                    <span
                                        :data-segment-text="'Adam Smith’s ideas on '"
                                        v-html="getHighlightedSegment('Adam Smith’s ideas on ')"
                                    ></span>
                                    <span
                                        class="mx-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >36</span
                                    >
                                    <input
                                        v-model="answers.q36"
                                        id="question-36"
                                        type="text"
                                        class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <span
                                        :data-segment-text="' were influenced by Stoicism'"
                                        v-html="getHighlightedSegment(' were influenced by Stoicism')"
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'Some of today’s political leaders are inspired by the Stoics'"
                                        v-html="getHighlightedSegment('Some of today’s political leaders are inspired by the Stoics')"
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'Cognitive Behaviour Therapy (CBT)'"
                                        v-html="getHighlightedSegment('Cognitive Behaviour Therapy (CBT)')"
                                    ></span>
                                    <ul class="mt-1 list-none space-y-1 pl-6">
                                        <li class="flex flex-wrap items-center">
                                            <span
                                                :data-segment-text="'– the treatment for '"
                                                v-html="getHighlightedSegment('– the treatment for ')"
                                            ></span>
                                            <span
                                                class="mx-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >37</span
                                            >
                                            <input
                                                v-model="answers.q37"
                                                id="question-37"
                                                type="text"
                                                class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                            />
                                            <span
                                                :data-segment-text="' is based on ideas from Stoicism'"
                                                v-html="getHighlightedSegment(' is based on ideas from Stoicism')"
                                            ></span>
                                        </li>
                                        <li class="flex flex-wrap items-center">
                                            <span
                                                :data-segment-text="'– people learn to base their thinking on '"
                                                v-html="getHighlightedSegment('– people learn to base their thinking on ')"
                                            ></span>
                                            <span
                                                class="mx-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >38</span
                                            >
                                            <input
                                                v-model="answers.q38"
                                                id="question-38"
                                                type="text"
                                                class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                            />
                                        </li>
                                    </ul>
                                </li>
                                <li class="flex flex-wrap items-center">
                                    <span
                                        :data-segment-text="'In business, people benefit from Stoicism by identifying obstacles as '"
                                        v-html="getHighlightedSegment('In business, people benefit from Stoicism by identifying obstacles as ')"
                                    ></span>
                                    <span
                                        class="mx-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >39</span
                                    >
                                    <input
                                        v-model="answers.q39"
                                        id="question-39"
                                        type="text"
                                        class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-3 rounded-lg border border-orange-200 bg-white p-4 shadow-inner">
                            <h3
                                class="text-lg font-semibold text-orange-800"
                                :data-segment-text="'Relevance of Stoicism'"
                                v-html="getHighlightedSegment('Relevance of Stoicism')"
                            ></h3>
                            <ul class="list-none space-y-2 pl-5">
                                <li class="flex flex-wrap items-center">
                                    <span :data-segment-text="'It requires a lot of '" v-html="getHighlightedSegment('It requires a lot of ')"></span>
                                    <span
                                        class="mx-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(249, 115, 22, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >40</span
                                    >
                                    <input
                                        v-model="answers.q40"
                                        id="question-40"
                                        type="text"
                                        class="m-1 inline-block w-40 rounded-md border border-orange-500 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <span
                                        :data-segment-text="', but Stoicism can help people to lead a good life'"
                                        v-html="getHighlightedSegment(', but Stoicism can help people to lead a good life')"
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'It teaches people that having a strong character is more important than anything else'"
                                        v-html="
                                            getHighlightedSegment(
                                                'It teaches people that having a strong character is more important than anything else',
                                            )
                                        "
                                    ></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="pointer-events-auto fixed z-[9999] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
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
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-orange-50 transition-all hover:scale-110 hover:bg-orange-100"
                            title="Add Note"
                        >
                            <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="fixed z-[9999] w-80 rounded-lg border-2 border-orange-300 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        placeholder="Write your note here..."
                        class="note-input-field w-full rounded-lg border-2 border-orange-200 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500 focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        @click="cancelNote"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveNote"
                        class="rounded-md bg-orange-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-orange-700"
                    >
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
    background-color: rgba(254, 240, 138, 0.5);
}
.highlight-green {
    background-color: rgba(187, 247, 208, 0.5);
}
.highlight-blue {
    background-color: rgba(191, 219, 254, 0.5);
}
.highlight-pink {
    background-color: rgba(251, 207, 232, 0.5);
}
.highlight-orange {
    background-color: rgba(254, 215, 170, 0.5);
}

.highlight-question {
    animation: highlightPulse 2s ease-in-out;
}
@keyframes highlightPulse {
    0% {
        background-color: rgba(249, 115, 22, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(249, 115, 22, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(249, 115, 22, 0.1);
        transform: scale(1);
    }
}
.highlight-flash {
    animation: flashHighlight 1.5s ease-out;
}
@keyframes flashHighlight {
    0% {
        background-color: yellow;
    }
    100% {
        background-color: transparent;
    }
}
</style>
