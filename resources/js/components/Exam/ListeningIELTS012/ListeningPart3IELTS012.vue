<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
}

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
    { text: 'QUESTIONS 21-30', offset: 0 },
    { text: 'Choose the correct answer for each question.', offset: 15 },
    { text: 'Questions 21 and 22', offset: 58 },
    { text: 'Choose TWO letters, A-E.', offset: 79 },
    { text: 'Which TWO parts of the introductory stage to their art projects do Jess and Tom agree were useful?', offset: 106 },
    { text: 'A. the Bird Park visit', offset: 200 },
    { text: 'B. the workshop sessions', offset: 223 },
    { text: 'C. the Natural History Museum visit', offset: 247 },
    { text: 'D. the projects done in previous years', offset: 281 },
    { text: 'E. the handouts with research sources', offset: 319 },
    { text: 'Questions 23 and 24', offset: 357 },
    { text: 'Choose TWO letters, A-E.', offset: 378 },
    { text: 'In which TWO ways do both Jess and Tom decide to change their proposals?', offset: 405 },
    { text: 'A. by giving a rationale for their action plans', offset: 479 },
    { text: 'B. by being less specific about the outcome', offset: 527 },
    { text: 'C. by adding a video diary presentation', offset: 570 },
    { text: 'D. by providing a timeline and a mind map', offset: 610 },
    { text: 'E. by making their notes more evaluative', offset: 655 },
    // New segments for 25-30
    { text: 'Questions 25-30', offset: 699 },
    { text: 'Which personal meaning do the students decide to give to each of the following pictures?', offset: 716 },
    { text: 'Choose SIX answers from the box and write the correct letter, A-H, next to Questions 25-30.', offset: 805 },
    { text: 'Personal meanings', offset: 895 },
    { text: 'A. a childhood memory', offset: 914 },
    { text: 'B. hope for the future', offset: 936 },
    { text: 'C. fast movement', offset: 958 },
    { text: 'D. a potential threat', offset: 975 },
    { text: 'E. the power of colour', offset: 996 },
    { text: 'F. the continuity of life', offset: 1018 },
    { text: 'G. protection of nature', offset: 1042 },
    { text: 'H. a confused attitude to nature', offset: 1066 },
    { text: 'Pictures', offset: 1098 },
    { text: '25. Falcon (Landseer)', offset: 1107 },
    { text: '26. Fish hawk (Audubon)', offset: 1129 },
    { text: '27. Kingfisher (van Gogh)', offset: 1153 },
    { text: '28. Portrait of William Wells', offset: 1179 },
    { text: '29. Vairumati (Gauguin)', offset: 1208 },
    { text: '30. Portrait of Giovanni de Medici', offset: 1232 },
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

    return allAnswers;
};

