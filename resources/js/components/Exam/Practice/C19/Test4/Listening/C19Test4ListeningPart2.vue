<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Answers State
const answers = ref({
    q11: [] as string[],
    q12: [] as string[],
    q13: [] as string[],
    q14: [] as string[],
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
});

// For compatibility with scoring system - flatten checkbox answers
const getAnswers = () => {
    return {
        q11: answers.value.q11.sort().join(','),
        q12: answers.value.q12.sort().join(','),
        q13: answers.value.q13.sort().join(','),
        q14: answers.value.q14.sort().join(','),
        q15: answers.value.q15,
        q16: answers.value.q16,
        q17: answers.value.q17,
        q18: answers.value.q18,
        q19: answers.value.q19,
        q20: answers.value.q20,
    };
};

// Toggle checkbox selection (max 2)
const toggleAnswer11_12 = (value: string) => {
    const combined = [...answers.value.q11, ...answers.value.q12];
    if (combined.includes(value)) {
        answers.value.q11 = answers.value.q11.filter((v) => v !== value);
        answers.value.q12 = answers.value.q12.filter((v) => v !== value);
    } else if (combined.length < 2) {
        if (answers.value.q11.length === 0) {
            answers.value.q11.push(value);
        } else {
            answers.value.q12.push(value);
        }
    }
};

const toggleAnswer13_14 = (value: string) => {
    const combined = [...answers.value.q13, ...answers.value.q14];
    if (combined.includes(value)) {
        answers.value.q13 = answers.value.q13.filter((v) => v !== value);
        answers.value.q14 = answers.value.q14.filter((v) => v !== value);
    } else if (combined.length < 2) {
        if (answers.value.q13.length === 0) {
            answers.value.q13.push(value);
        } else {
            answers.value.q14.push(value);
        }
    }
};

const isSelected11_12 = (value: string) => {
    return answers.value.q11.includes(value) || answers.value.q12.includes(value);
};

const isSelected13_14 = (value: string) => {
    return answers.value.q13.includes(value) || answers.value.q14.includes(value);
};

