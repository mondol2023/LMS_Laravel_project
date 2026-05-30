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
    { text: 'PART 2', offset: 0 },
    { text: 'Questions 11-16', offset: 7 },
    { text: 'Choose the correct letter, A, B, or C.', offset: 25 },
    { text: 'Pottery Workshop with Heather Edelman', offset: 66 },
    { text: 'Heather says pottery differs from other art forms because', offset: 103 },
    { text: 'A. It lasts longer in the ground.', offset: 166 },
    { text: 'B. It is practised by more people.', offset: 199 },
    { text: 'C. It can be repaired more easily.', offset: 232 },
    { text: 'Archaeologists sometimes identify the use of ancient pottery from', offset: 265 },
    { text: 'A. The clay it was made with.', offset: 336 },
    { text: 'B. The marks that are on it.', offset: 365 },
    { text: 'C. The basic shape of it.', offset: 393 },
    { text: "Some people join Heather's pottery class because they want to", offset: 419 },
    { text: 'A. Create an item that looks very old.', offset: 486 },
    { text: 'B. Find something that they are good at.', offset: 523 },
    { text: 'C. Make something that will outlive them.', offset: 562 },
    { text: 'What does Heather value most about being a potter?', offset: 602 },
    { text: 'A. Its calming effect', offset: 654 },
    { text: 'B. Its messy nature', offset: 675 },
    { text: 'C. Its physical benefits', offset: 695 },
    { text: 'Most of the visitors to Edelman Pottery', offset: 719 },
    { text: 'A. Bring friends to join courses.', offset: 761 },
    { text: 'B. Have never made a pot before.', offset: 793 },
    { text: 'C. Try to learn techniques too quickly.', offset: 825 },
    { text: 'Heather reminds her visitors that they should', offset: 863 },
    { text: 'A. Put on their aprons.', offset: 913 },
    { text: 'B. Change their clothes.', offset: 936 },
    { text: 'C. Take off their jewellery.', offset: 960 },
    { text: 'Questions 17-18', offset: 988 },
    { text: 'Choose TWO letters, A-E.', offset: 1004 },
    { text: 'Which TWO things does Heather explain about kilns?', offset: 1030 },
    { text: 'A. What their function is', offset: 1083 },
    { text: 'B. When they were invented', offset: 1108 },
    { text: 'C. Ways of keeping them safe', offset: 1134 },
    { text: 'D. Where to put one in your home', offset: 1163 },
    { text: 'E. What some people use instead of one', offset: 1195 },
    { text: 'Questions 19-20', offset: 1233 },
    { text: 'Choose TWO letters, A-E.', offset: 1249 },
    { text: "Which points does Heather make about a potter's tools?", offset: 1275 },
    { text: 'A. Some are hard to hold.', offset: 1332 },
    { text: 'B. Some are worth buying.', offset: 1358 },
    { text: 'C. Some are essential items.', offset: 1384 },
    { text: 'D. Some have memorable names.', offset: 1412 },
    { text: 'E. Some are available for use by participants.', offset: 1442 },
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

const multipleAnswers = reactive({
    q17_18: [],
    q19_20: [],
});

