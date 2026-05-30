<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import draftService from '@/services/draftService';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
interface Props {
    phone?: string;
    examId?: string;
}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

// Listening Part 3 component
const answers = ref({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
    q27: '',
    q28: '',
    q29: '',
    q30: '',
});

// Draft auto-saver
const autoSaver = ref<any>(null);

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
    { text: 'Section 3', offset: 0 },
    { text: 'Questions 21-24', offset: 10 },
    { text: 'Complete the notes below.', offset: 28 },
    { text: 'Write NO MORE THAN THREE WORDS for each answer.', offset: 54 },
    { text: 'Box Telecom', offset: 105 },
    { text: 'Problem: been affected by', offset: 118 },
    { text: '– drop in ', offset: 147 },
    { text: '– growing ', offset: 161 },
    { text: '– delays due to a strike', offset: 173 },
    { text: 'Cause of problems:', offset: 200 },
    { text: '– high ', offset: 220 },
    { text: '– lack of good ', offset: 231 },
    { text: 'Questions 25-27', offset: 251 },
    { text: 'Choose the correct letter, A, B or C.', offset: 269 },
    { text: 'What does Karin think the company will do?', offset: 309 },
    { text: 'A look for private investors', offset: 355 },
    { text: 'B accept a takeover offer', offset: 384 },
    { text: 'C issue some new shares', offset: 411 },
    { text: 'How does the tutor suggest the company can recover?', offset: 437 },
    { text: 'A by appointing a new managing director', offset: 494 },
    { text: 'B by changing the way it is organised', offset: 535 },
    { text: 'C by closing some of its retail outlets', offset: 574 },
    { text: 'The tutor wants Jason and Karin to produce a report which', offset: 618 },
    { text: 'A offers solutions to Box Telecom’s problems.', offset: 681 },
    { text: 'B analyses the UK market.', offset: 726 },
    { text: 'C compares different companies.', offset: 752 },
    { text: 'Questions 28-30', offset: 800 },
    { text: 'Which opinion does each person express about Box Telecom?', offset: 817 },
    { text: 'Choose your answers from the box and write the letters A-F next to questions 28-30.', offset: 880 },
    { text: 'A its workers are motivated', offset: 970 },
    { text: 'B it has too little investment', offset: 1000 },
    { text: 'C it will overcome its problems', offset: 1030 },
    { text: 'D its marketing campaign needs improvement', offset: 1063 },
    { text: 'E it is old-fashioned', offset: 1110 },
    { text: 'F it has strong managers', offset: 1133 },
    { text: 'Karin', offset: 1160 },
    { text: 'Jason', offset: 1170 },
    { text: 'The tutor', offset: 1180 },
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

// Initialize answers and load drafts
const loadSavedAnswers = async () => {
    const userPhone = props.phone || draftService.getUserPhone();
    const examId = props.examId || '1234567';

    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part3');

    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part3) {
            Object.assign(answers.value, draftsResponse.data.part3);
            console.log('Loaded Listening Part 3 drafts');
        }
    } catch (error) {
        console.error('Failed to load Listening Part 3 drafts:', error);
    }
};

// Save answers to drafts
const saveAnswers = () => {
    if (autoSaver.value) {
        autoSaver.value.saveBatch(answers.value);
    }
};

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
};

watch(answers, saveAnswers, { deep: true });

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    let targetId = `question-${questionNumber}`;
    if (questionNumber >= 21 && questionNumber <= 24) {
        targetId = 'questions-21-24';
    } else if (questionNumber >= 25 && questionNumber <= 27) {
        targetId = 'questions-25-27';
    }

    const element = document.getElementById(targetId);
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
    if (event.key === 'Escape' && showContextMenu.value) {
        showContextMenu.value = false;
    }
};

onMounted(async () => {
    await loadSavedAnswers();
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    if (autoSaver.value) autoSaver.value.stop();
});