// Highlighting & Notes
const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();
const contentTextRef = ref<HTMLElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Text segments
const textSegments = ref([
    { id: 'header', text: 'PART 2', offset: 0 },
    { id: 'questions', text: 'Questions 11 - 20', offset: 7 },
    { id: 'q11title', text: 'Questions 11 and 12', offset: 25 },
    { id: 'q11instruction', text: 'Choose TWO letters, A-E.', offset: 45 },
    { id: 'q11question', text: 'Which TWO problems with some training programmes for new runners does Liz mention?', offset: 70 },
    { id: 'q11a', text: 'There is a risk of serious injury.', offset: 152 },
    { id: 'q11b', text: 'They are unsuitable for certain age groups.', offset: 187 },
    { id: 'q11c', text: 'They are unsuitable for people with health issues.', offset: 231 },
    { id: 'q11d', text: 'It is difficult to stay motivated.', offset: 282 },
    { id: 'q11e', text: 'There is a lack of individual support.', offset: 317 },
    { id: 'q13title', text: 'Questions 13 and 14', offset: 356 },
    { id: 'q13instruction', text: 'Choose TWO letters, A-E.', offset: 376 },
    { id: 'q13question', text: 'Which TWO tips does Liz recommend for new runners?', offset: 401 },
    { id: 'q13a', text: 'doing two runs a week', offset: 452 },
    { id: 'q13b', text: 'running in the evening', offset: 474 },
    { id: 'q13c', text: 'going on runs with a friend', offset: 497 },
    { id: 'q13d', text: 'listening to music during runs', offset: 525 },
    { id: 'q13e', text: 'running very slowly', offset: 556 },
    { id: 'q15title', text: 'Questions 15-18', offset: 576 },
    {
        id: 'q15question',
        text: 'What reason prevented each of the following members of the Compton Park Runners Club from joining until recently?',
        offset: 592,
    },
    { id: 'q15instruction', text: 'Write the correct letter, A, B, or C next to Questions 15-18.', offset: 706 },
    { id: 'reasons', text: 'Reasons', offset: 768 },
    { id: 'reasonA', text: 'a lack of confidence', offset: 776 },
    { id: 'reasonB', text: 'a dislike of running', offset: 797 },
    { id: 'reasonC', text: 'a lack of time', offset: 818 },
    { id: 'members', text: 'Club members', offset: 833 },
    { id: 'ceri', text: 'Ceri', offset: 846 },
    { id: 'james', text: 'James', offset: 851 },
    { id: 'leo', text: 'Leo', offset: 857 },
    { id: 'mark', text: 'Mark', offset: 861 },
    { id: 'q19title', text: 'Questions 19 and 20', offset: 866 },
    { id: 'q19instruction', text: 'Choose the correct letter, A, B or C.', offset: 886 },
    { id: 'q19question', text: 'What does Liz say about running her first marathon?', offset: 924 },
    { id: 'q19a', text: 'It had always been her ambition.', offset: 976 },
    { id: 'q19b', text: 'Her husband persuaded her to do it.', offset: 1009 },
    { id: 'q19c', text: 'She nearly gave up before the end.', offset: 1045 },
    { id: 'q20question', text: 'Liz says new runners should sign up for a race', offset: 1080 },
    { id: 'q20a', text: 'every six months.', offset: 1127 },
    { id: 'q20b', text: 'within a few weeks of taking up running.', offset: 1145 },
    { id: 'q20c', text: 'after completing several practice runs.', offset: 1186 },
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
        el.classList.add('ring-4', 'ring-rose-400', 'ring-offset-2');
        setTimeout(() => el.classList.remove('ring-4', 'ring-rose-400', 'ring-offset-2'), 2000);
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
    getAnswers,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div class="mx-auto mb-16 px-4 py-8">
        <div
            ref="contentTextRef"
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
                            <span data-segment-id="header" v-html="getHighlightedSegment('PART 2', 'header')"></span>
                        </h2>
                        <h1 class="text-base font-semibold text-gray-700">
                            <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 11 - 20', 'questions')"></span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Questions 11-12: Choose TWO -->
            <div class="animate-slideUp mb-8" style="animation-delay: 0.1s">
                <div class="rounded-2xl border border-rose-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <h3 class="mb-2 text-lg font-bold text-rose-800">
                        <span data-segment-id="q11title" v-html="getHighlightedSegment('Questions 11 and 12', 'q11title')"></span>
                    </h3>
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-rose-100 to-pink-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="q11instruction"
                            class="text-sm font-semibold text-rose-800"
                            v-html="getHighlightedSegment('Choose TWO letters, A-E.', 'q11instruction')"
                        ></span>
                    </div>

                    <p class="mb-4 font-semibold text-gray-800">
                        <span
                            data-segment-id="q11question"
                            v-html="
                                getHighlightedSegment(
                                    'Which TWO problems with some training programmes for new runners does Liz mention?',
                                    'q11question',
                                )
                            "
                        ></span>
                    </p>

                    <div data-question="11" class="space-y-3">
                        <label
                            v-for="(option, idx) in [
                                { value: 'A', text: 'There is a risk of serious injury.', id: 'q11a' },
                                { value: 'B', text: 'They are unsuitable for certain age groups.', id: 'q11b' },
                                { value: 'C', text: 'They are unsuitable for people with health issues.', id: 'q11c' },
                                { value: 'D', text: 'It is difficult to stay motivated.', id: 'q11d' },
                                { value: 'E', text: 'There is a lack of individual support.', id: 'q11e' },
                            ]"
                            :key="option.value"
                            class="group flex cursor-pointer items-center gap-3 rounded-xl p-4 transition-all duration-200"
                            :class="
                                isSelected11_12(option.value)
                                    ? 'border-2 border-rose-400 bg-rose-100'
                                    : 'border-2 border-transparent bg-rose-50/50 hover:bg-rose-100/50'
                            "
                            @click="toggleAnswer11_12(option.value)"
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md border-2 transition-all"
                                :class="isSelected11_12(option.value) ? 'border-rose-500 bg-rose-500' : 'border-gray-300 bg-white'"
                            >
                                <svg
                                    v-if="isSelected11_12(option.value)"
                                    class="h-4 w-4 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="w-6 font-bold text-rose-700">{{ option.value }}</span>
                            <span :data-segment-id="option.id" class="text-gray-700" v-html="getHighlightedSegment(option.text, option.id)"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Questions 13-14: Choose TWO -->
            <div class="animate-slideUp mb-8" style="animation-delay: 0.15s">
                <div class="rounded-2xl border border-rose-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <h3 class="mb-2 text-lg font-bold text-rose-800">
                        <span data-segment-id="q13title" v-html="getHighlightedSegment('Questions 13 and 14', 'q13title')"></span>
                    </h3>
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-pink-100 to-rose-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="q13instruction"
                            class="text-sm font-semibold text-pink-800"
                            v-html="getHighlightedSegment('Choose TWO letters, A-E.', 'q13instruction')"
                        ></span>
                    </div>

                    <p class="mb-4 font-semibold text-gray-800">
                        <span
                            data-segment-id="q13question"
                            v-html="getHighlightedSegment('Which TWO tips does Liz recommend for new runners?', 'q13question')"
                        ></span>
                    </p>

                    <div data-question="13" class="space-y-3">
                        <label
                            v-for="(option, idx) in [
                                { value: 'A', text: 'doing two runs a week', id: 'q13a' },
                                { value: 'B', text: 'running in the evening', id: 'q13b' },
                                { value: 'C', text: 'going on runs with a friend', id: 'q13c' },
                                { value: 'D', text: 'listening to music during runs', id: 'q13d' },
                                { value: 'E', text: 'running very slowly', id: 'q13e' },
                            ]"
                            :key="option.value"
                            class="group flex cursor-pointer items-center gap-3 rounded-xl p-4 transition-all duration-200"
                            :class="
                                isSelected13_14(option.value)
                                    ? 'border-2 border-pink-400 bg-pink-100'
                                    : 'border-2 border-transparent bg-pink-50/50 hover:bg-pink-100/50'
                            "
                            @click="toggleAnswer13_14(option.value)"
                        >
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-md border-2 transition-all"
                                :class="isSelected13_14(option.value) ? 'border-pink-500 bg-pink-500' : 'border-gray-300 bg-white'"
                            >
                                <svg
                                    v-if="isSelected13_14(option.value)"
                                    class="h-4 w-4 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="w-6 font-bold text-pink-700">{{ option.value }}</span>
                            <span :data-segment-id="option.id" class="text-gray-700" v-html="getHighlightedSegment(option.text, option.id)"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Questions 15-18: Matching -->
            <div class="animate-slideUp mb-8 border-t-2 border-dashed border-rose-200 pt-8" style="animation-delay: 0.2s">
                <div class="rounded-2xl border border-rose-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <h3 class="mb-2 text-lg font-bold text-rose-800">
                        <span data-segment-id="q15title" v-html="getHighlightedSegment('Questions 15-18', 'q15title')"></span>
                    </h3>
                    <p class="mb-3 text-gray-700">
                        <span
                            data-segment-id="q15question"
                            v-html="
                                getHighlightedSegment(
                                    'What reason prevented each of the following members of the Compton Park Runners Club from joining until recently?',
                                    'q15question',
                                )
                            "
                        ></span>
                    </p>
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-fuchsia-100 to-rose-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="q15instruction"
                            class="text-sm font-semibold text-fuchsia-800"
                            v-html="getHighlightedSegment('Write the correct letter, A, B, or C next to Questions 15-18.', 'q15instruction')"
                        ></span>
                    </div>

                    <!-- Reasons Box -->
                    <div class="mb-6 rounded-xl border border-fuchsia-200 bg-gradient-to-r from-fuchsia-50 to-rose-50 p-4">
                        <h4 class="mb-3 text-center font-bold text-fuchsia-800">
                            <span data-segment-id="reasons" v-html="getHighlightedSegment('Reasons', 'reasons')"></span>
                        </h4>
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-fuchsia-700">A</span>
                                <span
                                    data-segment-id="reasonA"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('a lack of confidence', 'reasonA')"
                                ></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-fuchsia-700">B</span>
                                <span
                                    data-segment-id="reasonB"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('a dislike of running', 'reasonB')"
                                ></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-6 font-bold text-fuchsia-700">C</span>
                                <span
                                    data-segment-id="reasonC"
                                    class="text-gray-700"
                                    v-html="getHighlightedSegment('a lack of time', 'reasonC')"
                                ></span>
                            </div>
                        </div>
                    </div>

                    <h4 class="mb-4 font-bold text-gray-800">
                        <span data-segment-id="members" v-html="getHighlightedSegment('Club members', 'members')"></span>
                    </h4>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div
                            data-question="15"
                            class="group flex items-center gap-4 rounded-xl border border-fuchsia-100 bg-gradient-to-r from-fuchsia-50 to-rose-50 p-4 shadow-sm transition-all duration-300 hover:border-fuchsia-200 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-fuchsia-500 to-rose-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >15</span
                            >
                            <span
                                data-segment-id="ceri"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('Ceri', 'ceri')"
                            ></span>
                            <select
                                v-model="answers.q15"
                                class="h-11 w-20 cursor-pointer rounded-xl border-2 border-fuchsia-300 bg-white text-center text-lg font-bold text-fuchsia-700 shadow-sm transition-all focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/20"
                            >
                                <option value="" disabled>?</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>

                        <div
                            data-question="16"
                            class="group flex items-center gap-4 rounded-xl border border-fuchsia-100 bg-gradient-to-r from-fuchsia-50 to-rose-50 p-4 shadow-sm transition-all duration-300 hover:border-fuchsia-200 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-fuchsia-500 to-rose-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >16</span
                            >
                            <span
                                data-segment-id="james"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('James', 'james')"
                            ></span>
                            <select
                                v-model="answers.q16"
                                class="h-11 w-20 cursor-pointer rounded-xl border-2 border-fuchsia-300 bg-white text-center text-lg font-bold text-fuchsia-700 shadow-sm transition-all focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/20"
                            >
                                <option value="" disabled>?</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>

                        <div
                            data-question="17"
                            class="group flex items-center gap-4 rounded-xl border border-fuchsia-100 bg-gradient-to-r from-fuchsia-50 to-rose-50 p-4 shadow-sm transition-all duration-300 hover:border-fuchsia-200 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-fuchsia-500 to-rose-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >17</span
                            >
                            <span data-segment-id="leo" class="flex-1 font-medium text-gray-800" v-html="getHighlightedSegment('Leo', 'leo')"></span>
                            <select
                                v-model="answers.q17"
                                class="h-11 w-20 cursor-pointer rounded-xl border-2 border-fuchsia-300 bg-white text-center text-lg font-bold text-fuchsia-700 shadow-sm transition-all focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/20"
                            >
                                <option value="" disabled>?</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>

                        <div
                            data-question="18"
                            class="group flex items-center gap-4 rounded-xl border border-fuchsia-100 bg-gradient-to-r from-fuchsia-50 to-rose-50 p-4 shadow-sm transition-all duration-300 hover:border-fuchsia-200 hover:shadow-md"
                        >
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-fuchsia-500 to-rose-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                >18</span
                            >
                            <span
                                data-segment-id="mark"
                                class="flex-1 font-medium text-gray-800"
                                v-html="getHighlightedSegment('Mark', 'mark')"
                            ></span>
                            <select
                                v-model="answers.q18"
                                class="h-11 w-20 cursor-pointer rounded-xl border-2 border-fuchsia-300 bg-white text-center text-lg font-bold text-fuchsia-700 shadow-sm transition-all focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/20"
                            >
                                <option value="" disabled>?</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions 19-20: Multiple Choice -->
            <div class="animate-slideUp border-t-2 border-dashed border-rose-200 pt-8" style="animation-delay: 0.25s">
                <div class="rounded-2xl border border-rose-100 bg-white/80 p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <h3 class="mb-2 text-lg font-bold text-rose-800">
                        <span data-segment-id="q19title" v-html="getHighlightedSegment('Questions 19 and 20', 'q19title')"></span>
                    </h3>
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-rose-100 to-pink-100 px-4 py-2 shadow-sm">
                        <svg class="h-4 w-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <span
                            data-segment-id="q19instruction"
                            class="text-sm font-semibold text-rose-800"
                            v-html="getHighlightedSegment('Choose the correct letter, A, B or C.', 'q19instruction')"
                        ></span>
                    </div>

                    <div class="space-y-6">
                        <!-- Question 19 -->
                        <div
                            data-question="19"
                            class="rounded-xl border border-rose-100 bg-gradient-to-r from-rose-50/80 to-pink-50/80 p-5 transition-all duration-300 hover:shadow-md"
                        >
                            <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                                <span
                                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-sm font-bold text-white shadow-lg"
                                    >19</span
                                >
                                <span
                                    data-segment-id="q19question"
                                    v-html="getHighlightedSegment('What does Liz say about running her first marathon?', 'q19question')"
                                ></span>
                            </p>
                            <div class="ml-11 space-y-2">
                                <label
                                    class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-rose-100/50"
                                >
                                    <input type="radio" v-model="answers.q19" value="A" class="h-5 w-5 text-rose-600 focus:ring-rose-500" />
                                    <span class="w-6 font-bold text-rose-700">A</span>
                                    <span
                                        data-segment-id="q19a"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('It had always been her ambition.', 'q19a')"
                                    ></span>
                                </label>
                                <label
                                    class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-rose-100/50"
                                >
                                    <input type="radio" v-model="answers.q19" value="B" class="h-5 w-5 text-rose-600 focus:ring-rose-500" />
                                    <span class="w-6 font-bold text-rose-700">B</span>
                                    <span
                                        data-segment-id="q19b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('Her husband persuaded her to do it.', 'q19b')"
                                    ></span>
                                </label>
                                <label
                                    class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-rose-100/50"
                                >
                                    <input type="radio" v-model="answers.q19" value="C" class="h-5 w-5 text-rose-600 focus:ring-rose-500" />
                                    <span class="w-6 font-bold text-rose-700">C</span>
                                    <span
                                        data-segment-id="q19c"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('She nearly gave up before the end.', 'q19c')"
                                    ></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 20 -->
                        <div
                            data-question="20"
                            class="rounded-xl border border-rose-100 bg-gradient-to-r from-rose-50/80 to-pink-50/80 p-5 transition-all duration-300 hover:shadow-md"
                        >
                            <p class="mb-4 flex items-start gap-3 font-semibold text-gray-800">
                                <span
                                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-600 text-sm font-bold text-white shadow-lg"
                                    >20</span
                                >
                                <span
                                    data-segment-id="q20question"
                                    v-html="getHighlightedSegment('Liz says new runners should sign up for a race', 'q20question')"
                                ></span>
                            </p>
                            <div class="ml-11 space-y-2">
                                <label
                                    class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-rose-100/50"
                                >
                                    <input type="radio" v-model="answers.q20" value="A" class="h-5 w-5 text-rose-600 focus:ring-rose-500" />
                                    <span class="w-6 font-bold text-rose-700">A</span>
                                    <span
                                        data-segment-id="q20a"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('every six months.', 'q20a')"
                                    ></span>
                                </label>
                                <label
                                    class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-rose-100/50"
                                >
                                    <input type="radio" v-model="answers.q20" value="B" class="h-5 w-5 text-rose-600 focus:ring-rose-500" />
                                    <span class="w-6 font-bold text-rose-700">B</span>
                                    <span
                                        data-segment-id="q20b"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('within a few weeks of taking up running.', 'q20b')"
                                    ></span>
                                </label>
                                <label
                                    class="group/option flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all duration-200 hover:bg-rose-100/50"
                                >
                                    <input type="radio" v-model="answers.q20" value="C" class="h-5 w-5 text-rose-600 focus:ring-rose-500" />
                                    <span class="w-6 font-bold text-rose-700">C</span>
                                    <span
                                        data-segment-id="q20c"
                                        class="text-gray-700"
                                        v-html="getHighlightedSegment('after completing several practice runs.', 'q20c')"
                                    ></span>
                                </label>
                            </div>
                        </div>
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
    border-color: #f43f5e;
    background: #f43f5e;
    box-shadow: inset 0 0 0 3px white;
}
input[type='radio']:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.2);
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
