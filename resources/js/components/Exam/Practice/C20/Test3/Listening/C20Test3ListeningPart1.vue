<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Props
interface Props {
    correctAnswers: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), { correctAnswers: () => ({}) });

// Answers State
const answers = ref({
    q1: '',
    q2: '',
    q3: '',
    q4: '',
    q5: '',
    q6: '',
    q7: '',
    q8: '',
    q9: '',
    q10: '',
});

// Answer Modal State
const showAnswerModal = ref(false);
const currentAnswer = ref('');
const currentQuestionNumber = ref<number | null>(null);

const showAnswer = (questionKey: string) => {
    currentQuestionNumber.value = parseInt(questionKey.replace('q', ''));
    currentAnswer.value = props.correctAnswers[questionKey];
    showAnswerModal.value = true;
};

const closeAnswerModal = () => {
    showAnswerModal.value = false;
    currentAnswer.value = '';
    currentQuestionNumber.value = null;
};

// Drafts

// Highlighting & Notes
const contentTextRef = ref<HTMLDivElement | null>(null);
const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

const textSegments = ref([
    { text: 'Questions 1–10', offset: 0 },
    { text: 'Complete the table below.', offset: 15 },
    { text: 'Write ONE WORD AND/OR A NUMBER for each answer.', offset: 42 },
    { text: 'Furniture Rental Companies', offset: 95 },
    { text: 'Name of company', offset: 121 },
    { text: 'Information about costs', offset: 136 },
    { text: 'Additional notes', offset: 161 },
    { text: 'Peak Rentals', offset: 177 },
    { text: 'Prices range from $105 to $', offset: 189 },
    { text: ' per room per month.', offset: 216 },
    { text: 'The furniture is very ', offset: 236 },
    { text: 'Delivers in 1-2 days', offset: 258 },
    { text: 'Special offer: free ', offset: 278 },
    { text: ' with every living room set', offset: 298 },
    { text: ' and Oliver', offset: 325 },
    { text: 'Mid-range prices', offset: 336 },
    { text: '12% monthly free for ', offset: 352 },
    { text: 'Also offers a cleaning service', offset: 373 },
    { text: 'Larch Furniture', offset: 402 },
    { text: 'Offers cheapest prices for renting furniture and ', offset: 417 },
    { text: ' items', offset: 465 },
    { text: 'Must have own ', offset: 471 },
    { text: 'Minimum contract length: six months', offset: 485 },
    { text: ' Rentals', offset: 520 },
    { text: 'See the ', offset: 528 },
    { text: ' for the most up-to-date prices', offset: 536 },
    { text: ' are allowed within 7 days of delivery', offset: 567 },
]);

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

