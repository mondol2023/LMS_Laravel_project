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

// Single choice answers for questions 11-20
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
    { text: 'QUESTIONS 11-16', offset: 0 },
    { text: 'Choose the correct letter A, B or C.', offset: 17 },
    { text: 'PS Camping has been organising holidays for', offset: 55 },
    { text: 'A. 15 years', offset: 101 },
    { text: 'B. 20 years', offset: 111 },
    { text: 'C. 25 years', offset: 121 },
    { text: 'The company has most camping sites in', offset: 131 },
    { text: 'A. France', offset: 172 },
    { text: 'B. Italy', offset: 180 },
    { text: 'C. Switzerland', offset: 187 },
    { text: 'Which organised activity can children do every day of the week?', offset: 199 },
    { text: 'A. football', offset: 264 },
    { text: 'B. drama', offset: 274 },
    { text: 'C. model making', offset: 281 },
    { text: 'Some areas of the sites have a ‘no noise’ rule after', offset: 295 },
    { text: 'A. 9.30 p.m', offset: 349 },
    { text: 'B. 10.00 p.m', offset: 359 },
    { text: 'C. 10.30 p.m', offset: 370 },
    { text: 'The holiday insurance that is offered by PS Camping', offset: 381 },
    { text: 'A. can be charged on an annual basis', offset: 432 },
    { text: 'B. is included in the price of the holiday', offset: 468 },
    { text: 'C. must be taken out at the time of booking', offset: 507 },
    { text: 'Customers who recommend PS Camping to friends will receive', offset: 551 },
    { text: 'A. a free gift', offset: 612 },
    { text: 'B. an upgrade to a luxury tent', offset: 625 },
    { text: 'C. a discount', offset: 653 },
    { text: 'Questions 17-20', offset: 665 },
    { text: 'What does the speaker say about the following items?', offset: 681 },
    { text: 'Write the correct letter A, B or C next to questions 17-20', offset: 732 },
    { text: 'A. They are provided in all tents.', offset: 791 },
    { text: 'B. They are found in central areas of the campsite.', offset: 822 },
    { text: 'C. They are available on requests.', offset: 872 },
    { text: 'barbeques', offset: 904 },
    { text: 'Toys', offset: 913 },
    { text: 'cool boxes', offset: 917 },
    { text: 'mops and buckets', offset: 927 },
]);

const questions = ref([
    {
        num: 11,
        key: 'q11' as keyof typeof answers.value,
        text: 'PS Camping has been organising holidays for',
        options: [
            { value: 'A', text: 'A. 15 years' },
            { value: 'B', text: 'B. 20 years' },
            { value: 'C', text: 'C. 25 years' },
        ],
    },
    {
        num: 12,
        key: 'q12' as keyof typeof answers.value,
        text: 'The company has most camping sites in',
        options: [
            { value: 'A', text: 'A. France' },
            { value: 'B', text: 'B. Italy' },
            { value: 'C', text: 'C. Switzerland' },
        ],
    },
    {
        num: 13,
        key: 'q13' as keyof typeof answers.value,
        text: 'Which organised activity can children do every day of the week?',
        options: [
            { value: 'A', text: 'A. football' },
            { value: 'B', text: 'B. drama' },
            { value: 'C', text: 'C. model making' },
        ],
    },
    {
        num: 14,
        key: 'q14' as keyof typeof answers.value,
        text: 'Some areas of the sites have a ‘no noise’ rule after',
        options: [
            { value: 'A', text: 'A. 9.30 p.m' },
            { value: 'B', text: 'B. 10.00 p.m' },
            { value: 'C', text: 'C. 10.30 p.m' },
        ],
    },
    {
        num: 15,
        key: 'q15' as keyof typeof answers.value,
        text: 'The holiday insurance that is offered by PS Camping',
        options: [
            { value: 'A', text: 'A. can be charged on an annual basis' },
            { value: 'B', text: 'B. is included in the price of the holiday' },
            { value: 'C', text: 'C. must be taken out at the time of booking' },
        ],
    },
    {
        num: 16,
        key: 'q16' as keyof typeof answers.value,
        text: 'Customers who recommend PS Camping to friends will receive',
        options: [
            { value: 'A', text: 'A. a free gift' },
            { value: 'B', text: 'B. an upgrade to a luxury tent' },
            { value: 'C', text: 'C. a discount' },
        ],
    },
]);

