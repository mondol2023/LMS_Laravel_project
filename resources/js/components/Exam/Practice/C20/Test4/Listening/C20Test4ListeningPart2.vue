<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

// Props for draft system
interface Props {}

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

// For questions 11-12
const multipleAnswers11_12 = reactive({
    q11_12: [],
});

watch(multipleAnswers11_12, (newVal) => {
    answers.value.q11 = newVal.q11_12[0] || '';
    answers.value.q12 = newVal.q11_12[1] || '';
});

// For questions 13-14
const multipleAnswers13_14 = reactive({
    q13_14: [],
});

watch(multipleAnswers13_14, (newVal) => {
    answers.value.q13 = newVal.q13_14[0] || '';
    answers.value.q14 = newVal.q13_14[1] || '';
});

// Draft auto-saver

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
    { text: 'QUESTIONS 11-20', offset: 0 },
    { text: 'Choose the correct answer for each question.', offset: 15 },
    { text: 'Questions 11 and 12', offset: 58 },
    { text: 'Choose TWO letters, A-E.', offset: 77 },
    { text: 'Which TWO things does the speaker say about visiting the football stadium with children?', offset: 102 },
    { text: 'A Children can get their photo taken with a football player.', offset: 187 },
    { text: 'B There is a competition for children today.', offset: 249 },
    { text: 'C Parents must stay with their children at all times.', offset: 293 },
    { text: 'D Children will need sunhats and drinks.', offset: 347 },
    { text: 'E The café has a special offer on meals for children.', offset: 388 },
    { text: 'Questions 13 and 14', offset: 444 },
    { text: 'Choose TWO letters, A-E.', offset: 463 },
    { text: 'Which TWO features of the stadium tour are new this year?', offset: 488 },
    { text: 'A VIP tour', offset: 547 },
    { text: 'B 360 cinema experience', offset: 557 },
    { text: 'C audio guide', offset: 581 },
    { text: 'D dressing room tour', offset: 594 },
    { text: 'E tours in other languages', offset: 614 },
    { text: 'Questions 15-20', offset: 640 },
    { text: 'Which event in the history of football in the UK took place in each of the following years?', offset: 656 },
    { text: 'Choose SIX answers from the box and write the correct letter, A-H, next to Questions 15-20.', offset: 749 },
    { text: 'Events in the history of football', offset: 841 },
    { text: 'A the introduction of pay for the players', offset: 874 },
    { text: 'B a change to the design of the goal', offset: 915 },
    { text: 'C the first use of lights for matches', offset: 951 },
    { text: 'D the introduction of goalkeepers', offset: 988 },
    { text: 'E the first international match', offset: 1021 },
    { text: 'F two changes to the rules of the game', offset: 1051 },
    { text: 'G the introduction of fee for spectators', offset: 1090 },
    { text: 'H an agreement on the length of a game', offset: 1130 },
    { text: '1870', offset: 1169 },
    { text: '1874', offset: 1173 },
    { text: '1875', offset: 1177 },
    { text: '1877', offset: 1181 },
    { text: '1878', offset: 1185 },
    { text: '1880', offset: 1189 },
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

// Save answers to drafts

// Expose methods for parent component
const getAnswers = () => {
    return { ...answers.value };
};