const handleContentTextSelect = (event: MouseEvent) => {
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
            const segmentEl = (container as Element)?.closest('[data-segment-text]');
            if (!segmentEl) return null;
            const segmentText = segmentEl.getAttribute('data-segment-text');
            const segment = textSegments.value.find((s) => s.text === segmentText);
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

// Lifecycle
onMounted(async () => {
    document.addEventListener('mouseup', handleContentTextSelect);
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('mouseup', handleContentTextSelect);
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

// Expose
defineExpose({
    getAnswers: () => answers.value,
    scrollToQuestion: async (questionNumber: number) => {
        /* ... */
    },
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" class="container mx-auto mb-16 px-4 py-8">
        <div class="w-full rounded-2xl bg-white/70 p-6 shadow-2xl backdrop-blur-xl">
            <!-- Header -->
            <div class="mb-6 flex-shrink-0 border-b-2 border-dashed border-gray-300 pb-6">
                <h2 class="text-xl font-bold text-blue-800">
                    <span :data-segment-text="'Questions 1–10'" v-html="getHighlightedSegment('Questions 1–10')"></span>
                </h2>
                <p class="mt-1 text-gray-700">
                    <span :data-segment-text="'Complete the table below.'" v-html="getHighlightedSegment('Complete the table below.')"></span>
                </p>
                <p class="font-semibold text-blue-700 italic">
                    <span
                        :data-segment-text="'Write ONE WORD AND/OR A NUMBER for each answer.'"
                        v-html="getHighlightedSegment('Write ONE WORD AND/OR A NUMBER for each answer.')"
                    ></span>
                </p>
            </div>

            <!-- Content Table -->
            <div class="space-y-4">
                <h3 class="mb-6 text-center text-xl font-bold text-blue-800">
                    <span :data-segment-text="'Furniture Rental Companies'" v-html="getHighlightedSegment('Furniture Rental Companies')"></span>
                </h3>

                <div class="overflow-x-auto rounded-lg border border-blue-200/50 shadow-lg">
                    <table class="w-full text-left text-sm text-gray-700">
                        <thead class="bg-blue-100/50 text-base tracking-wider text-blue-800 uppercase">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-bold">
                                    <span :data-segment-text="'Name of company'" v-html="getHighlightedSegment('Name of company')"></span>
                                </th>
                                <th scope="col" class="px-6 py-4 font-bold">
                                    <span
                                        :data-segment-text="'Information about costs'"
                                        v-html="getHighlightedSegment('Information about costs')"
                                    ></span>
                                </th>
                                <th scope="col" class="px-6 py-4 font-bold">
                                    <span :data-segment-text="'Additional notes'" v-html="getHighlightedSegment('Additional notes')"></span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-blue-100/50 bg-white hover:bg-blue-50/50">
                                <td class="px-6 py-4 font-medium">
                                    <span :data-segment-text="'Peak Rentals'" v-html="getHighlightedSegment('Peak Rentals')"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :data-segment-text="'Prices range from 05 to '"
                                        v-html="getHighlightedSegment('Prices range from 05 to ')"
                                    ></span>
                                    <span class="inline-flex items-center gap-2">
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                            >1</span
                                        >
                                        <input
                                            v-model="answers.q1"
                                            type="text"
                                            class="w-24 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                        />
                                    </span>
                                    <span :data-segment-text="' per room per month.'" v-html="getHighlightedSegment(' per room per month.')"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="mb-2 flex items-center gap-2">
                                        <span
                                            :data-segment-text="'The furniture is very '"
                                            v-html="getHighlightedSegment('The furniture is very ')"
                                        ></span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                            >2</span
                                        >
                                        <input
                                            v-model="answers.q2"
                                            type="text"
                                            class="w-32 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                        />
                                    </div>
                                    <div class="mb-2">
                                        <span
                                            :data-segment-text="'Delivers in 1-2 days'"
                                            v-html="getHighlightedSegment('Delivers in 1-2 days')"
                                        ></span>
                                    </div>
                                    <div>
                                        <span
                                            :data-segment-text="'Special offer: free '"
                                            v-html="getHighlightedSegment('Special offer: free ')"
                                        ></span>
                                        <span class="inline-flex items-center gap-2">
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                                >3</span
                                            >
                                            <input
                                                v-model="answers.q3"
                                                type="text"
                                                class="w-32 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                            />
                                        </span>
                                        <span
                                            :data-segment-text="' with every living room set'"
                                            v-html="getHighlightedSegment(' with every living room set')"
                                        ></span>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b border-blue-100/50 bg-white hover:bg-blue-50/50">
                                <td class="px-6 py-4 font-medium">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                            >4</span
                                        >
                                        <input
                                            v-model="answers.q4"
                                            type="text"
                                            class="w-32 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                        />
                                        <span :data-segment-text="' and Oliver'" v-html="getHighlightedSegment(' and Oliver')"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="mb-2">
                                        <span :data-segment-text="'Mid-range prices'" v-html="getHighlightedSegment('Mid-range prices')"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            :data-segment-text="'12% monthly free for '"
                                            v-html="getHighlightedSegment('12% monthly free for ')"
                                        ></span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                            >5</span
                                        >
                                        <input
                                            v-model="answers.q5"
                                            type="text"
                                            class="w-32 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :data-segment-text="'Also offers a cleaning service'"
                                        v-html="getHighlightedSegment('Also offers a cleaning service')"
                                    ></span>
                                </td>
                            </tr>
                            <tr class="border-b border-blue-100/50 bg-white hover:bg-blue-50/50">
                                <td class="px-6 py-4 font-medium">
                                    <span :data-segment-text="'Larch Furniture'" v-html="getHighlightedSegment('Larch Furniture')"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            :data-segment-text="'Offers cheapest prices for renting furniture and '"
                                            v-html="getHighlightedSegment('Offers cheapest prices for renting furniture and ')"
                                        ></span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                            >6</span
                                        >
                                        <input
                                            v-model="answers.q6"
                                            type="text"
                                            class="w-24 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                        />
                                        <span :data-segment-text="' items'" v-html="getHighlightedSegment(' items')"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="mb-2 flex items-center gap-2">
                                        <span :data-segment-text="'Must have own '" v-html="getHighlightedSegment('Must have own ')"></span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                            >7</span
                                        >
                                        <input
                                            v-model="answers.q7"
                                            type="text"
                                            class="w-32 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                        />
                                    </div>
                                    <div>
                                        <span
                                            :data-segment-text="'Minimum contract length: six months'"
                                            v-html="getHighlightedSegment('Minimum contract length: six months')"
                                        ></span>
                                    </div>
                                </td>
                            </tr>
                            <tr class="bg-white hover:bg-blue-50/50">
                                <td class="px-6 py-4 font-medium">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                            >8</span
                                        >
                                        <input
                                            v-model="answers.q8"
                                            type="text"
                                            class="w-32 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                        />
                                        <span :data-segment-text="' Rentals'" v-html="getHighlightedSegment(' Rentals')"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span :data-segment-text="'See the '" v-html="getHighlightedSegment('See the ')"></span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                            >9</span
                                        >
                                        <input
                                            v-model="answers.q9"
                                            type="text"
                                            class="w-32 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                        />
                                        <span
                                            :data-segment-text="' for the most up-to-date prices'"
                                            v-html="getHighlightedSegment(' for the most up-to-date prices')"
                                        ></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-base font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.3)]"
                                            >10</span
                                        >
                                        <input
                                            v-model="answers.q10"
                                            type="text"
                                            class="w-24 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-base"
                                        />
                                        <span
                                            :data-segment-text="' are allowed within 7 days of delivery'"
                                            v-html="getHighlightedSegment(' are allowed within 7 days of delivery')"
                                        ></span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <Teleport to="body">
            <!-- Answer Reveal Modal -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showAnswerModal"
                    @click="closeAnswerModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
                >
                    <Transition
                        enter-active-class="transition ease-out duration-300"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="transition ease-in duration-200"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div @click.stop class="relative w-full max-w-xs rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100 p-6 shadow-2xl">
                            <div class="flex flex-col items-center">
                                <div
                                    class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-purple-500 text-white shadow-lg shadow-purple-500/30"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"
                                        />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800">Correct Answer</h3>
                                <p class="mb-6 text-sm text-gray-500">Question {{ currentQuestionNumber }}</p>

                                <div class="mb-6 w-full rounded-lg border-2 border-dashed border-gray-300 bg-white p-6 text-center">
                                    <p
                                        class="bg-gradient-to-r from-purple-600 to-blue-500 bg-clip-text text-4xl font-bold tracking-wider text-transparent"
                                    >
                                        {{ currentAnswer }}
                                    </p>
                                </div>

                                <button
                                    @click="closeAnswerModal"
                                    class="w-full rounded-lg bg-gray-800 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-black focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <!-- Context Menu for Highlighting -->
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

            <!-- Note Input Modal -->
            <div
                v-if="showNoteInput"
                class="fixed z-[9999] w-80 rounded-lg border-2 border-blue-300 bg-white p-4 shadow-2xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-base text-gray-600 italic">"{{ selectedText }}"</p>
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
                    <button
                        @click="cancelNote"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-base font-medium text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveNote"
                        class="rounded-md bg-blue-600 px-3 py-1.5 text-base font-medium text-white transition-colors hover:bg-blue-700"
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
    background-color: rgba(59, 130, 246, 0.15);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(59, 130, 246, 0.15);
        transform: scale(1);
    }
    50% {
        background-color: rgba(59, 130, 246, 0.3);
        transform: scale(1.05);
    }
    100% {
        background-color: rgba(59, 130, 246, 0.15);
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

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #3b82f6, #6366f1);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #2563eb, #4f46e5);
}
</style>