const matchingQuestions = ref([
    { num: 17, key: 'q17' as keyof typeof answers.value, text: 'barbeques' },
    { num: 18, key: 'q18' as keyof typeof answers.value, text: 'Toys' },
    { num: 19, key: 'q19' as keyof typeof answers.value, text: 'cool boxes' },
    { num: 20, key: 'q20' as keyof typeof answers.value, text: 'mops and buckets' },
]);

const matchingOptions = ref([
    { text: 'A. They are provided in all tents.' },
    { text: 'B. They are found in central areas of the campsite.' },
    { text: 'C. They are available on requests.' },
]);

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentText: string) => {
    // Find this segment's offset
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;

    // Check if any highlights overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    if (overlappingHighlights.length === 0) {
        return segmentText;
    }

    // Apply highlights to this segment
    // Sort by start offset descending
    const sorted = [...overlappingHighlights].sort((a, b) => b.start_offset - a.start_offset);

    let result = segmentText;
    for (const highlight of sorted) {
        // Calculate the position within this segment
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

    // Initialize auto-saver
    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part2');

    // Load saved drafts
    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part2) {
            // Load answers
            Object.keys(answers.value).forEach((key) => {
                if (draftsResponse.data.part2[key]) {
                    answers.value[key as keyof typeof answers.value] = draftsResponse.data.part2[key];
                }
            });
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
    return { ...answers.value };
};

