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
    { text: 'Section 2', offset: 0 },
    { text: 'Questions 11-20', offset: 10 },
    { text: 'Complete the sentences below.', offset: 28 },
    { text: 'Write NO MORE THAN TWO WORDS for each answer.', offset: 59 },
    { text: 'The next meeting of the soccer club will be in the ', offset: 111 },
    { text: ' in King’s Park on 2 July.', offset: 161 },
    { text: 'The first event is a ', offset: 191 },
    { text: 'At the final dinner, players receive ', offset: 215 },
    { text: 'Questions 14-17', offset: 255 },
    { text: 'Complete the table below.', offset: 273 },
    { text: 'Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.', offset: 300 },
    { text: 'Competition', offset: 373 },
    { text: 'Number of teams', offset: 387 },
    { text: 'Games begin', offset: 405 },
    { text: 'Training Session (in King’s Park)', offset: 419 },
    { text: 'Junior', offset: 456 },
    { text: '8.30 am', offset: 480 },
    { text: 'Senior', offset: 495 },
    { text: '2.00 pm', offset: 518 },
    { text: 'Questions 18-20', offset: 530 },
    { text: 'Complete the table below.', offset: 548 },
    { text: 'Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.', offset: 575 },
    { text: 'Name of Office Bearer', offset: 648 },
    { text: 'Responsibility', offset: 672 },
    { text: 'Robert Young: President', offset: 689 },
    { text: 'to manage meetings', offset: 715 },
    { text: 'Gina Costello: Treasurer', offset: 736 },
    { text: 'David West: Secretary', offset: 763 },
    { text: 'Jason Dokic: Head Coach', offset: 787 },
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

    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part2');

    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part2) {
            Object.assign(answers.value, draftsResponse.data.part2);
            console.log('Loaded Listening Part 2 drafts');
        }
    } catch (error) {
        console.error('Failed to load Listening Part 2 drafts:', error);
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
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

            <div class="flex-1 overflow-y-auto bg-pink-50 p-4 sm:p-6 md:p-8">
                <div class="mx-auto space-y-8">
                    <!-- Questions 11-13 -->
                    <div id="questions-11-13" class="rounded-2xl border-2 border-purple-200 bg-white p-6 shadow-lg">
                        <div class="mb-4">
                            <p
                                class="text-lg font-semibold text-purple-800"
                                :data-segment-text="'Questions 11-20'"
                                v-html="getHighlightedSegment('Questions 11-20')"
                            ></p>
                            <p
                                class="text-gray-700"
                                :data-segment-text="'Complete the sentences below.'"
                                v-html="getHighlightedSegment('Complete the sentences below.')"
                            ></p>
                            <p
                                class="mt-1 text-sm font-bold text-purple-700"
                                :data-segment-text="'Write NO MORE THAN TWO WORDS for each answer.'"
                                v-html="getHighlightedSegment('Write NO MORE THAN TWO WORDS for each answer.')"
                            ></p>
                        </div>
                        <div class="space-y-4 text-base">
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-500 font-bold text-white shadow-lg"
                                    style="
                                        box-shadow:
                                            0 4px 6px -1px rgba(168, 85, 247, 0.5),
                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                    "
                                    >11</span
                                >
                                <span
                                    :data-segment-text="'The next meeting of the soccer club will be in the '"
                                    v-html="getHighlightedSegment('The next meeting of the soccer club will be in the ')"
                                ></span>
                                <input
                                    v-model="answers.q11"
                                    type="text"
                                    class="inline-block w-48 rounded-md border-2 border-purple-300 bg-purple-50 px-3 py-1 focus:border-purple-500 focus:bg-white focus:outline-none"
                                />
                                <span
                                    :data-segment-text="' in King’s Park on 2 July.'"
                                    v-html="getHighlightedSegment(' in King’s Park on 2 July.')"
                                ></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-500 font-bold text-white shadow-lg"
                                    style="
                                        box-shadow:
                                            0 4px 6px -1px rgba(168, 85, 247, 0.5),
                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                    "
                                    >12</span
                                >
                                <span :data-segment-text="'The first event is a '" v-html="getHighlightedSegment('The first event is a ')"></span>
                                <input
                                    v-model="answers.q12"
                                    type="text"
                                    class="inline-block w-48 rounded-md border-2 border-purple-300 bg-purple-50 px-3 py-1 focus:border-purple-500 focus:bg-white focus:outline-none"
                                />
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-500 font-bold text-white shadow-lg"
                                    style="
                                        box-shadow:
                                            0 4px 6px -1px rgba(168, 85, 247, 0.5),
                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                    "
                                    >13</span
                                >
                                <span
                                    :data-segment-text="'At the final dinner, players receive '"
                                    v-html="getHighlightedSegment('At the final dinner, players receive ')"
                                ></span>
                                <input
                                    v-model="answers.q13"
                                    type="text"
                                    class="inline-block w-48 rounded-md border-2 border-purple-300 bg-purple-50 px-3 py-1 focus:border-purple-500 focus:bg-white focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Questions 14-17 -->
                    <div id="questions-14-17" class="rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-lg">
                        <div class="mb-4">
                            <p
                                class="text-lg font-semibold text-pink-800"
                                :data-segment-text="'Questions 14-17'"
                                v-html="getHighlightedSegment('Questions 14-17')"
                            ></p>
                            <p
                                class="text-gray-700"
                                :data-segment-text="'Complete the table below.'"
                                v-html="getHighlightedSegment('Complete the table below.')"
                            ></p>
                            <p
                                class="mt-1 text-sm font-bold text-pink-700"
                                :data-segment-text="'Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.'"
                                v-html="getHighlightedSegment('Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.')"
                            ></p>
                        </div>
                        <table class="w-full border-collapse text-left">
                            <thead class="bg-pink-100">
                                <tr>
                                    <th
                                        class="border border-pink-200 p-3"
                                        :data-segment-text="'Competition'"
                                        v-html="getHighlightedSegment('Competition')"
                                    ></th>
                                    <th
                                        class="border border-pink-200 p-3"
                                        :data-segment-text="'Number of teams'"
                                        v-html="getHighlightedSegment('Number of teams')"
                                    ></th>
                                    <th
                                        class="border border-pink-200 p-3"
                                        :data-segment-text="'Games begin'"
                                        v-html="getHighlightedSegment('Games begin')"
                                    ></th>
                                    <th
                                        class="border border-pink-200 p-3"
                                        :data-segment-text="'Training Session (in King’s Park)'"
                                        v-html="getHighlightedSegment('Training Session (in King’s Park)')"
                                    ></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white">
                                    <td
                                        class="border border-pink-200 p-3"
                                        :data-segment-text="'Junior'"
                                        v-html="getHighlightedSegment('Junior')"
                                    ></td>
                                    <td class="border border-pink-200 p-3">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >14</span
                                            >
                                            <input
                                                v-model="answers.q14"
                                                type="text"
                                                class="w-full rounded-md border-2 border-pink-300 bg-pink-50 px-3 py-1 focus:border-pink-500 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                    </td>
                                    <td
                                        class="border border-pink-200 p-3"
                                        :data-segment-text="'8.30 am'"
                                        v-html="getHighlightedSegment('8.30 am')"
                                    ></td>
                                    <td class="border border-pink-200 p-3">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >15</span
                                            >
                                            <input
                                                v-model="answers.q15"
                                                type="text"
                                                class="w-full rounded-md border-2 border-pink-300 bg-pink-50 px-3 py-1 focus:border-pink-500 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-pink-50/30">
                                    <td
                                        class="border border-pink-200 p-3"
                                        :data-segment-text="'Senior'"
                                        v-html="getHighlightedSegment('Senior')"
                                    ></td>
                                    <td class="border border-pink-200 p-3">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >16</span
                                            >
                                            <input
                                                v-model="answers.q16"
                                                type="text"
                                                class="w-full rounded-md border-2 border-pink-300 bg-pink-50 px-3 py-1 focus:border-pink-500 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                    </td>
                                    <td
                                        class="border border-pink-200 p-3"
                                        :data-segment-text="'2.00 pm'"
                                        v-html="getHighlightedSegment('2.00 pm')"
                                    ></td>
                                    <td class="border border-pink-200 p-3">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >17</span
                                            >
                                            <input
                                                v-model="answers.q17"
                                                type="text"
                                                class="w-full rounded-md border-2 border-pink-300 bg-pink-50 px-3 py-1 focus:border-pink-500 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Questions 18-20 -->
                    <div id="questions-18-20" class="rounded-2xl border-2 border-purple-200 bg-white p-6 shadow-lg">
                        <div class="mb-4">
                            <p
                                class="text-lg font-semibold text-purple-800"
                                :data-segment-text="'Questions 18-20'"
                                v-html="getHighlightedSegment('Questions 18-20')"
                            ></p>
                            <p
                                class="text-gray-700"
                                :data-segment-text="'Complete the table below.'"
                                v-html="getHighlightedSegment('Complete the table below.')"
                            ></p>
                            <p
                                class="mt-1 text-sm font-bold text-purple-700"
                                :data-segment-text="'Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.'"
                                v-html="getHighlightedSegment('Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.')"
                            ></p>
                        </div>
                        <table class="w-full border-collapse text-left">
                            <thead class="bg-purple-100">
                                <tr>
                                    <th
                                        class="border border-purple-200 p-3"
                                        :data-segment-text="'Name of Office Bearer'"
                                        v-html="getHighlightedSegment('Name of Office Bearer')"
                                    ></th>
                                    <th
                                        class="border border-purple-200 p-3"
                                        :data-segment-text="'Responsibility'"
                                        v-html="getHighlightedSegment('Responsibility')"
                                    ></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white">
                                    <td
                                        class="border border-purple-200 p-3"
                                        :data-segment-text="'Robert Young: President'"
                                        v-html="getHighlightedSegment('Robert Young: President')"
                                    ></td>
                                    <td
                                        class="border border-purple-200 p-3"
                                        :data-segment-text="'to manage meetings'"
                                        v-html="getHighlightedSegment('to manage meetings')"
                                    ></td>
                                </tr>
                                <tr class="bg-purple-50/30">
                                    <td
                                        class="border border-purple-200 p-3"
                                        :data-segment-text="'Gina Costello: Treasurer'"
                                        v-html="getHighlightedSegment('Gina Costello: Treasurer')"
                                    ></td>
                                    <td class="border border-purple-200 p-3">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(168, 85, 247, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >18</span
                                            >
                                            <input
                                                v-model="answers.q18"
                                                type="text"
                                                class="w-full rounded-md border-2 border-purple-300 bg-purple-50 px-3 py-1 focus:border-purple-500 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-white">
                                    <td
                                        class="border border-purple-200 p-3"
                                        :data-segment-text="'David West: Secretary'"
                                        v-html="getHighlightedSegment('David West: Secretary')"
                                    ></td>
                                    <td class="border border-purple-200 p-3">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(168, 85, 247, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >19</span
                                            >
                                            <input
                                                v-model="answers.q19"
                                                type="text"
                                                class="w-full rounded-md border-2 border-purple-300 bg-purple-50 px-3 py-1 focus:border-purple-500 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-purple-50/30">
                                    <td
                                        class="border border-purple-200 p-3"
                                        :data-segment-text="'Jason Dokic: Head Coach'"
                                        v-html="getHighlightedSegment('Jason Dokic: Head Coach')"
                                    ></td>
                                    <td class="border border-purple-200 p-3">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-500 font-bold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(168, 85, 247, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >20</span
                                            >
                                            <input
                                                v-model="answers.q20"
                                                type="text"
                                                class="w-full rounded-md border-2 border-purple-300 bg-purple-50 px-3 py-1 focus:border-purple-500 focus:bg-white focus:outline-none"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
