<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
interface Props {}

// Local types for this component
interface AnswersRecord {
    q21: string;
    q22: string;
    q23: string;
    q24: string;
    q25: string;
    q26: string;
    q27: string;
    q28: string;
    q29: string;
    q30: string;
}

interface MultipleAnswersRecord {
    q21_22: string[];
    q23_24: string[];
    q25_26: string[];
}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

// Listening Part 3 component

// Single choice answers for questions 21-30
const answers = ref<AnswersRecord>({
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

// Multiple choice answers for grouped questions
const multipleAnswers = ref<MultipleAnswersRecord>({
    q21_22: [],
    q23_24: [],
    q25_26: [],
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
    { text: 'PART 3', offset: 0 },
    { text: 'Questions 21 and 22', offset: 7 },
    { text: 'Choose TWO letters', offset: 28 },
    { text: 'The Impact of Loneliness on Health and Society', offset: 46 },
    { text: 'Which TWO things do the students both believe are responsible for the increase in loneliness?', offset: 94 },
    { text: 'A. Social media', offset: 189 },
    { text: 'B. Smaller nuclear families', offset: 205 },
    { text: 'C. Urban design', offset: 232 },
    { text: 'D. Longer lifespans', offset: 248 },
    { text: 'E. A mobile workforce', offset: 268 },
    { text: 'Questions 23-24', offset: 290 },
    { text: 'Choose TWO letters, A-E.', offset: 306 },
    { text: 'Which TWO health risks associated with loneliness do the students agree are based on solid evidence?', offset: 332 },
    { text: 'A. A weakened immune system', offset: 436 },
    { text: 'B. Dementia', offset: 464 },
    { text: 'C. Cancer', offset: 476 },
    { text: 'D. Obesity', offset: 486 },
    { text: 'E. Cardiovascular disease', offset: 497 },
    { text: 'Questions 25-26', offset: 523 },
    { text: 'Choose TWO letters, A-E.', offset: 539 },
    { text: 'Which TWO opinions do both the students express about the evolutionary theory of loneliness?', offset: 565 },
    { text: 'A. It has little practical relevance.', offset: 664 },
    { text: 'B. It needs further investigation.', offset: 699 },
    { text: 'C. It is misleading.', offset: 732 },
    { text: 'D. It should be more widely accepted.', offset: 751 },
    { text: 'E. It is difficult to understand.', offset: 787 },
    { text: 'Questions 27-30', offset: 819 },
    { text: 'Choose the correct letter, A, B, or C.', offset: 835 },
    { text: 'Loneliness and mental health', offset: 874 },
    { text: 'When comparing loneliness to depression, the students', offset: 902 },
    { text: 'A. Doubt that there will ever be a medical cure for loneliness.', offset: 959 },
    { text: 'B. Claim that the link between loneliness and mental health is overstated.', offset: 1023 },
    { text: 'C. Express frustration that loneliness is not taken more seriously.', offset: 1097 },
    { text: 'Why do the students decide to start their presentation with an example from their own experience?', offset: 1167 },
    { text: 'A. To explain how difficult loneliness can be', offset: 1270 },
    { text: 'B. To highlight a situation that most students will recognise', offset: 1314 },
    { text: 'C. To emphasise that feeling lonely is more common for men than women', offset: 1374 },
    { text: 'The students agree that talking to strangers is a good strategy for dealing with loneliness because', offset: 1446 },
    { text: 'A. It creates a sense of belonging.', offset: 1550 },
    { text: 'B. It builds self-confidence.', offset: 1584 },
    { text: 'C. It makes people feel more positive.', offset: 1612 },
    { text: 'The students find it difficult to understand why solitude is considered to be', offset: 1649 },
    { text: 'A. Similar to loneliness.', offset: 1732 },
    { text: 'B. Necessary for mental health.', offset: 1757 },
    { text: 'C. An enjoyable experience.', offset: 1788 },
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

// Expose methods for parent component
const getAnswers = () => {
    const allAnswers: Record<string, string> = {};

    // Copy single answers
    (Object.keys(answers.value) as Array<keyof AnswersRecord>).forEach((k) => {
        allAnswers[String(k)] = String(answers.value[k]);
    });

    // Handle grouped multiple choice for questions 21-22 by mapping first two selections
    if (Array.isArray(multipleAnswers.value.q21_22) && multipleAnswers.value.q21_22.length > 0) {
        const selectedOptions = multipleAnswers.value.q21_22;
        allAnswers.q21 = selectedOptions[0] || '';
        allAnswers.q22 = selectedOptions[1] || '';
    }

    // Handle grouped multiple choice for questions 23-24 similarly (map to q23 and q24)
    if (Array.isArray(multipleAnswers.value.q23_24) && multipleAnswers.value.q23_24.length > 0) {
        const selectedOptions = multipleAnswers.value.q23_24;
        allAnswers.q23 = selectedOptions[0] || '';
        allAnswers.q24 = selectedOptions[1] || '';
    }

    if (Array.isArray(multipleAnswers.value.q25_26) && multipleAnswers.value.q25_26.length > 0) {
        const selectedOptions = multipleAnswers.value.q25_26;
        allAnswers.q25 = selectedOptions[0] || '';
        allAnswers.q26 = selectedOptions[1] || '';
    }

    return allAnswers;
};

// Watch for changes and auto-save
watch([answers, multipleAnswers], () => {}, { deep: true });

const handleMultipleChoice = (questionGroup: keyof MultipleAnswersRecord, option: string) => {
    const key = questionGroup;
    const currentAnswers = multipleAnswers.value[key];

    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        // Remove if already selected
        currentAnswers.splice(index, 1);
    } else {
        // Different groups have different max selections
        const maxAllowed = 2;
        if (currentAnswers.length < maxAllowed) {
            currentAnswers.push(option);
        }
    }
};

const isSelected = (questionGroup: keyof MultipleAnswersRecord, option: string) => {
    const arr = multipleAnswers.value[questionGroup];
    return Array.isArray(arr) && arr.includes(option);
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    let targetId = `question-${questionNumber}`;
    if (questionNumber >= 21 && questionNumber <= 22) {
        targetId = 'question-21-22';
    } else if (questionNumber >= 23 && questionNumber <= 24) {
        targetId = 'question-23-24';
    } else if (questionNumber >= 25 && questionNumber <= 26) {
        targetId = 'question-25-26';
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
    answers: ((): any => ({ ...answers.value, ...multipleAnswers.value }))(),
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
                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-600 sm:h-7 sm:w-7 md:h-8 md:w-8"
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
                                <span :data-segment-text="'PART 3'" v-html="getHighlightedSegment('PART 3')"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto bg-green-50 pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="mb-8 text-center">
                        <h2
                            class="text-2xl font-bold text-green-800"
                            :data-segment-text="'The Impact of Loneliness on Health and Society'"
                            v-html="getHighlightedSegment('The Impact of Loneliness on Health and Society')"
                        ></h2>
                    </div>
                    <div class="space-y-8">
                        <section
                            id="question-21-22"
                            class="transform rounded-2xl border-2 border-green-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-green-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-10 w-16 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(16, 185, 129, 0.4)"
                                >
                                    21-22
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-2 font-semibold text-gray-800"
                                        :data-segment-text="'Choose TWO letters'"
                                        v-html="getHighlightedSegment('Choose TWO letters')"
                                    ></p>
                                    <p
                                        class="mb-4 text-gray-700"
                                        :data-segment-text="'Which TWO things do the students both believe are responsible for the increase in loneliness?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Which TWO things do the students both believe are responsible for the increase in loneliness?',
                                            )
                                        "
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="A"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('A')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. Social media'"
                                                v-html="getHighlightedSegment('A. Social media')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="B"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('B')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. Smaller nuclear families'"
                                                v-html="getHighlightedSegment('B. Smaller nuclear families')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="C"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('C')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. Urban design'"
                                                v-html="getHighlightedSegment('C. Urban design')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="D"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('D')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'D. Longer lifespans'"
                                                v-html="getHighlightedSegment('D. Longer lifespans')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="E"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('E')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'E. A mobile workforce'"
                                                v-html="getHighlightedSegment('E. A mobile workforce')"
                                            ></span>
                                        </label>
                                    </div>
                                    <div class="mt-4 rounded-lg border border-green-200 bg-white p-3 shadow">
                                        <p class="font-medium text-green-700">Selected: {{ multipleAnswers.q21_22.length }}/2 answers</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section
                            id="question-23-24"
                            class="transform rounded-2xl border-2 border-green-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-green-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-10 w-16 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(16, 185, 129, 0.4)"
                                >
                                    23-24
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-2 font-semibold text-gray-800"
                                        :data-segment-text="'Choose TWO letters, A-E.'"
                                        v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                    ></p>
                                    <p
                                        class="mb-4 text-gray-700"
                                        :data-segment-text="'Which TWO health risks associated with loneliness do the students agree are based on solid evidence?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Which TWO health risks associated with loneliness do the students agree are based on solid evidence?',
                                            )
                                        "
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="A"
                                                v-model="multipleAnswers.q23_24"
                                                :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('A')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. A weakened immune system'"
                                                v-html="getHighlightedSegment('A. A weakened immune system')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="B"
                                                v-model="multipleAnswers.q23_24"
                                                :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('B')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. Dementia'"
                                                v-html="getHighlightedSegment('B. Dementia')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="C"
                                                v-model="multipleAnswers.q23_24"
                                                :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('C')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. Cancer'"
                                                v-html="getHighlightedSegment('C. Cancer')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="D"
                                                v-model="multipleAnswers.q23_24"
                                                :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('D')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'D. Obesity'"
                                                v-html="getHighlightedSegment('D. Obesity')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="E"
                                                v-model="multipleAnswers.q23_24"
                                                :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes('E')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'E. Cardiovascular disease'"
                                                v-html="getHighlightedSegment('E. Cardiovascular disease')"
                                            ></span>
                                        </label>
                                    </div>
                                    <div class="mt-4 rounded-lg border border-green-200 bg-white p-3 shadow">
                                        <p class="font-medium text-green-700">Selected: {{ multipleAnswers.q23_24.length }}/2 answers</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section
                            id="question-25-26"
                            class="transform rounded-2xl border-2 border-green-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-green-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div
                                    class="flex h-10 w-16 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-md"
                                    style="box-shadow: 2px 2px 5px rgba(16, 185, 129, 0.4)"
                                >
                                    25-26
                                </div>
                                <div class="w-full">
                                    <p
                                        class="mb-2 font-semibold text-gray-800"
                                        :data-segment-text="'Choose TWO letters, A-E.'"
                                        v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                    ></p>
                                    <p
                                        class="mb-4 text-gray-700"
                                        :data-segment-text="'Which TWO opinions do both the students express about the evolutionary theory of loneliness?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Which TWO opinions do both the students express about the evolutionary theory of loneliness?',
                                            )
                                        "
                                    ></p>
                                    <div class="space-y-3">
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="A"
                                                v-model="multipleAnswers.q25_26"
                                                :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('A')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'A. It has little practical relevance.'"
                                                v-html="getHighlightedSegment('A. It has little practical relevance.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="B"
                                                v-model="multipleAnswers.q25_26"
                                                :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('B')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'B. It needs further investigation.'"
                                                v-html="getHighlightedSegment('B. It needs further investigation.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="C"
                                                v-model="multipleAnswers.q25_26"
                                                :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('C')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'C. It is misleading.'"
                                                v-html="getHighlightedSegment('C. It is misleading.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="D"
                                                v-model="multipleAnswers.q25_26"
                                                :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('D')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'D. It should be more widely accepted.'"
                                                v-html="getHighlightedSegment('D. It should be more widely accepted.')"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100">
                                            <input
                                                type="checkbox"
                                                value="E"
                                                v-model="multipleAnswers.q25_26"
                                                :disabled="multipleAnswers.q25_26.length >= 2 && !multipleAnswers.q25_26.includes('E')"
                                                class="h-5 w-5 rounded border-green-400 text-green-600 focus:ring-green-500"
                                            />
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'E. It is difficult to understand.'"
                                                v-html="getHighlightedSegment('E. It is difficult to understand.')"
                                            ></span>
                                        </label>
                                    </div>
                                    <div class="mt-4 rounded-lg border border-green-200 bg-white p-3 shadow">
                                        <p class="font-medium text-green-700">Selected: {{ multipleAnswers.q25_26.length }}/2 answers</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section
                            id="question-27-30"
                            class="transform rounded-2xl border-2 border-green-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-green-300 hover:shadow-2xl"
                        >
                            <div class="flex items-start space-x-4">
                                <div class="w-full">
                                    <p
                                        class="mb-2 font-semibold text-gray-800"
                                        :data-segment-text="'Choose the correct letter, A, B, or C.'"
                                        v-html="getHighlightedSegment('Choose the correct letter, A, B, or C.')"
                                    ></p>
                                    <p
                                        class="mb-4 text-gray-700"
                                        :data-segment-text="'Loneliness and mental health'"
                                        v-html="getHighlightedSegment('Loneliness and mental health')"
                                    ></p>

                                    <!-- Q27 -->
                                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 shadow-md">
                                        <div class="mb-2 flex items-center space-x-3">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-md"
                                                style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                            >
                                                27
                                            </div>
                                            <p
                                                class="mb-2"
                                                :data-segment-text="'When comparing loneliness to depression, the students'"
                                                v-html="getHighlightedSegment('When comparing loneliness to depression, the students')"
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q27"
                                                    value="A"
                                                    v-model="answers.q27"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'A. Doubt that there will ever be a medical cure for loneliness.'"
                                                    v-html="getHighlightedSegment('A. Doubt that there will ever be a medical cure for loneliness.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q27"
                                                    value="B"
                                                    v-model="answers.q27"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'B. Claim that the link between loneliness and mental health is overstated.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'B. Claim that the link between loneliness and mental health is overstated.',
                                                        )
                                                    "
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q27"
                                                    value="C"
                                                    v-model="answers.q27"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'C. Express frustration that loneliness is not taken more seriously.'"
                                                    v-html="
                                                        getHighlightedSegment('C. Express frustration that loneliness is not taken more seriously.')
                                                    "
                                                ></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q28 -->
                                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 shadow-md">
                                        <div class="mb-2 flex items-center space-x-3">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-md"
                                                style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                            >
                                                28
                                            </div>
                                            <p
                                                class="mb-2"
                                                :data-segment-text="'Why do the students decide to start their presentation with an example from their own experience?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Why do the students decide to start their presentation with an example from their own experience?',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q28"
                                                    value="A"
                                                    v-model="answers.q28"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'A. To explain how difficult loneliness can be'"
                                                    v-html="getHighlightedSegment('A. To explain how difficult loneliness can be')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q28"
                                                    value="B"
                                                    v-model="answers.q28"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'B. To highlight a situation that most students will recognise'"
                                                    v-html="getHighlightedSegment('B. To highlight a situation that most students will recognise')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q28"
                                                    value="C"
                                                    v-model="answers.q28"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'C. To emphasise that feeling lonely is more common for men than women'"
                                                    v-html="
                                                        getHighlightedSegment('C. To emphasise that feeling lonely is more common for men than women')
                                                    "
                                                ></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q29 -->
                                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 shadow-md">
                                        <div class="mb-2 flex items-center space-x-3">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-md"
                                                style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                            >
                                                29
                                            </div>
                                            <p
                                                class="mb-2"
                                                :data-segment-text="'The students agree that talking to strangers is a good strategy for dealing with loneliness because'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'The students agree that talking to strangers is a good strategy for dealing with loneliness because',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q29"
                                                    value="A"
                                                    v-model="answers.q29"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'A. It creates a sense of belonging.'"
                                                    v-html="getHighlightedSegment('A. It creates a sense of belonging.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q29"
                                                    value="B"
                                                    v-model="answers.q29"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'B. It builds self-confidence.'"
                                                    v-html="getHighlightedSegment('B. It builds self-confidence.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q29"
                                                    value="C"
                                                    v-model="answers.q29"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'C. It makes people feel more positive.'"
                                                    v-html="getHighlightedSegment('C. It makes people feel more positive.')"
                                                ></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q30 -->
                                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 shadow-md">
                                        <div class="mb-2 flex items-center space-x-3">
                                            <div
                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-md"
                                                style="box-shadow: 2px 2px 5px rgba(236, 72, 153, 0.4)"
                                            >
                                                30
                                            </div>
                                            <p
                                                class="mb-2"
                                                :data-segment-text="'The students find it difficult to understand why solitude is considered to be'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'The students find it difficult to understand why solitude is considered to be',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q30"
                                                    value="A"
                                                    v-model="answers.q30"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'A. Similar to loneliness.'"
                                                    v-html="getHighlightedSegment('A. Similar to loneliness.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q30"
                                                    value="B"
                                                    v-model="answers.q30"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'B. Necessary for mental health.'"
                                                    v-html="getHighlightedSegment('B. Necessary for mental health.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q30"
                                                    value="C"
                                                    v-model="answers.q30"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span
                                                    :data-segment-text="'C. An enjoyable experience.'"
                                                    v-html="getHighlightedSegment('C. An enjoyable experience.')"
                                                ></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
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
        background-color: rgba(34, 197, 94, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(34, 197, 94, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(34, 197, 94, 0.1);
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
    background: linear-gradient(to bottom, #10b981, #059669);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #059669, #047857);
}
</style>