// Watch for changes and auto-save
watch(
    answers,
    () => {
        saveAnswers();
    },
    { deep: true },
);

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const targetId = `question-${questionNumber}`;

    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        // Add highlight effect
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
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        // Optional: Add a temporary visual effect to the scrolled highlight
        element.classList.add('highlight-flash');
        setTimeout(() => {
            element.classList.remove('highlight-flash');
        }, 2000);
    }
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            showContextMenu.value = false;
            return;
        }

        const selected = selection.toString().trim();
        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) {
                container = container.parentNode as Node;
            }
            const segmentEl = (container as Element).closest('[data-segment-text]');

            if (!segmentEl) {
                // It's possible the selection is on non-segmented text, like inside a label for a checkbox.
                // In this case, we don't want to show the context menu.
                return null;
            }

            const segmentText = segmentEl.getAttribute('data-segment-text');
            const segment = textSegments.value.find((s) => s.text === segmentText);
            if (!segment) {
                console.error('Segment not found for text:', segmentText);
                return null;
            }

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
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.bottom + 5;

            const viewportWidth = window.innerWidth;
            const menuWidth = 250;

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2), viewportWidth - menuWidth / 2),
                y: menuY,
            };
            showContextMenu.value = true;

            selectedText.value = selection.toString();
            selectionRange.value = { start: startAbsOffset, end: endAbsOffset };

            console.log('Selection:', { selected: selectedText.value, startOffset: startAbsOffset, endOffset: endAbsOffset });
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

    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };

    showNoteInput.value = true;
    showContextMenu.value = false;

    setTimeout(() => {
        const input = document.querySelector<HTMLInputElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const noteId = Date.now();
    notes.value.push({
        id: noteId,
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
    if (showContextMenu.value) {
        showContextMenu.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) {
        showContextMenu.value = false;
    }
};

// Load saved answers when component mounts
onMounted(async () => {
    await loadSavedAnswers();
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    if (autoSaver.value) {
        autoSaver.value.stop();
    }
});

defineExpose({
    getAnswers,
    answers: answers.value,
    scrollToQuestion,
    scrollToHighlight,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="container mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col rounded-lg bg-white shadow-lg">
            <!-- Questions Header (Fixed) -->
            <div class="flex-shrink-0 border-b border-gray-200 bg-white p-2 sm:p-3 md:p-4 lg:p-6">
                <div class="flex flex-col gap-2 sm:gap-3">
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-r from-purple-500 to-pink-600 sm:h-7 sm:w-7 md:h-8 md:w-8"
                        >
                            <svg class="h-3 w-3 text-white sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                ></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-gray-700 uppercase sm:text-sm">
                                <span :data-segment-text="'QUESTIONS 11-20'" v-html="getHighlightedSegment('QUESTIONS 11-20')"></span>
                            </p>
                            <p class="text-xs text-gray-600">
                                <span
                                    :data-segment-text="'Choose the correct answer for each question.'"
                                    v-html="getHighlightedSegment('Choose the correct answer for each question.')"
                                ></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <!-- Questions 11-16: Single Choice MCQ -->
                    <div class="space-y-3 sm:space-y-4 md:space-y-6">
                        <div class="rounded-2xl border border-pink-200 bg-gradient-to-br from-pink-50 to-rose-50 p-4 shadow-lg sm:p-6 md:p-8">
                            <div class="mb-6 sm:mb-8">
                                <h3 class="bg-gradient-to-r from-pink-600 to-rose-600 bg-clip-text text-xl font-bold text-transparent">
                                    <span :data-segment-text="'QUESTIONS 11-16'" v-html="getHighlightedSegment('QUESTIONS 11-16')"></span>
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    <span
                                        :data-segment-text="'Choose the correct letter A, B or C.'"
                                        v-html="getHighlightedSegment('Choose the correct letter A, B or C.')"
                                    ></span>
                                </p>
                            </div>

                            <div class="space-y-6">
                                <div
                                    v-for="question in questions"
                                    :key="question.num"
                                    :id="`question-${question.num}`"
                                    class="rounded-xl border border-pink-200 bg-white p-4 shadow-md transition-all duration-300 hover:border-pink-300 hover:shadow-lg sm:p-5"
                                >
                                    <div class="flex items-start gap-3 sm:gap-4">
                                        <div
                                            class="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-rose-500 text-xs font-bold text-white sm:h-8 sm:w-8 sm:text-sm"
                                        >
                                            {{ question.num }}
                                        </div>
                                        <div class="flex-grow">
                                            <p class="mb-3 text-base font-semibold text-gray-800">
                                                <span :data-segment-text="question.text" v-html="getHighlightedSegment(question.text)"></span>
                                            </p>
                                            <div class="space-y-2">
                                                <label
                                                    v-for="option in question.options"
                                                    :key="option.value"
                                                    class="flex cursor-pointer items-center space-x-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-pink-50 has-[:checked]:border-pink-400 has-[:checked]:bg-pink-100"
                                                >
                                                    <input
                                                        type="radio"
                                                        :name="question.key"
                                                        :value="option.value"
                                                        v-model="answers[question.key]"
                                                        class="h-4 w-4 border-gray-300 text-pink-600 focus:ring-pink-500"
                                                    />
                                                    <span class="text-gray-700">
                                                        <span :data-segment-text="option.text" v-html="getHighlightedSegment(option.text)"></span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Questions 17-20: Matching -->
                        <div class="mt-6 rounded-2xl border border-pink-200 bg-gradient-to-br from-pink-50 to-rose-50 p-4 shadow-lg sm:p-6 md:p-8">
                            <div class="mb-6 sm:mb-8">
                                <h3 class="bg-gradient-to-r from-pink-600 to-rose-600 bg-clip-text text-xl font-bold text-transparent">
                                    <span :data-segment-text="'Questions 17-20'" v-html="getHighlightedSegment('Questions 17-20')"></span>
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    <span
                                        :data-segment-text="'What does the speaker say about the following items?'"
                                        v-html="getHighlightedSegment('What does the speaker say about the following items?')"
                                    ></span>
                                </p>
                                <p class="mt-1 text-sm text-gray-600">
                                    <span
                                        :data-segment-text="'Write the correct letter A, B or C next to questions 17-20'"
                                        v-html="getHighlightedSegment('Write the correct letter A, B or C next to questions 17-20')"
                                    ></span>
                                </p>
                            </div>

                            <div class="mb-6 rounded-xl border border-rose-200 bg-white p-4 shadow-sm">
                                <ul class="space-y-2 text-gray-700">
                                    <li v-for="option in matchingOptions" :key="option.text">
                                        <span :data-segment-text="option.text" v-html="getHighlightedSegment(option.text)"></span>
                                    </li>
                                </ul>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="question in matchingQuestions"
                                    :key="question.num"
                                    :id="`question-${question.num}`"
                                    class="flex items-center justify-between rounded-xl border border-pink-200 bg-white p-4 shadow-md"
                                >
                                    <p class="flex items-center gap-4 font-semibold text-gray-800">
                                        <span
                                            class="mr-2 inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md ring-2 ring-white"
                                            >{{ question.num }}</span
                                        >
                                        <span :data-segment-text="question.text" v-html="getHighlightedSegment(question.text)"></span>
                                    </p>
                                    <select
                                        v-model="answers[question.key]"
                                        class="rounded-lg border-2 border-rose-300 bg-rose-50 px-3 py-2 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 focus:outline-none"
                                    >
                                        <option value="">Select</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Context Menu for Highlighting -->
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
                        class="note-input-field w-full rounded-lg border-2 border-blue-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
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
                        class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700"
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
        background-color: rgba(168, 85, 247, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(168, 85, 247, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(168, 85, 247, 0.1);
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
    background: linear-gradient(to bottom, #a855f7, #ec4899);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #9333ea, #db2777);
}
</style>
