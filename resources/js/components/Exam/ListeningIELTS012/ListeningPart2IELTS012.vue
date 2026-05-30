<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Listening Part 2 component
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
    { text: 'PART 2', offset: 0 },
    { text: 'Questions 11 – 14', offset: 7 },
    { text: 'Choose the correct letter, A, B or C.', offset: 25 },
    { text: '11 Stevenson’s was founded in', offset: 62 },
    { text: 'A. 1923.', offset: 93 },
    { text: 'B. 1924.', offset: 101 },
    { text: 'C. 1926.', offset: 109 },
    { text: '12 Originally, Stevenson’s manufactured goods for', offset: 117 },
    { text: 'A. the healthcare industry.', offset: 167 },
    { text: 'B. the automotive industry.', offset: 194 },
    { text: 'C. the machine tools industry.', offset: 222 },
    { text: '13 What does the speaker say about the company premises?', offset: 255 },
    { text: 'A. The company has recently moved.', offset: 310 },
    { text: 'B. The company has no plans to move.', offset: 343 },
    { text: 'C. The company is going to move shortly.', offset: 379 },
    { text: '14 The programme for the work experience group includes', offset: 419 },
    { text: 'A. a time to do research.', offset: 474 },
    { text: 'B. meetings with a teacher.', offset: 499 },
    { text: 'C. talks by staff.', offset: 527 },
    // Restored 15-20
    { text: 'Questions 15-20', offset: 543 },
    { text: 'Label the diagram below.', offset: 561 },
    { text: 'Choose the correct letter from the box and write the correct letter A-J', offset: 588 },
    { text: '15. coffee room', offset: 658 },
    { text: '16. warehouse', offset: 675 },
    { text: '17. staff canteen', offset: 689 },
    { text: '18. meeting room', offset: 708 },
    { text: '19. human resources', offset: 724 },
    { text: '20. boardroom', offset: 743 },
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
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-pink-500 to-fuchsia-600 sm:h-9 sm:w-9 md:h-10 md:w-10"
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
                            <span :data-segment-text="'PART 2'" v-html="getHighlightedSegment('PART 2')"></span>:
                            <span :data-segment-text="'Questions 11 – 14'" v-html="getHighlightedSegment('Questions 11 – 14')"></span>
                        </p>
                        <p class="text-xs text-gray-600">
                            <span
                                :data-segment-text="'Choose the correct letter, A, B or C.'"
                                v-html="getHighlightedSegment('Choose the correct letter, A, B or C.')"
                            ></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8">
                <div class="space-y-8">
                    <!-- Question 11 -->
                    <div id="question-11" class="rounded-2xl border-2 border-pink-200 bg-pink-50/50 p-6 shadow-lg">
                        <p
                            class="mb-4 text-lg font-semibold text-gray-800"
                            :data-segment-text="'11 Stevenson’s was founded in'"
                            v-html="getHighlightedSegment('11 Stevenson’s was founded in')"
                        ></p>
                        <div class="space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q11" value="A" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span class="text-gray-700" :data-segment-text="'A. 1923.'" v-html="getHighlightedSegment('A. 1923.')"></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q11" value="B" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span class="text-gray-700" :data-segment-text="'B. 1924.'" v-html="getHighlightedSegment('B. 1924.')"></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q11" value="C" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span class="text-gray-700" :data-segment-text="'C. 1926.'" v-html="getHighlightedSegment('C. 1926.')"></span>
                            </label>
                        </div>
                    </div>
                    <!-- Question 12 -->
                    <div id="question-12" class="rounded-2xl border-2 border-pink-200 bg-pink-50/50 p-6 shadow-lg">
                        <p
                            class="mb-4 text-lg font-semibold text-gray-800"
                            :data-segment-text="'12 Originally, Stevenson’s manufactured goods for'"
                            v-html="getHighlightedSegment('12 Originally, Stevenson’s manufactured goods for')"
                        ></p>
                        <div class="space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q12" value="A" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span
                                    class="text-gray-700"
                                    :data-segment-text="'A. the healthcare industry.'"
                                    v-html="getHighlightedSegment('A. the healthcare industry.')"
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q12" value="B" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span
                                    class="text-gray-700"
                                    :data-segment-text="'B. the automotive industry.'"
                                    v-html="getHighlightedSegment('B. the automotive industry.')"
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q12" value="C" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span
                                    class="text-gray-700"
                                    :data-segment-text="'C. the machine tools industry.'"
                                    v-html="getHighlightedSegment('C. the machine tools industry.')"
                                ></span>
                            </label>
                        </div>
                    </div>
                    <!-- Question 13 -->
                    <div id="question-13" class="rounded-2xl border-2 border-pink-200 bg-pink-50/50 p-6 shadow-lg">
                        <p
                            class="mb-4 text-lg font-semibold text-gray-800"
                            :data-segment-text="'13 What does the speaker say about the company premises?'"
                            v-html="getHighlightedSegment('13 What does the speaker say about the company premises?')"
                        ></p>
                        <div class="space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q13" value="A" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span
                                    class="text-gray-700"
                                    :data-segment-text="'A. The company has recently moved.'"
                                    v-html="getHighlightedSegment('A. The company has recently moved.')"
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q13" value="B" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span
                                    class="text-gray-700"
                                    :data-segment-text="'B. The company has no plans to move.'"
                                    v-html="getHighlightedSegment('B. The company has no plans to move.')"
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q13" value="C" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span
                                    class="text-gray-700"
                                    :data-segment-text="'C. The company is going to move shortly.'"
                                    v-html="getHighlightedSegment('C. The company is going to move shortly.')"
                                ></span>
                            </label>
                        </div>
                    </div>
                    <!-- Question 14 -->
                    <div id="question-14" class="rounded-2xl border-2 border-pink-200 bg-pink-50/50 p-6 shadow-lg">
                        <p
                            class="mb-4 text-lg font-semibold text-gray-800"
                            :data-segment-text="'14 The programme for the work experience group includes'"
                            v-html="getHighlightedSegment('14 The programme for the work experience group includes')"
                        ></p>
                        <div class="space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q14" value="A" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span
                                    class="text-gray-700"
                                    :data-segment-text="'A a time to do research.'"
                                    v-html="getHighlightedSegment('A a time to do research.')"
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q14" value="B" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span
                                    class="text-gray-700"
                                    :data-segment-text="'B meetings with a teacher.'"
                                    v-html="getHighlightedSegment('B meetings with a teacher.')"
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 transition hover:bg-pink-50"
                            >
                                <input type="radio" v-model="answers.q14" value="C" class="form-radio h-5 w-5 text-pink-600 focus:ring-pink-500" />
                                <span
                                    class="text-gray-700"
                                    :data-segment-text="'C talks by staff.'"
                                    v-html="getHighlightedSegment('C talks by staff.')"
                                ></span>
                            </label>
                        </div>
                    </div>
                    <!-- Questions 15-20 -->
                    <div class="rounded-2xl border-2 border-pink-200 bg-pink-50/50 p-6 shadow-lg">
                        <div class="mb-6">
                            <h3 class="mb-2 bg-gradient-to-r from-pink-600 to-fuchsia-600 bg-clip-text text-xl font-bold text-transparent">
                                <span :data-segment-text="'Questions 15-20'" v-html="getHighlightedSegment('Questions 15-20')"></span>
                            </h3>
                            <p class="text-base font-medium text-gray-800">
                                <span
                                    :data-segment-text="'Label the diagram below.'"
                                    v-html="getHighlightedSegment('Label the diagram below.')"
                                ></span
                                ><br />
                                <span
                                    :data-segment-text="'Choose the correct letter from the box and write the correct letter A-J'"
                                    v-html="getHighlightedSegment('Choose the correct letter from the box and write the correct letter A-J')"
                                ></span>
                            </p>
                        </div>
                        <div class="mt-4 rounded-lg border border-pink-200 bg-white p-4 shadow-sm">
                            <div class="flex-center flex justify-center text-base sm:text-lg">
                                <img src="/images/listening/IELTS012.png" alt="Diagram for questions 15-20" />
                            </div>
                        </div>
                        <div class="mt-6">
                            <table class="min-w-full overflow-hidden rounded-lg border border-pink-300 bg-white text-sm shadow">
                                <thead class="bg-gradient-to-r from-pink-100 to-fuchsia-100">
                                    <tr>
                                        <th class="border border-pink-300 px-3 py-2 text-left font-semibold text-pink-800">Item</th>
                                        <th class="border border-pink-300 px-3 py-2 text-left font-semibold text-pink-800">Letter</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="question-15">
                                        <td class="border border-pink-300 px-3 py-2 font-medium text-gray-700">
                                            <span :data-segment-text="'15. coffee room'" v-html="getHighlightedSegment('15. coffee room')"></span>
                                        </td>
                                        <td class="border border-pink-300 px-3 py-2">
                                            <select
                                                v-model="answers.q15"
                                                class="w-full rounded-lg border border-pink-400 bg-white px-2 py-1 shadow focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                            >
                                                <option disabled value="">Select A–J</option>
                                                <option v-for="i in 'ABCDEFGHIJ'.split('')" :key="i" :value="i">{{ i }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr id="question-16">
                                        <td class="border border-pink-300 px-3 py-2 font-medium text-gray-700">
                                            <span :data-segment-text="'16. warehouse'" v-html="getHighlightedSegment('16. warehouse')"></span>
                                        </td>
                                        <td class="border border-pink-300 px-3 py-2">
                                            <select
                                                v-model="answers.q16"
                                                class="w-full rounded-lg border border-pink-400 bg-white px-2 py-1 shadow focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                            >
                                                <option disabled value="">Select A–J</option>
                                                <option v-for="i in 'ABCDEFGHIJ'.split('')" :key="i" :value="i">{{ i }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr id="question-17">
                                        <td class="border border-pink-300 px-3 py-2 font-medium text-gray-700">
                                            <span :data-segment-text="'17. staff canteen'" v-html="getHighlightedSegment('17. staff canteen')"></span>
                                        </td>
                                        <td class="border border-pink-300 px-3 py-2">
                                            <select
                                                v-model="answers.q17"
                                                class="w-full rounded-lg border border-pink-400 bg-white px-2 py-1 shadow focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                            >
                                                <option disabled value="">Select A–J</option>
                                                <option v-for="i in 'ABCDEFGHIJ'.split('')" :key="i" :value="i">{{ i }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr id="question-18">
                                        <td class="border border-pink-300 px-3 py-2 font-medium text-gray-700">
                                            <span :data-segment-text="'18. meeting room'" v-html="getHighlightedSegment('18. meeting room')"></span>
                                        </td>
                                        <td class="border border-pink-300 px-3 py-2">
                                            <select
                                                v-model="answers.q18"
                                                class="w-full rounded-lg border border-pink-400 bg-white px-2 py-1 shadow focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                            >
                                                <option disabled value="">Select A–J</option>
                                                <option v-for="i in 'ABCDEFGHIJ'.split('')" :key="i" :value="i">{{ i }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr id="question-19">
                                        <td class="border border-pink-300 px-3 py-2 font-medium text-gray-700">
                                            <span
                                                :data-segment-text="'19. human resources'"
                                                v-html="getHighlightedSegment('19. human resources')"
                                            ></span>
                                        </td>
                                        <td class="border border-pink-300 px-3 py-2">
                                            <select
                                                v-model="answers.q19"
                                                class="w-full rounded-lg border border-pink-400 bg-white px-2 py-1 shadow focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                            >
                                                <option disabled value="">Select A–J</option>
                                                <option v-for="i in 'ABCDEFGHIJ'.split('')" :key="i" :value="i">{{ i }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr id="question-20">
                                        <td class="border border-pink-300 px-3 py-2 font-medium text-gray-700">
                                            <span :data-segment-text="'20. boardroom'" v-html="getHighlightedSegment('20. boardroom')"></span>
                                        </td>
                                        <td class="border border-pink-300 px-3 py-2">
                                            <select
                                                v-model="answers.q20"
                                                class="w-full rounded-lg border border-pink-400 bg-white px-2 py-1 shadow focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                            >
                                                <option disabled value="">Select A–J</option>
                                                <option v-for="i in 'ABCDEFGHIJ'.split('')" :key="i" :value="i">{{ i }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-pink-50 transition-all hover:scale-110 hover:bg-pink-100"
                            title="Add Note"
                        >
                            <svg class="h-4 w-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="fixed z-[9999] w-80 rounded-lg border-2 border-pink-300 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        placeholder="Write your note here..."
                        class="note-input-field w-full rounded-lg border-2 border-pink-200 px-3 py-2 text-sm focus:border-pink-500 focus:ring-2 focus:ring-pink-500 focus:outline-none"
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
                        class="rounded-md bg-pink-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-pink-700"
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
        background-color: rgba(236, 72, 153, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(236, 72, 153, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(236, 72, 153, 0.1);
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
