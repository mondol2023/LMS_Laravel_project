<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import draftService from '@/services/draftService';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
interface Props {
    phone?: string;
    examId?: string;
}

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
    { text: 'QUESTIONS 21-30', offset: 0 },
    { text: 'Choose the correct answer for each question.', offset: 15 },
    { text: 'Questions 21 and 22', offset: 58 },
    { text: 'Choose the correct letter', offset: 79 },
    { text: 'A, B or C', offset: 104 },
    { text: 'In her home country, Kira had', offset: 113 },
    { text: 'A. completed a course', offset: 147 },
    { text: 'B. done two years of a course', offset: 169 },
    { text: 'C. found her course difficult', offset: 198 },
    { text: 'To succeed with assignments, Kira had to', offset: 227 },
    { text: 'A. read faster', offset: 271 },
    { text: 'B. write faster', offset: 285 },
    { text: 'C. change her way of thinking', offset: 301 },
    { text: 'Questions 23–25', offset: 330 },
    { text: 'Complete the sentences below. Write', offset: 346 },
    { text: 'ONE WORD ONLY', offset: 382 },
    { text: 'for each answer.', offset: 395 },
    { text: 'Kira says that lecturers are easier to', offset: 411 },
    { text: 'than those in her home country.', offset: 454 },
    { text: 'Paul suggests that Kira may be more', offset: 486 },
    { text: 'than when she was studying before.', offset: 526 },
    { text: 'Kira says that students want to discuss things that worry them or that', offset: 563 },
    { text: 'them very much.', offset: 636 },
    { text: 'Questions 26–30', offset: 651 },
    { text: 'Answer the questions below. Write', offset: 667 },
    { text: 'NO MORE THAN THREE WORDS AND/OR A NUMBER', offset: 700 },
    { text: 'How did the students do their practical sessions?', offset: 742 },
    { text: 'In the second semester how often did Kira work in a hospital?', offset: 798 },
    { text: 'How much full-time work did Kira do during the year?', offset: 864 },
    { text: 'Having completed the year, how does Kira feel?', offset: 923 },
    { text: 'In addition to the language, what do overseas students need to become familiar with?', offset: 979 },
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
    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part3');

    // Load saved drafts
    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part3) {
            // Load single answers
            (Object.keys(answers.value) as Array<keyof AnswersRecord>).forEach((key) => {
                if (draftsResponse.data.part3[key]) {
                    answers.value[key] = String(draftsResponse.data.part3[key]);
                }
            });
            // Load multiple answers (deserialize from JSON strings)
            (Object.keys(multipleAnswers.value) as Array<keyof MultipleAnswersRecord>).forEach((key) => {
                if (draftsResponse.data.part3[key]) {
                    try {
                        const parsedValue = JSON.parse(draftsResponse.data.part3[key]);
                        multipleAnswers.value[key] = Array.isArray(parsedValue) ? parsedValue : [];
                    } catch (e) {
                        console.warn(`Failed to parse ${String(key)} data:`, e);
                        multipleAnswers.value[key] = [];
                    }
                }
            });
            console.log('Loaded Listening Part 3 drafts');
        }
    } catch (error) {
        console.error('Failed to load Listening Part 3 drafts:', error);
    }
};