// Watch for changes and auto-save
watch(answers, () => {}, { deep: true });

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
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
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
                <div class="space-y-8 p-4 sm:p-6 lg:p-8">
                    <!-- Questions 11-12 -->
                    <div id="question-11-12" class="rounded-2xl border border-pink-200 bg-white p-6 shadow-lg">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-pink-700">
                                <span :data-segment-text="'Questions 11 and 12'" v-html="getHighlightedSegment('Questions 11 and 12')"></span>
                            </h3>
                            <p class="text-sm text-gray-600">
                                <span
                                    :data-segment-text="'Choose TWO letters, A-E.'"
                                    v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                ></span>
                            </p>
                        </div>
                        <p class="mb-4 font-medium text-gray-800">
                            <span
                                :data-segment-text="'Which TWO things does the speaker say about visiting the football stadium with children?'"
                                v-html="
                                    getHighlightedSegment('Which TWO things does the speaker say about visiting the football stadium with children?')
                                "
                            ></span>
                        </p>
                        <div class="space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow-sm hover:bg-pink-50"
                            >
                                <input
                                    type="checkbox"
                                    value="A"
                                    v-model="multipleAnswers11_12.q11_12"
                                    :disabled="multipleAnswers11_12.q11_12.length >= 2 && !multipleAnswers11_12.q11_12.includes('A')"
                                    class="h-5 w-5 rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                                />
                                <span class="font-medium"
                                    ><span
                                        :data-segment-text="'A Children can get their photo taken with a football player.'"
                                        v-html="getHighlightedSegment('A Children can get their photo taken with a football player.')"
                                    ></span
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow-sm hover:bg-pink-50"
                            >
                                <input
                                    type="checkbox"
                                    value="B"
                                    v-model="multipleAnswers11_12.q11_12"
                                    :disabled="multipleAnswers11_12.q11_12.length >= 2 && !multipleAnswers11_12.q11_12.includes('B')"
                                    class="h-5 w-5 rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                                />
                                <span class="font-medium"
                                    ><span
                                        :data-segment-text="'B There is a competition for children today.'"
                                        v-html="getHighlightedSegment('B There is a competition for children today.')"
                                    ></span
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow-sm hover:bg-pink-50"
                            >
                                <input
                                    type="checkbox"
                                    value="C"
                                    v-model="multipleAnswers11_12.q11_12"
                                    :disabled="multipleAnswers11_12.q11_12.length >= 2 && !multipleAnswers11_12.q11_12.includes('C')"
                                    class="h-5 w-5 rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                                />
                                <span class="font-medium"
                                    ><span
                                        :data-segment-text="'C Parents must stay with their children at all times.'"
                                        v-html="getHighlightedSegment('C Parents must stay with their children at all times.')"
                                    ></span
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow-sm hover:bg-pink-50"
                            >
                                <input
                                    type="checkbox"
                                    value="D"
                                    v-model="multipleAnswers11_12.q11_12"
                                    :disabled="multipleAnswers11_12.q11_12.length >= 2 && !multipleAnswers11_12.q11_12.includes('D')"
                                    class="h-5 w-5 rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                                />
                                <span class="font-medium"
                                    ><span
                                        :data-segment-text="'D Children will need sunhats and drinks.'"
                                        v-html="getHighlightedSegment('D Children will need sunhats and drinks.')"
                                    ></span
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow-sm hover:bg-pink-50"
                            >
                                <input
                                    type="checkbox"
                                    value="E"
                                    v-model="multipleAnswers11_12.q11_12"
                                    :disabled="multipleAnswers11_12.q11_12.length >= 2 && !multipleAnswers11_12.q11_12.includes('E')"
                                    class="h-5 w-5 rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                                />
                                <span class="font-medium"
                                    ><span
                                        :data-segment-text="'E The café has a special offer on meals for children.'"
                                        v-html="getHighlightedSegment('E The café has a special offer on meals for children.')"
                                    ></span
                                ></span>
                            </label>
                        </div>
                        <div class="mt-4 rounded-lg border border-pink-200 bg-pink-50 p-3">
                            <p class="text-sm font-medium text-pink-700">Selected: {{ multipleAnswers11_12.q11_12.length }}/2 answers</p>
                        </div>
                    </div>

                    <!-- Questions 13-14 -->
                    <div id="question-13-14" class="rounded-2xl border border-purple-200 bg-white p-6 shadow-lg">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-purple-700">
                                <span :data-segment-text="'Questions 13 and 14'" v-html="getHighlightedSegment('Questions 13 and 14')"></span>
                            </h3>
                            <p class="text-sm text-gray-600">
                                <span
                                    :data-segment-text="'Choose TWO letters, A-E.'"
                                    v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                ></span>
                            </p>
                        </div>
                        <p class="mb-4 font-medium text-gray-800">
                            <span
                                :data-segment-text="'Which TWO features of the stadium tour are new this year?'"
                                v-html="getHighlightedSegment('Which TWO features of the stadium tour are new this year?')"
                            ></span>
                        </p>
                        <div class="space-y-3">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-purple-200 bg-white p-3 shadow-sm hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="A"
                                    v-model="multipleAnswers13_14.q13_14"
                                    :disabled="multipleAnswers13_14.q13_14.length >= 2 && !multipleAnswers13_14.q13_14.includes('A')"
                                    class="h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="font-medium"
                                    ><span :data-segment-text="'A VIP tour'" v-html="getHighlightedSegment('A VIP tour')"></span
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-purple-200 bg-white p-3 shadow-sm hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="B"
                                    v-model="multipleAnswers13_14.q13_14"
                                    :disabled="multipleAnswers13_14.q13_14.length >= 2 && !multipleAnswers13_14.q13_14.includes('B')"
                                    class="h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="font-medium"
                                    ><span
                                        :data-segment-text="'B 360 cinema experience'"
                                        v-html="getHighlightedSegment('B 360 cinema experience')"
                                    ></span
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-purple-200 bg-white p-3 shadow-sm hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="C"
                                    v-model="multipleAnswers13_14.q13_14"
                                    :disabled="multipleAnswers13_14.q13_14.length >= 2 && !multipleAnswers13_14.q13_14.includes('C')"
                                    class="h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="font-medium"
                                    ><span :data-segment-text="'C audio guide'" v-html="getHighlightedSegment('C audio guide')"></span
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-purple-200 bg-white p-3 shadow-sm hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="D"
                                    v-model="multipleAnswers13_14.q13_14"
                                    :disabled="multipleAnswers13_14.q13_14.length >= 2 && !multipleAnswers13_14.q13_14.includes('D')"
                                    class="h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="font-medium"
                                    ><span :data-segment-text="'D dressing room tour'" v-html="getHighlightedSegment('D dressing room tour')"></span
                                ></span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-purple-200 bg-white p-3 shadow-sm hover:bg-purple-50"
                            >
                                <input
                                    type="checkbox"
                                    value="E"
                                    v-model="multipleAnswers13_14.q13_14"
                                    :disabled="multipleAnswers13_14.q13_14.length >= 2 && !multipleAnswers13_14.q13_14.includes('E')"
                                    class="h-5 w-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="font-medium"
                                    ><span
                                        :data-segment-text="'E tours in other languages'"
                                        v-html="getHighlightedSegment('E tours in other languages')"
                                    ></span
                                ></span>
                            </label>
                        </div>
                        <div class="mt-4 rounded-lg border border-purple-200 bg-purple-50 p-3">
                            <p class="text-sm font-medium text-purple-700">Selected: {{ multipleAnswers13_14.q13_14.length }}/2 answers</p>
                        </div>
                    </div>

                    <!-- Questions 15-20 -->
                    <div
                        id="question-15-20"
                        class="rounded-2xl border-2 border-purple-200 bg-gradient-to-br from-pink-50 via-white to-purple-50 p-6 shadow-2xl"
                    >
                        <div class="mb-6">
                            <h3 class="mb-2 bg-gradient-to-r from-pink-600 to-purple-700 bg-clip-text text-xl font-bold text-transparent">
                                <span :data-segment-text="'Questions 15-20'" v-html="getHighlightedSegment('Questions 15-20')"></span>
                            </h3>
                            <p class="mb-2 text-gray-600">
                                <span
                                    :data-segment-text="'Which event in the history of football in the UK took place in each of the following years?'"
                                    v-html="
                                        getHighlightedSegment(
                                            'Which event in the history of football in the UK took place in each of the following years?',
                                        )
                                    "
                                ></span>
                            </p>
                            <p class="font-semibold text-purple-800">
                                <span
                                    :data-segment-text="'Choose SIX answers from the box and write the correct letter, A-H, next to Questions 15-20.'"
                                    v-html="
                                        getHighlightedSegment(
                                            'Choose SIX answers from the box and write the correct letter, A-H, next to Questions 15-20.',
                                        )
                                    "
                                ></span>
                            </p>
                        </div>

                        <div class="mb-8 rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-xl">
                            <h4 class="mb-4 bg-gradient-to-r from-pink-500 to-purple-500 bg-clip-text text-center text-lg font-bold text-transparent">
                                <span
                                    :data-segment-text="'Events in the history of football'"
                                    v-html="getHighlightedSegment('Events in the history of football')"
                                ></span>
                            </h4>
                            <div class="grid grid-cols-1 gap-x-8 gap-y-3 text-gray-700 md:grid-cols-2">
                                <p>
                                    <strong class="font-semibold text-pink-600">A</strong>
                                    <span
                                        :data-segment-text="'the introduction of pay for the players'"
                                        v-html="getHighlightedSegment('the introduction of pay for the players')"
                                    ></span>
                                </p>
                                <p>
                                    <strong class="font-semibold text-pink-600">B</strong>
                                    <span
                                        :data-segment-text="'a change to the design of the goal'"
                                        v-html="getHighlightedSegment('a change to the design of the goal')"
                                    ></span>
                                </p>
                                <p>
                                    <strong class="font-semibold text-pink-600">C</strong>
                                    <span
                                        :data-segment-text="'the first use of lights for matches'"
                                        v-html="getHighlightedSegment('the first use of lights for matches')"
                                    ></span>
                                </p>
                                <p>
                                    <strong class="font-semibold text-pink-600">D</strong>
                                    <span
                                        :data-segment-text="'the introduction of goalkeepers'"
                                        v-html="getHighlightedSegment('the introduction of goalkeepers')"
                                    ></span>
                                </p>
                                <p>
                                    <strong class="font-semibold text-pink-600">E</strong>
                                    <span
                                        :data-segment-text="'the first international match'"
                                        v-html="getHighlightedSegment('the first international match')"
                                    ></span>
                                </p>
                                <p>
                                    <strong class="font-semibold text-pink-600">F</strong>
                                    <span
                                        :data-segment-text="'two changes to the rules of the game'"
                                        v-html="getHighlightedSegment('two changes to the rules of the game')"
                                    ></span>
                                </p>
                                <p>
                                    <strong class="font-semibold text-pink-600">G</strong>
                                    <span
                                        :data-segment-text="'the introduction of fee for spectators'"
                                        v-html="getHighlightedSegment('the introduction of fee for spectators')"
                                    ></span>
                                </p>
                                <p>
                                    <strong class="font-semibold text-pink-600">H</strong>
                                    <span
                                        :data-segment-text="'an agreement on the length of a game'"
                                        v-html="getHighlightedSegment('an agreement on the length of a game')"
                                    ></span>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="flex items-center gap-4 rounded-xl border-2 border-gray-100 bg-white p-3 shadow-lg" id="question-15">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 font-bold text-white shadow-xl"
                                        >15</span
                                    >
                                    <span class="text-lg font-bold text-gray-700" :data-segment-text="'1870'">1870</span>
                                    <select
                                        v-model="answers.q15"
                                        class="w-full rounded-lg border-2 border-purple-200 bg-purple-50 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-300 focus:outline-none"
                                    >
                                        <option value="">Select...</option>
                                        <option v-for="opt in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :value="opt">{{ opt }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-4 rounded-xl border-2 border-gray-100 bg-white p-3 shadow-lg" id="question-16">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 font-bold text-white shadow-xl"
                                        >16</span
                                    >
                                    <span class="text-lg font-bold text-gray-700" :data-segment-text="'1874'">1874</span>
                                    <select
                                        v-model="answers.q16"
                                        class="w-full rounded-lg border-2 border-purple-200 bg-purple-50 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-300 focus:outline-none"
                                    >
                                        <option value="">Select...</option>
                                        <option v-for="opt in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :value="opt">{{ opt }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-4 rounded-xl border-2 border-gray-100 bg-white p-3 shadow-lg" id="question-17">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 font-bold text-white shadow-xl"
                                        >17</span
                                    >
                                    <span class="text-lg font-bold text-gray-700" :data-segment-text="'1875'">1875</span>
                                    <select
                                        v-model="answers.q17"
                                        class="w-full rounded-lg border-2 border-purple-200 bg-purple-50 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-300 focus:outline-none"
                                    >
                                        <option value="">Select...</option>
                                        <option v-for="opt in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :value="opt">{{ opt }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-4 rounded-xl border-2 border-gray-100 bg-white p-3 shadow-lg" id="question-18">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 font-bold text-white shadow-xl"
                                        >18</span
                                    >
                                    <span class="text-lg font-bold text-gray-700" :data-segment-text="'1877'">1877</span>
                                    <select
                                        v-model="answers.q18"
                                        class="w-full rounded-lg border-2 border-purple-200 bg-purple-50 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-300 focus:outline-none"
                                    >
                                        <option value="">Select...</option>
                                        <option v-for="opt in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :value="opt">{{ opt }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-4 rounded-xl border-2 border-gray-100 bg-white p-3 shadow-lg" id="question-19">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 font-bold text-white shadow-xl"
                                        >19</span
                                    >
                                    <span class="text-lg font-bold text-gray-700" :data-segment-text="'1878'">1878</span>
                                    <select
                                        v-model="answers.q19"
                                        class="w-full rounded-lg border-2 border-purple-200 bg-purple-50 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-300 focus:outline-none"
                                    >
                                        <option value="">Select...</option>
                                        <option v-for="opt in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :value="opt">{{ opt }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-4 rounded-xl border-2 border-gray-100 bg-white p-3 shadow-lg" id="question-20">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 font-bold text-white shadow-xl"
                                        >20</span
                                    >
                                    <span class="text-lg font-bold text-gray-700" :data-segment-text="'1880'">1880</span>
                                    <select
                                        v-model="answers.q20"
                                        class="w-full rounded-lg border-2 border-purple-200 bg-purple-50 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-300 focus:outline-none"
                                    >
                                        <option value="">Select...</option>
                                        <option v-for="opt in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :value="opt">{{ opt }}</option>
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