watch(multipleAnswers, (newVal) => {
    answers.value.q17 = newVal.q17_18[0] || '';
    answers.value.q18 = newVal.q17_18[1] || '';
    answers.value.q19 = newVal.q19_20[0] || '';
    answers.value.q20 = newVal.q19_20[1] || '';
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
                                <span :data-segment-text="'Questions 11-20'" v-html="getHighlightedSegment('Questions 11-20')"></span>
                            </p>
                            <p class="text-xs text-gray-600">
                                <span
                                    :data-segment-text="'Choose the correct letter, A, B, or C.'"
                                    v-html="getHighlightedSegment('Choose the correct letter, A, B, or C.')"
                                ></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto bg-pink-50 pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="mb-8 text-center">
                        <h2
                            class="text-2xl font-bold text-pink-800"
                            :data-segment-text="'Pottery Workshop with Heather Edelman'"
                            v-html="getHighlightedSegment('Pottery Workshop with Heather Edelman')"
                        ></h2>
                    </div>
                    <!-- Questions 11-16: Single Choice MCQ -->
                    <div class="space-y-8">
                        <!-- Question 11 -->
                        <div
                            id="question-11"
                            class="transform rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-pink-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                >
                                    11
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-4 font-semibold text-gray-800"
                                        :data-segment-text="'Heather says pottery differs from other art forms because'"
                                        v-html="getHighlightedSegment('Heather says pottery differs from other art forms because')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q11"
                                                value="A"
                                                v-model="answers.q11"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. It lasts longer in the ground.'"
                                                v-html="getHighlightedSegment('A. It lasts longer in the ground.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q11"
                                                value="B"
                                                v-model="answers.q11"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. It is practised by more people.'"
                                                v-html="getHighlightedSegment('B. It is practised by more people.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q11"
                                                value="C"
                                                v-model="answers.q11"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. It can be repaired more easily.'"
                                                v-html="getHighlightedSegment('C. It can be repaired more easily.')"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 12 -->
                        <div
                            id="question-12"
                            class="transform rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-pink-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                >
                                    12
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-4 font-semibold text-gray-800"
                                        :data-segment-text="'Archaeologists sometimes identify the use of ancient pottery from'"
                                        v-html="getHighlightedSegment('Archaeologists sometimes identify the use of ancient pottery from')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q12"
                                                value="A"
                                                v-model="answers.q12"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. The clay it was made with.'"
                                                v-html="getHighlightedSegment('A. The clay it was made with.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q12"
                                                value="B"
                                                v-model="answers.q12"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. The marks that are on it.'"
                                                v-html="getHighlightedSegment('B. The marks that are on it.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q12"
                                                value="C"
                                                v-model="answers.q12"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. The basic shape of it.'"
                                                v-html="getHighlightedSegment('C. The basic shape of it.')"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 13 -->
                        <div
                            id="question-13"
                            class="transform rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-pink-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                >
                                    13
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-4 font-semibold text-gray-800"
                                        :data-segment-text="'Some people join Heather\'s pottery class because they want to'"
                                        v-html="getHighlightedSegment('Some people join Heather\'s pottery class because they want to')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q13"
                                                value="A"
                                                v-model="answers.q13"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. Create an item that looks very old.'"
                                                v-html="getHighlightedSegment('A. Create an item that looks very old.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q13"
                                                value="B"
                                                v-model="answers.q13"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. Find something that they are good at.'"
                                                v-html="getHighlightedSegment('B. Find something that they are good at.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q13"
                                                value="C"
                                                v-model="answers.q13"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. Make something that will outlive them.'"
                                                v-html="getHighlightedSegment('C. Make something that will outlive them.')"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 14 -->
                        <div
                            id="question-14"
                            class="transform rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-pink-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                >
                                    14
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-4 font-semibold text-gray-800"
                                        :data-segment-text="'What does Heather value most about being a potter?'"
                                        v-html="getHighlightedSegment('What does Heather value most about being a potter?')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q14"
                                                value="A"
                                                v-model="answers.q14"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. Its calming effect'"
                                                v-html="getHighlightedSegment('A. Its calming effect')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q14"
                                                value="B"
                                                v-model="answers.q14"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. Its messy nature'"
                                                v-html="getHighlightedSegment('B. Its messy nature')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q14"
                                                value="C"
                                                v-model="answers.q14"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. Its physical benefits'"
                                                v-html="getHighlightedSegment('C. Its physical benefits')"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 15 -->
                        <div
                            id="question-15"
                            class="transform rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-pink-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                >
                                    15
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-4 font-semibold text-gray-800"
                                        :data-segment-text="'Most of the visitors to Edelman Pottery'"
                                        v-html="getHighlightedSegment('Most of the visitors to Edelman Pottery')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q15"
                                                value="A"
                                                v-model="answers.q15"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. Bring friends to join courses.'"
                                                v-html="getHighlightedSegment('A. Bring friends to join courses.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q15"
                                                value="B"
                                                v-model="answers.q15"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. Have never made a pot before.'"
                                                v-html="getHighlightedSegment('B. Have never made a pot before.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q15"
                                                value="C"
                                                v-model="answers.q15"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. Try to learn techniques too quickly.'"
                                                v-html="getHighlightedSegment('C. Try to learn techniques too quickly.')"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 16 -->
                        <div
                            id="question-16"
                            class="transform rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-pink-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                >
                                    16
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-4 font-semibold text-gray-800"
                                        :data-segment-text="'Heather reminds her visitors that they should'"
                                        v-html="getHighlightedSegment('Heather reminds her visitors that they should')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q16"
                                                value="A"
                                                v-model="answers.q16"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. Put on their aprons.'"
                                                v-html="getHighlightedSegment('A. Put on their aprons.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q16"
                                                value="B"
                                                v-model="answers.q16"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. Change their clothes.'"
                                                v-html="getHighlightedSegment('B. Change their clothes.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="radio"
                                                name="q16"
                                                value="C"
                                                v-model="answers.q16"
                                                class="h-5 w-5 border-gray-300 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. Take off their jewellery.'"
                                                v-html="getHighlightedSegment('C. Take off their jewellery.')"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 17-18 -->
                        <div
                            id="question-17-18"
                            class="transform rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-pink-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-10 w-14 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                >
                                    17-18
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-2 font-semibold text-gray-800"
                                        :data-segment-text="'Choose TWO letters, A-E.'"
                                        v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                    ></p>
                                    <p
                                        class="mb-4 text-gray-700"
                                        :data-segment-text="'Which TWO things does Heather explain about kilns?'"
                                        v-html="getHighlightedSegment('Which TWO things does Heather explain about kilns?')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="A"
                                                v-model="multipleAnswers.q17_18"
                                                :disabled="multipleAnswers.q17_18.length >= 2 && !multipleAnswers.q17_18.includes('A')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. What their function is'"
                                                v-html="getHighlightedSegment('A. What their function is')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="B"
                                                v-model="multipleAnswers.q17_18"
                                                :disabled="multipleAnswers.q17_18.length >= 2 && !multipleAnswers.q17_18.includes('B')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. When they were invented'"
                                                v-html="getHighlightedSegment('B. When they were invented')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="C"
                                                v-model="multipleAnswers.q17_18"
                                                :disabled="multipleAnswers.q17_18.length >= 2 && !multipleAnswers.q17_18.includes('C')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. Ways of keeping them safe'"
                                                v-html="getHighlightedSegment('C. Ways of keeping them safe')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="D"
                                                v-model="multipleAnswers.q17_18"
                                                :disabled="multipleAnswers.q17_18.length >= 2 && !multipleAnswers.q17_18.includes('D')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'D. Where to put one in your home'"
                                                v-html="getHighlightedSegment('D. Where to put one in your home')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="E"
                                                v-model="multipleAnswers.q17_18"
                                                :disabled="multipleAnswers.q17_18.length >= 2 && !multipleAnswers.q17_18.includes('E')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'E. What some people use instead of one'"
                                                v-html="getHighlightedSegment('E. What some people use instead of one')"
                                            ></span>
                                        </label>
                                    </div>
                                    <div class="mt-4 rounded-lg border border-pink-200 bg-white p-3 shadow">
                                        <p class="font-medium text-pink-700">Selected: {{ multipleAnswers.q17_18.length }}/2 answers</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 19-20 -->
                        <div
                            id="question-19-20"
                            class="transform rounded-2xl border-2 border-pink-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-pink-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-10 w-14 flex-shrink-0 items-center justify-center rounded-full bg-pink-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                >
                                    19-20
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-2 font-semibold text-gray-800"
                                        :data-segment-text="'Choose TWO letters, A-E.'"
                                        v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                    ></p>
                                    <p
                                        class="mb-4 text-gray-700"
                                        :data-segment-text="'Which points does Heather make about a potter\'s tools?'"
                                        v-html="getHighlightedSegment('Which points does Heather make about a potter\'s tools?')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="A"
                                                v-model="multipleAnswers.q19_20"
                                                :disabled="multipleAnswers.q19_20.length >= 2 && !multipleAnswers.q19_20.includes('A')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. Some are hard to hold.'"
                                                v-html="getHighlightedSegment('A. Some are hard to hold.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="B"
                                                v-model="multipleAnswers.q19_20"
                                                :disabled="multipleAnswers.q19_20.length >= 2 && !multipleAnswers.q19_20.includes('B')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. Some are worth buying.'"
                                                v-html="getHighlightedSegment('B. Some are worth buying.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="C"
                                                v-model="multipleAnswers.q19_20"
                                                :disabled="multipleAnswers.q19_20.length >= 2 && !multipleAnswers.q19_20.includes('C')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. Some are essential items.'"
                                                v-html="getHighlightedSegment('C. Some are essential items.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="D"
                                                v-model="multipleAnswers.q19_20"
                                                :disabled="multipleAnswers.q19_20.length >= 2 && !multipleAnswers.q19_20.includes('D')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'D. Some have memorable names.'"
                                                v-html="getHighlightedSegment('D. Some have memorable names.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-pink-100">
                                            <input
                                                type="checkbox"
                                                value="E"
                                                v-model="multipleAnswers.q19_20"
                                                :disabled="multipleAnswers.q19_20.length >= 2 && !multipleAnswers.q19_20.includes('E')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'E. Some are available for use by participants.'"
                                                v-html="getHighlightedSegment('E. Some are available for use by participants.')"
                                            ></span>
                                        </label>
                                    </div>
                                    <div class="mt-4 rounded-lg border border-pink-200 bg-white p-3 shadow">
                                        <p class="font-medium text-pink-700">Selected: {{ multipleAnswers.q19_20.length }}/2 answers</p>
                                    </div>
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