const handleMultipleChoice = (questionGroup: keyof MultipleAnswersRecord, option: string) => {
    const key = questionGroup;
    const currentAnswers = multipleAnswers.value[key];

    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        // Remove if already selected
        currentAnswers.splice(index, 1);
    } else {
        // Different groups have different max selections
        // q21_22 => 2, q23_24 => 2 (both are 'choose TWO' in the UI)
        const maxAllowed = key === 'q21_22' || key === 'q23_24' ? 2 : 3;
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
    // Handle grouped questions for 21-22
    let targetId = `question-${questionNumber}`;
    if (questionNumber >= 21 && questionNumber <= 22) {
        targetId = 'question-21-22';
    }

    // Handle grouped questions for 23-24
    if (questionNumber >= 23 && questionNumber <= 24) {
        targetId = 'question-23-24';
    }

    if (questionNumber >= 25 && questionNumber <= 30) {
        targetId = 'question-25-30';
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
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

defineExpose({
    getAnswers,
    // expose a merged object but widen to any to avoid TypeScript index problems in consumers
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
                                <span :data-segment-text="'QUESTIONS 21-30'" v-html="getHighlightedSegment('QUESTIONS 21-30')"></span>
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
                    <div class="space-y-3 sm:space-y-4 md:space-y-6">
                        <section
                            id="question-21-22"
                            class="rounded-xl border-2 border-green-300 bg-gradient-to-br from-green-50 to-emerald-50 p-4 shadow-lg sm:rounded-2xl sm:p-6 md:p-8"
                        >
                            <div class="mb-4 flex items-center space-x-3">
                                <div
                                    class="flex h-10 w-14 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg"
                                >
                                    <span class="text-base font-bold text-white">21–22</span>
                                </div>
                                <h3
                                    class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent sm:text-xl md:text-2xl"
                                >
                                    <span :data-segment-text="'Questions 21 and 22'" v-html="getHighlightedSegment('Questions 21 and 22')"></span>
                                </h3>
                            </div>

                            <div class="rounded-lg border border-green-200 bg-white p-4 shadow-sm sm:rounded-xl sm:p-6">
                                <p class="mb-2 text-base font-medium text-gray-800 sm:text-lg">
                                    <span
                                        :data-segment-text="'Choose TWO letters, A-E.'"
                                        v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                    ></span>
                                </p>
                                <p class="text-base font-semibold text-gray-800 sm:text-lg">
                                    <span
                                        :data-segment-text="'Which TWO parts of the introductory stage to their art projects do Jess and Tom agree were useful?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Which TWO parts of the introductory stage to their art projects do Jess and Tom agree were useful?',
                                            )
                                        "
                                    ></span>
                                </p>
                            </div>

                            <div class="mt-4 space-y-3">
                                <label
                                    v-for="option in ['A', 'B', 'C', 'D', 'E']"
                                    :key="'21-22-' + option"
                                    @click="handleMultipleChoice('q21_22', option)"
                                    :class="[
                                        'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all sm:rounded-xl sm:p-4',
                                        isSelected('q21_22', option)
                                            ? 'border-green-500 bg-green-100 shadow-md'
                                            : 'border-green-200 bg-white hover:bg-green-50',
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        :value="option"
                                        :checked="isSelected('q21_22', option)"
                                        class="h-5 w-5 flex-shrink-0 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base font-medium text-gray-800 sm:text-lg">
                                        <span
                                            v-if="option === 'A'"
                                            :data-segment-text="'A. the Bird Park visit'"
                                            v-html="getHighlightedSegment('A. the Bird Park visit')"
                                        ></span>
                                        <span
                                            v-if="option === 'B'"
                                            :data-segment-text="'B. the workshop sessions'"
                                            v-html="getHighlightedSegment('B. the workshop sessions')"
                                        ></span>
                                        <span
                                            v-if="option === 'C'"
                                            :data-segment-text="'C. the Natural History Museum visit'"
                                            v-html="getHighlightedSegment('C. the Natural History Museum visit')"
                                        ></span>
                                        <span
                                            v-if="option === 'D'"
                                            :data-segment-text="'D. the projects done in previous years'"
                                            v-html="getHighlightedSegment('D. the projects done in previous years')"
                                        ></span>
                                        <span
                                            v-if="option === 'E'"
                                            :data-segment-text="'E. the handouts with research sources'"
                                            v-html="getHighlightedSegment('E. the handouts with research sources')"
                                        ></span>
                                    </span>
                                </label>
                            </div>
                        </section>

                        <section
                            id="question-23-24"
                            class="rounded-xl border-2 border-green-300 bg-gradient-to-br from-green-50 to-emerald-50 p-4 shadow-lg sm:rounded-2xl sm:p-6 md:p-8"
                        >
                            <div class="mb-4 flex items-center space-x-3">
                                <div
                                    class="flex h-10 w-14 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg"
                                >
                                    <span class="text-base font-bold text-white">23–24</span>
                                </div>
                                <h3
                                    class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent sm:text-xl md:text-2xl"
                                >
                                    <span :data-segment-text="'Questions 23 and 24'" v-html="getHighlightedSegment('Questions 23 and 24')"></span>
                                </h3>
                            </div>

                            <div class="rounded-lg border border-green-200 bg-white p-4 shadow-sm sm:rounded-xl sm:p-6">
                                <p class="mb-2 text-base font-medium text-gray-800 sm:text-lg">
                                    <span
                                        :data-segment-text="'Choose TWO letters, A-E.'"
                                        v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                    ></span>
                                </p>
                                <p class="text-base font-semibold text-gray-800 sm:text-lg">
                                    <span
                                        :data-segment-text="'In which TWO ways do both Jess and Tom decide to change their proposals?'"
                                        v-html="getHighlightedSegment('In which TWO ways do both Jess and Tom decide to change their proposals?')"
                                    ></span>
                                </p>
                            </div>

                            <div class="mt-4 space-y-3">
                                <label
                                    v-for="option in ['A', 'B', 'C', 'D', 'E']"
                                    :key="'23-24-' + option"
                                    @click="handleMultipleChoice('q23_24', option)"
                                    :class="[
                                        'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all sm:rounded-xl sm:p-4',
                                        isSelected('q23_24', option)
                                            ? 'border-green-500 bg-green-100 shadow-md'
                                            : 'border-green-200 bg-white hover:bg-green-50',
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        :value="option"
                                        :checked="isSelected('q23_24', option)"
                                        class="h-5 w-5 flex-shrink-0 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base font-medium text-gray-800 sm:text-lg">
                                        <span
                                            v-if="option === 'A'"
                                            :data-segment-text="'A. by giving a rationale for their action plans'"
                                            v-html="getHighlightedSegment('A. by giving a rationale for their action plans')"
                                        ></span>
                                        <span
                                            v-if="option === 'B'"
                                            :data-segment-text="'B. by being less specific about the outcome'"
                                            v-html="getHighlightedSegment('B. by being less specific about the outcome')"
                                        ></span>
                                        <span
                                            v-if="option === 'C'"
                                            :data-segment-text="'C. by adding a video diary presentation'"
                                            v-html="getHighlightedSegment('C. by adding a video diary presentation')"
                                        ></span>
                                        <span
                                            v-if="option === 'D'"
                                            :data-segment-text="'D. by providing a timeline and a mind map'"
                                            v-html="getHighlightedSegment('D. by providing a timeline and a mind map')"
                                        ></span>
                                        <span
                                            v-if="option === 'E'"
                                            :data-segment-text="'E. by making their notes more evaluative'"
                                            v-html="getHighlightedSegment('E. by making their notes more evaluative')"
                                        ></span>
                                    </span>
                                </label>
                            </div>
                        </section>

                        <section
                            id="question-25-30"
                            class="rounded-xl border-2 border-green-300 bg-gradient-to-br from-green-50 to-emerald-50 p-4 shadow-lg sm:rounded-2xl sm:p-6 md:p-8"
                        >
                            <div class="mb-4 flex items-center space-x-3">
                                <div
                                    class="flex h-10 w-14 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg"
                                >
                                    <span class="text-base font-bold text-white">25–30</span>
                                </div>
                                <h3
                                    class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent sm:text-xl md:text-2xl"
                                >
                                    <span :data-segment-text="'Questions 25-30'" v-html="getHighlightedSegment('Questions 25-30')"></span>
                                </h3>
                            </div>

                            <div class="rounded-lg border border-green-200 bg-white p-4 shadow-sm sm:rounded-xl sm:p-6">
                                <p class="mb-2 text-base font-medium text-gray-800 sm:text-lg">
                                    <span
                                        :data-segment-text="'Which personal meaning do the students decide to give to each of the following pictures?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Which personal meaning do the students decide to give to each of the following pictures?',
                                            )
                                        "
                                    ></span>
                                </p>
                                <p class="text-base font-semibold text-gray-800 sm:text-lg">
                                    <span
                                        :data-segment-text="'Choose SIX answers from the box and write the correct letter, A-H, next to Questions 25-30.'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Choose SIX answers from the box and write the correct letter, A-H, next to Questions 25-30.',
                                            )
                                        "
                                    ></span>
                                </p>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="rounded-xl border border-green-200 bg-white p-4 shadow">
                                    <h4
                                        class="mb-3 text-center text-lg font-bold text-green-700"
                                        :data-segment-text="'Personal meanings'"
                                        v-html="getHighlightedSegment('Personal meanings')"
                                    ></h4>
                                    <ul class="space-y-2 text-base">
                                        <li>
                                            <span
                                                :data-segment-text="'A. a childhood memory'"
                                                v-html="getHighlightedSegment('A. a childhood memory')"
                                            ></span>
                                        </li>
                                        <li>
                                            <span
                                                :data-segment-text="'B. hope for the future'"
                                                v-html="getHighlightedSegment('B. hope for the future')"
                                            ></span>
                                        </li>
                                        <li>
                                            <span :data-segment-text="'C. fast movement'" v-html="getHighlightedSegment('C. fast movement')"></span>
                                        </li>
                                        <li>
                                            <span
                                                :data-segment-text="'D. a potential threat'"
                                                v-html="getHighlightedSegment('D. a potential threat')"
                                            ></span>
                                        </li>
                                        <li>
                                            <span
                                                :data-segment-text="'E. the power of colour'"
                                                v-html="getHighlightedSegment('E. the power of colour')"
                                            ></span>
                                        </li>
                                        <li>
                                            <span
                                                :data-segment-text="'F. the continuity of life'"
                                                v-html="getHighlightedSegment('F. the continuity of life')"
                                            ></span>
                                        </li>
                                        <li>
                                            <span
                                                :data-segment-text="'G. protection of nature'"
                                                v-html="getHighlightedSegment('G. protection of nature')"
                                            ></span>
                                        </li>
                                        <li>
                                            <span
                                                :data-segment-text="'H. a confused attitude to nature'"
                                                v-html="getHighlightedSegment('H. a confused attitude to nature')"
                                            ></span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="space-y-4">
                                    <h4
                                        class="mb-3 text-lg font-bold text-green-700"
                                        :data-segment-text="'Pictures'"
                                        v-html="getHighlightedSegment('Pictures')"
                                    ></h4>
                                    <div
                                        v-for="i in 6"
                                        :key="i"
                                        class="flex items-center justify-between rounded-lg border border-green-200 bg-white p-3 shadow"
                                    >
                                        <p class="font-semibold text-gray-800">
                                            <span
                                                v-if="i === 1"
                                                :data-segment-text="'25. Falcon (Landseer)'"
                                                v-html="getHighlightedSegment('25. Falcon (Landseer)')"
                                            ></span>
                                            <span
                                                v-if="i === 2"
                                                :data-segment-text="'26. Fish hawk (Audubon)'"
                                                v-html="getHighlightedSegment('26. Fish hawk (Audubon)')"
                                            ></span>
                                            <span
                                                v-if="i === 3"
                                                :data-segment-text="'27. Kingfisher (van Gogh)'"
                                                v-html="getHighlightedSegment('27. Kingfisher (van Gogh)')"
                                            ></span>
                                            <span
                                                v-if="i === 4"
                                                :data-segment-text="'28. Portrait of William Wells'"
                                                v-html="getHighlightedSegment('28. Portrait of William Wells')"
                                            ></span>
                                            <span
                                                v-if="i === 5"
                                                :data-segment-text="'29. Vairumati (Gauguin)'"
                                                v-html="getHighlightedSegment('29. Vairumati (Gauguin)')"
                                            ></span>
                                            <span
                                                v-if="i === 6"
                                                :data-segment-text="'30. Portrait of Giovanni de Medici'"
                                                v-html="getHighlightedSegment('30. Portrait of Giovanni de Medici')"
                                            ></span>
                                        </p>
                                        <select
                                            v-model="answers['q' + (24 + i)]"
                                            class="w-24 rounded-md border-green-500 bg-green-100 p-2 shadow-xl focus:border-green-500 focus:ring-green-500"
                                        >
                                            <option disabled value="">Select</option>
                                            <option v-for="char in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :key="char" :value="char">
                                                {{ char }}
                                            </option>
                                        </select>
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