// Save answers to drafts
const saveAnswers = () => {
    if (!autoSaver.value) {
        return;
    }

    const allAnswers: Record<string, string> = {};

    // Copy single answers with explicit keys to avoid index signature issues
    (Object.keys(answers.value) as Array<keyof AnswersRecord>).forEach((k) => {
        allAnswers[String(k)] = String(answers.value[k]);
    });

    // Serialize multiple choice arrays to JSON strings
    (Object.keys(multipleAnswers.value) as Array<keyof MultipleAnswersRecord>).forEach((k) => {
        allAnswers[String(k)] = JSON.stringify(multipleAnswers.value[k]);
    });

    autoSaver.value.saveBatch(allAnswers);
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

// Watch for changes and auto-save
watch(
    [answers, multipleAnswers],
    () => {
        saveAnswers();
    },
    { deep: true },
);

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
                            <div class="mb-4 sm:mb-6">
                                <div class="mb-3 flex items-center space-x-3">
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
                                            :data-segment-text="'Choose the correct letter'"
                                            v-html="getHighlightedSegment('Choose the correct letter')"
                                        ></span>
                                        <strong
                                            class="text-green-600"
                                            :data-segment-text="'A, B or C'"
                                            v-html="getHighlightedSegment('A, B or C')"
                                        ></strong
                                        >.
                                    </p>
                                </div>
                            </div>

                            <!-- Question 21 -->
                            <div class="my-4 mb-6 space-y-2 rounded-2xl border-l-6 border-l-green-400 bg-white p-6 shadow-lg">
                                <div class="mb-2 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">21</span>
                                    </div>
                                    <span
                                        :data-segment-text="'In her home country, Kira had'"
                                        v-html="getHighlightedSegment('In her home country, Kira had')"
                                    ></span>
                                </div>

                                <label
                                    class="flex cursor-pointer items-start gap-2 rounded-lg border border-green-200 bg-white p-3 transition-colors hover:bg-green-100 sm:rounded-xl sm:p-4"
                                >
                                    <input
                                        type="radio"
                                        value="A"
                                        v-model="answers.q21"
                                        class="h-5 w-5 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                        <span
                                            :data-segment-text="'A. completed a course'"
                                            v-html="getHighlightedSegment('A. completed a course')"
                                        ></span>
                                    </span>
                                </label>

                                <label
                                    class="flex cursor-pointer items-start gap-2 rounded-lg border border-green-200 bg-white p-3 transition-colors hover:bg-green-100 sm:rounded-xl sm:p-4"
                                >
                                    <input
                                        type="radio"
                                        value="B"
                                        v-model="answers.q21"
                                        class="h-5 w-5 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                        <span
                                            :data-segment-text="'B. done two years of a course'"
                                            v-html="getHighlightedSegment('B. done two years of a course')"
                                        ></span>
                                    </span>
                                </label>

                                <label
                                    class="flex cursor-pointer items-start gap-2 rounded-lg border border-green-200 bg-white p-3 transition-colors hover:bg-green-100 sm:rounded-xl sm:p-4"
                                >
                                    <input
                                        type="radio"
                                        value="C"
                                        v-model="answers.q21"
                                        class="h-5 w-5 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                        <span
                                            :data-segment-text="'C. found her course difficult'"
                                            v-html="getHighlightedSegment('C. found her course difficult')"
                                        ></span>
                                    </span>
                                </label>
                            </div>

                            <!-- Question 22 -->
                            <div class="space-y-2 rounded-2xl border-l-6 border-l-green-400 bg-white p-6 shadow-lg">
                                <div class="mb-2 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">22</span>
                                    </div>
                                    <span
                                        :data-segment-text="'To succeed with assignments, Kira had to'"
                                        v-html="getHighlightedSegment('To succeed with assignments, Kira had to')"
                                    ></span>
                                </div>

                                <label
                                    class="flex cursor-pointer items-start gap-2 rounded-lg border border-green-200 bg-white p-3 transition-colors hover:bg-green-100 sm:rounded-xl sm:p-4"
                                >
                                    <input
                                        type="radio"
                                        value="A"
                                        v-model="answers.q22"
                                        class="h-5 w-5 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base font-medium text-gray-800 sm:text-lg"
                                        ><span :data-segment-text="'A. read faster'" v-html="getHighlightedSegment('A. read faster')"></span
                                    ></span>
                                </label>

                                <label
                                    class="flex cursor-pointer items-start gap-2 rounded-lg border border-green-200 bg-white p-3 transition-colors hover:bg-green-100 sm:rounded-xl sm:p-4"
                                >
                                    <input
                                        type="radio"
                                        value="B"
                                        v-model="answers.q22"
                                        class="h-5 w-5 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base font-medium text-gray-800 sm:text-lg"
                                        ><span :data-segment-text="'B. write faster'" v-html="getHighlightedSegment('B. write faster')"></span
                                    ></span>
                                </label>

                                <label
                                    class="flex cursor-pointer items-start gap-2 rounded-lg border border-green-200 bg-white p-3 transition-colors hover:bg-green-100 sm:rounded-xl sm:p-4"
                                >
                                    <input
                                        type="radio"
                                        value="C"
                                        v-model="answers.q22"
                                        class="h-5 w-5 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base font-medium text-gray-800 sm:text-lg"
                                        ><span
                                            :data-segment-text="'C. change her way of thinking'"
                                            v-html="getHighlightedSegment('C. change her way of thinking')"
                                        ></span
                                    ></span>
                                </label>
                            </div>
                        </section>

                        <section id="questions-23-30" class="space-y-6">
                            <!-- QUESTIONS 23–25 -->
                            <div
                                class="rounded-xl border-2 border-green-300 bg-gradient-to-br from-green-50 to-emerald-50 p-4 shadow-lg sm:p-6 md:p-8"
                            >
                                <div class="mb-4 flex items-center space-x-3">
                                    <div
                                        class="flex h-10 w-16 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg"
                                    >
                                        <span class="text-base font-bold text-white">23–25</span>
                                    </div>
                                    <h3 class="bg-gradient-to-r from-green-700 to-emerald-700 bg-clip-text text-xl font-bold text-transparent">
                                        <span :data-segment-text="'Questions 23–25'" v-html="getHighlightedSegment('Questions 23–25')"></span>
                                    </h3>
                                </div>

                                <p class="mb-4 text-base font-medium text-gray-800 sm:text-lg">
                                    <span
                                        :data-segment-text="'Complete the sentences below. Write'"
                                        v-html="getHighlightedSegment('Complete the sentences below. Write')"
                                    ></span>
                                    <strong
                                        class="text-green-700"
                                        :data-segment-text="'ONE WORD ONLY'"
                                        v-html="getHighlightedSegment('ONE WORD ONLY')"
                                    ></strong>
                                    <span :data-segment-text="'for each answer.'" v-html="getHighlightedSegment('for each answer.')"></span>
                                </p>

                                <!-- Q23 -->
                                <div class="mb-3 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">23</span>
                                    </div>
                                    <span
                                        :data-segment-text="'Kira says that lecturers are easier to'"
                                        v-html="getHighlightedSegment('Kira says that lecturers are easier to')"
                                    ></span>
                                    <input
                                        v-model="answers.q23"
                                        type="text"
                                        placeholder="answer"
                                        class="mx-1 w-full rounded-xl border border-green-300 px-2 py-1 shadow focus:ring-2 focus:ring-green-500 sm:w-64"
                                    />
                                    <span
                                        :data-segment-text="'than those in her home country.'"
                                        v-html="getHighlightedSegment('than those in her home country.')"
                                    ></span>
                                </div>

                                <!-- Q24 -->
                                <div class="mb-3 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">24</span>
                                    </div>
                                    <span
                                        :data-segment-text="'Paul suggests that Kira may be more'"
                                        v-html="getHighlightedSegment('Paul suggests that Kira may be more')"
                                    ></span>
                                    <input
                                        v-model="answers.q24"
                                        type="text"
                                        placeholder="answer"
                                        class="mx-1 w-full rounded-xl border border-green-300 px-2 py-1 shadow focus:ring-2 focus:ring-green-500 sm:w-64"
                                    />
                                    <span
                                        :data-segment-text="'than when she was studying before.'"
                                        v-html="getHighlightedSegment('than when she was studying before.')"
                                    ></span>
                                </div>

                                <!-- Q25 -->
                                <div class="mb-3 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">25</span>
                                    </div>
                                    <span
                                        :data-segment-text="'Kira says that students want to discuss things that worry them or that'"
                                        v-html="getHighlightedSegment('Kira says that students want to discuss things that worry them or that')"
                                    ></span>
                                    <input
                                        v-model="answers.q25"
                                        type="text"
                                        placeholder="answer"
                                        class="mx-1 w-full rounded-xl border border-green-300 px-2 py-1 shadow focus:ring-2 focus:ring-green-500 sm:w-64"
                                    />
                                    <span :data-segment-text="'them very much.'" v-html="getHighlightedSegment('them very much.')"></span>
                                </div>
                            </div>

                            <!-- QUESTIONS 26–30 -->
                            <div
                                class="rounded-xl border-2 border-green-300 bg-gradient-to-br from-green-50 to-emerald-50 p-4 shadow-lg sm:p-6 md:p-8"
                            >
                                <div class="mb-4 flex items-center space-x-3">
                                    <div
                                        class="flex h-10 w-16 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg"
                                    >
                                        <span class="text-base font-bold text-white">26–30</span>
                                    </div>
                                    <h3 class="bg-gradient-to-r from-green-700 to-emerald-700 bg-clip-text text-xl font-bold text-transparent">
                                        <span :data-segment-text="'Questions 26–30'" v-html="getHighlightedSegment('Questions 26–30')"></span>
                                    </h3>
                                </div>

                                <p class="mb-4 text-base font-medium text-gray-800 sm:text-lg">
                                    <span
                                        :data-segment-text="'Answer the questions below. Write'"
                                        v-html="getHighlightedSegment('Answer the questions below. Write')"
                                    ></span>
                                    <strong
                                        class="text-green-700"
                                        :data-segment-text="'NO MORE THAN THREE WORDS AND/OR A NUMBER'"
                                        v-html="getHighlightedSegment('NO MORE THAN THREE WORDS AND/OR A NUMBER')"
                                    ></strong
                                    >.
                                </p>

                                <!-- Q26 -->
                                <div class="mb-1 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">26</span>
                                    </div>
                                    <span
                                        :data-segment-text="'How did the students do their practical sessions?'"
                                        v-html="getHighlightedSegment('How did the students do their practical sessions?')"
                                    ></span>
                                    <input
                                        v-model="answers.q26"
                                        type="text"
                                        placeholder="answer"
                                        class="m-2 w-full rounded-xl border border-green-300 px-2 py-1 shadow focus:ring-2 focus:ring-green-500 sm:w-72"
                                    />
                                </div>

                                <!-- Q27 -->
                                <div class="mb-1 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">27</span>
                                    </div>
                                    <span
                                        :data-segment-text="'In the second semester how often did Kira work in a hospital?'"
                                        v-html="getHighlightedSegment('In the second semester how often did Kira work in a hospital?')"
                                    ></span>
                                    <input
                                        v-model="answers.q27"
                                        type="text"
                                        placeholder="answer"
                                        class="m-2 w-full rounded-xl border border-green-300 px-2 py-1 shadow focus:ring-2 focus:ring-green-500 sm:w-72"
                                    />
                                </div>

                                <!-- Q28 -->
                                <div class="mb-1 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">28</span>
                                    </div>
                                    <span
                                        :data-segment-text="'How much full-time work did Kira do during the year?'"
                                        v-html="getHighlightedSegment('How much full-time work did Kira do during the year?')"
                                    ></span>
                                    <input
                                        v-model="answers.q28"
                                        type="text"
                                        placeholder="answer"
                                        class="m-2 w-full rounded-xl border border-green-300 px-2 py-1 shadow focus:ring-2 focus:ring-green-500 sm:w-72"
                                    />
                                </div>

                                <!-- Q29 -->
                                <div class="mb-1 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">29</span>
                                    </div>
                                    <span
                                        :data-segment-text="'Having completed the year, how does Kira feel?'"
                                        v-html="getHighlightedSegment('Having completed the year, how does Kira feel?')"
                                    ></span>
                                    <input
                                        v-model="answers.q29"
                                        type="text"
                                        placeholder="answer"
                                        class="m-2 w-full rounded-xl border border-green-300 px-2 py-1 shadow focus:ring-2 focus:ring-green-500 sm:w-72"
                                    />
                                </div>

                                <!-- Q30 -->
                                <div class="mb-4 flex items-center text-base font-semibold text-gray-800 sm:text-lg">
                                    <div
                                        class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 p-4 shadow"
                                    >
                                        <span class="text-xs font-bold text-white">30</span>
                                    </div>
                                    <span
                                        :data-segment-text="'In addition to the language, what do overseas students need to become familiar with?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'In addition to the language, what do overseas students need to become familiar with?',
                                            )
                                        "
                                    ></span>
                                    <input
                                        v-model="answers.q30"
                                        type="text"
                                        placeholder="answer"
                                        class="m-2 w-full rounded-xl border border-green-300 px-2 py-1 shadow focus:ring-2 focus:ring-green-500 sm:w-72"
                                    />
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