defineExpose({ getAnswers, scrollToQuestion, scrollToHighlight, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="mx-auto rounded px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
        <div class="flex min-h-screen w-full flex-col rounded-2xl bg-white shadow-2xl">
            <div class="sticky top-0 z-10 flex-shrink-0 border-b border-gray-200 bg-white p-3 sm:p-4 md:p-5 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-600 sm:h-9 sm:w-9 md:h-10 md:w-10"
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
                            <span :data-segment-text="'Section 3'" v-html="getHighlightedSegment('Section 3')"></span>:
                            <span :data-segment-text="'Questions 21-27'" v-html="getHighlightedSegment('Questions 21-27')"></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto bg-green-50 p-4 sm:p-6 md:p-8">
                <div class="mx-auto space-y-6">
                    <!-- Questions 21-24 -->
                    <section id="questions-21-24" class="rounded-2xl border-2 border-green-200 bg-white p-6 shadow-lg">
                        <div class="mb-4">
                            <p
                                class="text-lg font-semibold text-green-800"
                                :data-segment-text="'Questions 21-24'"
                                v-html="getHighlightedSegment('Questions 21-24')"
                            ></p>
                            <p
                                class="mt-1 text-gray-700"
                                :data-segment-text="'Complete the notes below.'"
                                v-html="getHighlightedSegment('Complete the notes below.')"
                            ></p>
                            <p
                                class="text-sm font-bold text-green-700"
                                :data-segment-text="'Write NO MORE THAN THREE WORDS for each answer.'"
                                v-html="getHighlightedSegment('Write NO MORE THAN THREE WORDS for each answer.')"
                            ></p>
                        </div>
                        <div class="rounded-lg border border-green-200 bg-green-50 p-6 shadow-xl">
                            <h3
                                class="mb-4 text-xl font-bold text-green-800"
                                :data-segment-text="'Box Telecom'"
                                v-html="getHighlightedSegment('Box Telecom')"
                            ></h3>
                            <div class="space-y-4">
                                <div class="grid grid-cols-[max-content_1fr] items-start gap-x-4">
                                    <p
                                        class="font-semibold text-gray-700"
                                        :data-segment-text="'Problem: been affected by'"
                                        v-html="getHighlightedSegment('Problem: been affected by')"
                                    ></p>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span :data-segment-text="'– drop in '" v-html="getHighlightedSegment('– drop in ')"></span>
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >21</span
                                            >
                                            <input
                                                v-model="answers.q21"
                                                type="text"
                                                class="w-48 rounded-md border-2 border-green-300 bg-white px-3 py-1 focus:border-green-500 focus:outline-none"
                                            />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span :data-segment-text="'– growing '" v-html="getHighlightedSegment('– growing ')"></span>
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >22</span
                                            >
                                            <input
                                                v-model="answers.q22"
                                                type="text"
                                                class="w-48 rounded-md border-2 border-green-300 bg-white px-3 py-1 focus:border-green-500 focus:outline-none"
                                            />
                                        </div>
                                        <p
                                            :data-segment-text="'– delays due to a strike'"
                                            v-html="getHighlightedSegment('– delays due to a strike')"
                                        ></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-[max-content_1fr] items-start gap-x-4 pt-2">
                                    <p
                                        class="font-semibold text-gray-700"
                                        :data-segment-text="'Cause of problems:'"
                                        v-html="getHighlightedSegment('Cause of problems:')"
                                    ></p>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span :data-segment-text="'– high '" v-html="getHighlightedSegment('– high ')"></span>
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >23</span
                                            >
                                            <input
                                                v-model="answers.q23"
                                                type="text"
                                                class="w-48 rounded-md border-2 border-green-300 bg-white px-3 py-1 focus:border-green-500 focus:outline-none"
                                            />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span :data-segment-text="'– lack of good '" v-html="getHighlightedSegment('– lack of good ')"></span>
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >24</span
                                            >
                                            <input
                                                v-model="answers.q24"
                                                type="text"
                                                class="w-48 rounded-md border-2 border-green-300 bg-white px-3 py-1 focus:border-green-500 focus:outline-none"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Questions 25-27 -->
                    <section id="questions-25-27" class="rounded-2xl border-2 border-emerald-200 bg-green-50 p-6 shadow-lg">
                        <div class="mb-4">
                            <p
                                class="text-lg font-semibold text-emerald-800"
                                :data-segment-text="'Questions 25-27'"
                                v-html="getHighlightedSegment('Questions 25-27')"
                            ></p>
                            <p
                                class="mt-1 text-gray-700"
                                :data-segment-text="'Choose the correct letter, A, B or C.'"
                                v-html="getHighlightedSegment('Choose the correct letter, A, B or C.')"
                            ></p>
                        </div>
                        <div class="space-y-6">
                            <div v-for="i in 3" :key="i" class="rounded-lg border-l-4 border-emerald-500 bg-white p-4 shadow-xl">
                                <div class="flex items-start gap-3">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >{{ 24 + i }}</span
                                    >
                                    <p
                                        class="font-semibold text-gray-800"
                                        :data-segment-text="
                                            [
                                                'What does Karin think the company will do?',
                                                'How does the tutor suggest the company can recover?',
                                                'The tutor wants Jason and Karin to produce a report which',
                                            ][i - 1]
                                        "
                                        v-html="
                                            getHighlightedSegment(
                                                [
                                                    'What does Karin think the company will do?',
                                                    'How does the tutor suggest the company can recover?',
                                                    'The tutor wants Jason and Karin to produce a report which',
                                                ][i - 1],
                                            )
                                        "
                                    ></p>
                                </div>
                                <div class="mt-3 space-y-2 pl-11">
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="radio"
                                            :name="`q${24 + i}`"
                                            :value="'A'"
                                            v-model="answers[`q${24 + i}`]"
                                            class="h-5 w-5 text-emerald-600 focus:ring-emerald-500"
                                        />
                                        <span
                                            :data-segment-text="
                                                [
                                                    'A look for private investors',
                                                    'A by appointing a new managing director',
                                                    'A offers solutions to Box Telecom’s problems.',
                                                ][i - 1]
                                            "
                                            v-html="
                                                getHighlightedSegment(
                                                    [
                                                        'A look for private investors',
                                                        'A by appointing a new managing director',
                                                        'A offers solutions to Box Telecom’s problems.',
                                                    ][i - 1],
                                                )
                                            "
                                        ></span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="radio"
                                            :name="`q${24 + i}`"
                                            :value="'B'"
                                            v-model="answers[`q${24 + i}`]"
                                            class="h-5 w-5 text-emerald-600 focus:ring-emerald-500"
                                        />
                                        <span
                                            :data-segment-text="
                                                ['B accept a takeover offer', 'B by changing the way it is organised', 'B analyses the UK market.'][
                                                    i - 1
                                                ]
                                            "
                                            v-html="
                                                getHighlightedSegment(
                                                    [
                                                        'B accept a takeover offer',
                                                        'B by changing the way it is organised',
                                                        'B analyses the UK market.',
                                                    ][i - 1],
                                                )
                                            "
                                        ></span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="radio"
                                            :name="`q${24 + i}`"
                                            :value="'C'"
                                            v-model="answers[`q${24 + i}`]"
                                            class="h-5 w-5 text-emerald-600 focus:ring-emerald-500"
                                        />
                                        <span
                                            :data-segment-text="
                                                [
                                                    'C issue some new shares',
                                                    'C by closing some of its retail outlets',
                                                    'C compares different companies.',
                                                ][i - 1]
                                            "
                                            v-html="
                                                getHighlightedSegment(
                                                    [
                                                        'C issue some new shares',
                                                        'C by closing some of its retail outlets',
                                                        'C compares different companies.',
                                                    ][i - 1],
                                                )
                                            "
                                        ></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Questions 28-30 -->
                    <section id="questions-28-30" class="mx-auto mb-20 w-full rounded-2xl border-2 border-green-200 bg-white p-6 shadow-lg">
                        <div class="mb-4">
                            <p
                                class="text-lg font-semibold text-green-800"
                                :data-segment-text="'Questions 28-30'"
                                v-html="getHighlightedSegment('Questions 28-30')"
                            ></p>
                            <p
                                class="mt-1 text-gray-700"
                                :data-segment-text="'Which opinion does each person express about Box Telecom?'"
                                v-html="getHighlightedSegment('Which opinion does each person express about Box Telecom?')"
                            ></p>
                            <p
                                class="text-sm font-bold text-green-700"
                                :data-segment-text="'Choose your answers from the box and write the letters A-F next to questions 28-30.'"
                                v-html="getHighlightedSegment('Choose your answers from the box and write the letters A-F next to questions 28-30.')"
                            ></p>
                        </div>

                        <div class="mb-6 rounded-lg border border-green-200 bg-green-50/30 p-4">
                            <ul class="space-y-1 text-gray-700">
                                <li
                                    :data-segment-text="'A its workers are motivated'"
                                    v-html="getHighlightedSegment('A its workers are motivated')"
                                ></li>
                                <li
                                    :data-segment-text="'B it has too little investment'"
                                    v-html="getHighlightedSegment('B it has too little investment')"
                                ></li>
                                <li
                                    :data-segment-text="'C it will overcome its problems'"
                                    v-html="getHighlightedSegment('C it will overcome its problems')"
                                ></li>
                                <li
                                    :data-segment-text="'D its marketing campaign needs improvement'"
                                    v-html="getHighlightedSegment('D its marketing campaign needs improvement')"
                                ></li>
                                <li :data-segment-text="'E it is old-fashioned'" v-html="getHighlightedSegment('E it is old-fashioned')"></li>
                                <li :data-segment-text="'F it has strong managers'" v-html="getHighlightedSegment('F it has strong managers')"></li>
                            </ul>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-lg"
                                    style="
                                        box-shadow:
                                            0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                    "
                                    >28</span
                                >
                                <label
                                    class="font-semibold text-gray-800"
                                    :data-segment-text="'Karin'"
                                    v-html="getHighlightedSegment('Karin')"
                                ></label>
                                <select
                                    v-model="answers.q28"
                                    class="ml-auto w-24 rounded-md border-2 border-green-300 bg-white px-3 py-1 text-sm focus:border-green-500 focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option v-for="opt in ['A', 'B', 'C', 'D', 'E', 'F']" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-4">
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-lg"
                                    style="
                                        box-shadow:
                                            0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                    "
                                    >29</span
                                >
                                <label
                                    class="font-semibold text-gray-800"
                                    :data-segment-text="'Jason'"
                                    v-html="getHighlightedSegment('Jason')"
                                ></label>
                                <select
                                    v-model="answers.q29"
                                    class="ml-auto w-24 rounded-md border-2 border-green-300 bg-white px-3 py-1 text-sm focus:border-green-500 focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option v-for="opt in ['A', 'B', 'C', 'D', 'E', 'F']" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-4">
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-lg"
                                    style="
                                        box-shadow:
                                            0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                    "
                                    >30</span
                                >
                                <label
                                    class="font-semibold text-gray-800"
                                    :data-segment-text="'The tutor'"
                                    v-html="getHighlightedSegment('The tutor')"
                                ></label>
                                <select
                                    v-model="answers.q30"
                                    class="ml-auto w-24 rounded-md border-2 border-green-300 bg-white px-3 py-1 text-sm focus:border-green-500 focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option v-for="opt in ['A', 'B', 'C', 'D', 'E', 'F']" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="pointer-events-auto fixed z-[9999] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
                    :style="{
                        left: `${contextMenuPosition.x}px`,
                        top: `${contextMenuPosition.y}px`,
                        transform: 'translateX(-50%)',
                    }"
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
                        <!-- Note Button -->
                        <button
                            @click="openNoteInput"
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-green-50 transition-all hover:scale-110 hover:bg-green-100"
                            title="Add Note"
                        >
                            <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <!-- Note Input Modal -->
            <div
                v-if="showNoteInput"
                class="fixed z-[9999] w-80 rounded-lg border-2 border-green-300 bg-white p-4 shadow-2xl"
                :style="{
                    left: `${noteInputPosition.x}px`,
                    top: `${noteInputPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input
                        v-model="noteInputText"
                        type="text"
                        placeholder="Write your note here..."
                        class="note-input-field w-full rounded-lg border-2 border-green-200 px-3 py-2 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
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
                        class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-green-700"
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
.highlight-green {
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
