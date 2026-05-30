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

const emit = defineEmits<{
    notesChange: [notes: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>];
}>();

// Listening Part 3 component
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

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

const textSegments = ref([
    { text: 'QUESTIONS 21-30', offset: 0 },
    { text: 'Choose the correct answer for each question.', offset: 16 },
    { text: 'Questions 21 and 22', offset: 61 },
    { text: 'Choose TWO letters A-E.', offset: 83 },
    { text: 'In which TWO ways is Dan financing his course?', offset: 109 },
    { text: 'A. He is receiving money from the government', offset: 161 },
    { text: 'B. His family are willing to help him', offset: 204 },
    { text: 'C. The college is giving him a small grant', offset: 242 },
    { text: 'D. His local council is supporting him for a limited period', offset: 284 },
    { text: 'E. A former employer is providing partial funding', offset: 348 },
    { text: 'Questions 23 and 24', offset: 399 },
    { text: 'Choose TWO letters A-E.', offset: 421 },
    { text: 'In which TWO reasons does Jeannie give for deciding to leave some college clubs?', offset: 447 },
    { text: 'A. She is not sufficiently challenged', offset: 531 },
    { text: 'B. The activity interferes with her studies', offset: 569 },
    { text: 'C. She does not have enough time', offset: 612 },
    { text: 'D. The activity is too demanding physically', offset: 645 },
    { text: 'E. She does not think she is any good at the activity', offset: 688 },
    { text: 'Questions 25-26', offset: 745 },
    { text: 'Choose the correct letter,', offset: 762 },
    { text: 'A', offset: 789 },
    { text: 'B', offset: 791 },
    { text: 'C', offset: 793 },
    { text: 'What does Dan say about the seminars on the course?', offset: 795 },
    { text: 'A. The other students do not give him a chance to speak', offset: 853 },
    { text: 'B. The seminars make him feel inferior to the other students', offset: 911 },
    { text: 'C. The preparation for seminars takes too much time', offset: 975 },
    { text: 'What does Jeannie say about the tutorials on the course?', offset: 1028 },
    { text: 'A. They are an inefficient way of providing guidance', offset: 1086 },
    { text: 'B. They are more challenging than she had expected', offset: 1140 },
    { text: 'C. They are helping her to develop her study skills', offset: 1192 },
    { text: 'Questions 27-30', offset: 1246 },
    { text: 'Complete the flow chart below.', offset: 1263 },
    { text: 'Write NO MORE THAN TWO WORDS OR A NUMBER.', offset: 1293 },
    { text: 'Advice on exam preparations', offset: 1338 },
    { text: 'Make sure you know the exam requirements', offset: 1367 },
    { text: 'Find some past papers', offset: 1408 },
    { text: 'Work out your', offset: 1431 },
    { text: 'for revision and write them on a card.', offset: 1444 },
    { text: 'Make a', offset: 1485 },
    { text: 'and keep it in view.', offset: 1493 },
    { text: 'Divide revision into', offset: 1515 },
    { text: 'for each day.', offset: 1536 },
    { text: 'Write one', offset: 1551 },
    { text: 'about each topic.', offset: 1561 },
    { text: 'Practice writing some exam answers', offset: 1579 },
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

// Initialize answers and load drafts
const loadSavedAnswers = async () => {
    const userPhone = props.phone || draftService.getUserPhone();
    const examId = props.examId || 'IELTS009';

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

const handleContentTextSelect = (event: MouseEvent) => {
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

watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

defineExpose({
    getAnswers,
    scrollToQuestion,
    scrollToHighlight,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col rounded-lg bg-white shadow-lg">
            <!-- Questions Header (Fixed) -->
            <div class="flex-shrink-0 border-b border-gray-200 bg-white p-2 sm:p-3 md:p-4 lg:p-6">
                <div class="flex flex-col gap-2 sm:gap-3">
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-600 shadow-md sm:h-7 sm:w-7 md:h-8 md:w-8"
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
                            <p
                                class="text-xs font-semibold tracking-wide text-gray-700 uppercase sm:text-sm"
                                :data-segment-text="'QUESTIONS 21-30'"
                                v-html="getHighlightedSegment('QUESTIONS 21-30')"
                            ></p>
                            <p
                                class="text-xs text-gray-600"
                                :data-segment-text="'Choose the correct answer for each question.'"
                                v-html="getHighlightedSegment('Choose the correct answer for each question.')"
                            ></p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-3 sm:space-y-4 md:space-y-6">
                        <!-- Questions 21-22 -->
                        <div
                            id="question-21-22"
                            class="rounded-xl border-2 border-green-300 bg-gradient-to-br from-green-50 to-emerald-50 p-3 shadow-lg sm:rounded-2xl sm:p-4 md:p-6 lg:p-8"
                        >
                            <div class="mb-3 sm:mb-4 md:mb-6">
                                <div class="mb-2 flex items-center space-x-1.5 sm:space-x-2 md:space-x-3">
                                    <div
                                        class="flex h-7 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg sm:h-8 sm:w-12 md:h-10 md:w-14"
                                    >
                                        <span class="text-xs font-bold text-white sm:text-sm md:text-base">21-22</span>
                                    </div>
                                    <h3
                                        class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-sm font-bold text-transparent sm:text-base md:text-lg lg:text-xl"
                                        :data-segment-text="'Questions 21 and 22'"
                                        v-html="getHighlightedSegment('Questions 21 and 22')"
                                    ></h3>
                                </div>
                                <div class="rounded-lg border border-green-200 bg-white p-3 shadow-sm sm:rounded-xl sm:p-4 md:p-6">
                                    <p
                                        class="mb-2 text-base leading-relaxed font-medium text-gray-800 sm:mb-3 sm:text-lg"
                                        :data-segment-text="'Choose TWO letters A-E.'"
                                        v-html="getHighlightedSegment('Choose TWO letters A-E.')"
                                    ></p>
                                    <p
                                        class="text-base leading-tight font-semibold text-gray-800 sm:text-lg"
                                        :data-segment-text="'In which TWO ways is Dan financing his course?'"
                                        v-html="getHighlightedSegment('In which TWO ways is Dan financing his course?')"
                                    ></p>
                                </div>
                            </div>

                            <div class="space-y-1.5 sm:space-y-2 md:space-y-3">
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q21_22', 'A')"
                                        @change="handleMultipleChoice('q21_22', 'A')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'A. He is receiving money from the government'"
                                        v-html="getHighlightedSegment('A. He is receiving money from the government')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q21_22', 'B')"
                                        @change="handleMultipleChoice('q21_22', 'B')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'B. His family are willing to help him'"
                                        v-html="getHighlightedSegment('B. His family are willing to help him')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q21_22', 'C')"
                                        @change="handleMultipleChoice('q21_22', 'C')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'C. The college is giving him a small grant'"
                                        v-html="getHighlightedSegment('C. The college is giving him a small grant')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q21_22', 'D')"
                                        @change="handleMultipleChoice('q21_22', 'D')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'D. His local council is supporting him for a limited period'"
                                        v-html="getHighlightedSegment('D. His local council is supporting him for a limited period')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q21_22', 'E')"
                                        @change="handleMultipleChoice('q21_22', 'E')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'E. A former employer is providing partial funding'"
                                        v-html="getHighlightedSegment('E. A former employer is providing partial funding')"
                                    ></span>
                                </label>
                            </div>

                            <div class="mt-3 rounded-lg border border-green-200 bg-white p-2 sm:mt-4 sm:p-3">
                                <p class="text-xs font-medium text-green-600 sm:text-sm md:text-base">
                                    Selected: {{ multipleAnswers.q21_22.length }}/2 answers
                                </p>
                            </div>
                        </div>

                        <!-- Questions 23-24 -->
                        <div
                            id="question-23-24"
                            class="rounded-xl border-2 border-green-300 bg-gradient-to-br from-green-50 to-emerald-50 p-3 shadow-lg sm:rounded-2xl sm:p-4 md:p-6 lg:p-8"
                        >
                            <div class="mb-3 sm:mb-4 md:mb-6">
                                <div class="mb-2 flex items-center space-x-1.5 sm:space-x-2 md:space-x-3">
                                    <div
                                        class="flex h-7 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg sm:h-8 sm:w-12 md:h-10 md:w-14"
                                    >
                                        <span class="text-xs font-bold text-white sm:text-sm md:text-base">23-24</span>
                                    </div>
                                    <h3
                                        class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-sm font-bold text-transparent sm:text-base md:text-lg lg:text-xl"
                                        :data-segment-text="'Questions 23 and 24'"
                                        v-html="getHighlightedSegment('Questions 23 and 24')"
                                    ></h3>
                                </div>
                                <div class="rounded-lg border border-green-200 bg-white p-3 shadow-sm sm:rounded-xl sm:p-4 md:p-6">
                                    <p
                                        class="mb-2 text-base leading-relaxed font-medium text-gray-800 sm:mb-3 sm:text-lg"
                                        :data-segment-text="'Choose TWO letters A-E.'"
                                        v-html="getHighlightedSegment('Choose TWO letters A-E.')"
                                    ></p>
                                    <p
                                        class="text-base leading-tight font-semibold text-gray-800 sm:text-lg"
                                        :data-segment-text="'In which TWO reasons does Jeannie give for deciding to leave some college clubs?'"
                                        v-html="
                                            getHighlightedSegment('In which TWO reasons does Jeannie give for deciding to leave some college clubs?')
                                        "
                                    ></p>
                                </div>
                            </div>

                            <div class="space-y-1.5 sm:space-y-2 md:space-y-3">
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q23_24', 'A')"
                                        @change="handleMultipleChoice('q23_24', 'A')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'A. She is not sufficiently challenged'"
                                        v-html="getHighlightedSegment('A. She is not sufficiently challenged')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q23_24', 'B')"
                                        @change="handleMultipleChoice('q23_24', 'B')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'B. The activity interferes with her studies'"
                                        v-html="getHighlightedSegment('B. The activity interferes with her studies')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q23_24', 'C')"
                                        @change="handleMultipleChoice('q23_24', 'C')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'C. She does not have enough time'"
                                        v-html="getHighlightedSegment('C. She does not have enough time')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q23_24', 'D')"
                                        @change="handleMultipleChoice('q23_24', 'D')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'D. The activity is too demanding physically'"
                                        v-html="getHighlightedSegment('D. The activity is too demanding physically')"
                                    ></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1.5 rounded-lg border border-green-200 bg-white p-2 transition-colors hover:bg-green-100 sm:gap-2 sm:rounded-xl sm:p-3 md:gap-3 md:p-4"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q23_24', 'E')"
                                        @change="handleMultipleChoice('q23_24', 'E')"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-green-300 text-green-600 focus:ring-green-500 sm:h-5 sm:w-5"
                                    />
                                    <span
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'E. She does not think she is any good at the activity'"
                                        v-html="getHighlightedSegment('E. She does not think she is any good at the activity')"
                                    ></span>
                                </label>
                            </div>

                            <div class="mt-3 rounded-lg border border-green-200 bg-white p-2 sm:mt-4 sm:p-3">
                                <p class="text-xs font-medium text-green-600 sm:text-sm md:text-base">
                                    Selected: {{ multipleAnswers.q23_24.length }}/2 answers
                                </p>
                            </div>
                        </div>
                        <!-- Questions 25-26 -->
                        <div
                            class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                        >
                            <div>
                                <div class="mb-4 flex items-center space-x-2">
                                    <h3
                                        class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent"
                                        :data-segment-text="'Questions 25-26'"
                                        v-html="getHighlightedSegment('Questions 25-26')"
                                    ></h3>
                                </div>
                                <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-lg">
                                    <span
                                        :data-segment-text="'Choose the correct letter,'"
                                        v-html="getHighlightedSegment('Choose the correct letter,')"
                                    ></span>
                                    <strong><span :data-segment-text="'A'" v-html="getHighlightedSegment('A')"></span></strong>
                                    <strong><span :data-segment-text="'B'" v-html="getHighlightedSegment('B')"></span></strong>
                                    <strong><span :data-segment-text="'C'" v-html="getHighlightedSegment('C')"></span></strong>
                                </p>
                            </div>
                            <div class="space-y-4">
                                <div class="space-y-4">
                                    <div
                                        id="question-25"
                                        class="rounded-lg border border-purple-200 bg-white p-3 shadow-md transition-all duration-200 hover:shadow-lg sm:rounded-xl sm:p-4 md:p-6"
                                    >
                                        <div class="flex items-start gap-2 sm:gap-3 md:gap-4">
                                            <div
                                                class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg sm:h-8 sm:w-8 md:h-10 md:w-10"
                                            >
                                                <span class="text-xs font-bold text-white sm:text-sm md:text-base">25</span>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <h4
                                                    class="mb-2 text-sm leading-tight font-semibold text-gray-800 sm:mb-3 sm:text-base md:mb-4 md:text-lg lg:text-xl"
                                                    :data-segment-text="'What does Dan say about the seminars on the course?'"
                                                    v-html="getHighlightedSegment('What does Dan say about the seminars on the course?')"
                                                ></h4>

                                                <div class="space-y-1.5 sm:space-y-2 md:space-y-3">
                                                    <label
                                                        class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:gap-2 sm:p-2 md:gap-3 md:p-3"
                                                    >
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q25"
                                                            value="A"
                                                            class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                                        />
                                                        <span
                                                            class="text-xs leading-relaxed font-medium text-gray-800 sm:text-sm md:text-base lg:text-lg"
                                                            :data-segment-text="'A. The other students do not give him a chance to speak'"
                                                            v-html="getHighlightedSegment('A. The other students do not give him a chance to speak')"
                                                        ></span>
                                                    </label>

                                                    <label
                                                        class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:gap-2 sm:p-2 md:gap-3 md:p-3"
                                                    >
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q25"
                                                            value="B"
                                                            class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                                        />
                                                        <span
                                                            class="text-xs leading-relaxed font-medium text-gray-800 sm:text-sm md:text-base lg:text-lg"
                                                            :data-segment-text="'B. The seminars make him feel inferior to the other students'"
                                                            v-html="
                                                                getHighlightedSegment('B. The seminars make him feel inferior to the other students')
                                                            "
                                                        ></span>
                                                    </label>

                                                    <label
                                                        class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:gap-2 sm:p-2 md:gap-3 md:p-3"
                                                    >
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q25"
                                                            value="C"
                                                            class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                                        />
                                                        <span
                                                            class="text-xs leading-relaxed font-medium text-gray-800 sm:text-sm md:text-base lg:text-lg"
                                                            :data-segment-text="'C. The preparation for seminars takes too much time'"
                                                            v-html="getHighlightedSegment('C. The preparation for seminars takes too much time')"
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 26 -->
                                    <div
                                        id="question-26"
                                        class="mt-4 rounded-lg border border-purple-200 bg-white p-3 shadow-md transition-all duration-200 hover:shadow-lg sm:rounded-xl sm:p-4 md:p-6"
                                    >
                                        <div class="flex items-start gap-2 sm:gap-3 md:gap-4">
                                            <div
                                                class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg sm:h-8 sm:w-8 md:h-10 md:w-10"
                                            >
                                                <span class="text-xs font-bold text-white sm:text-sm md:text-base">26</span>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <h4
                                                    class="mb-2 text-sm leading-tight font-semibold text-gray-800 sm:mb-3 sm:text-base md:mb-4 md:text-lg lg:text-xl"
                                                    :data-segment-text="'What does Jeannie say about the tutorials on the course?'"
                                                    v-html="getHighlightedSegment('What does Jeannie say about the tutorials on the course?')"
                                                ></h4>

                                                <div class="space-y-1.5 sm:space-y-2 md:space-y-3">
                                                    <label
                                                        class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:gap-2 sm:p-2 md:gap-3 md:p-3"
                                                    >
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q26"
                                                            value="A"
                                                            class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                                        />
                                                        <span
                                                            class="text-xs leading-relaxed font-medium text-gray-800 sm:text-sm md:text-base lg:text-lg"
                                                            :data-segment-text="'A. They are an inefficient way of providing guidance'"
                                                            v-html="getHighlightedSegment('A. They are an inefficient way of providing guidance')"
                                                        ></span>
                                                    </label>

                                                    <label
                                                        class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:gap-2 sm:p-2 md:gap-3 md:p-3"
                                                    >
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q26"
                                                            value="B"
                                                            class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                                        />
                                                        <span
                                                            class="text-xs leading-relaxed font-medium text-gray-800 sm:text-sm md:text-base lg:text-lg"
                                                            :data-segment-text="'B. They are more challenging than she had expected'"
                                                            v-html="getHighlightedSegment('B. They are more challenging than she had expected')"
                                                        ></span>
                                                    </label>

                                                    <label
                                                        class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:gap-2 sm:p-2 md:gap-3 md:p-3"
                                                    >
                                                        <input
                                                            type="radio"
                                                            v-model="answers.q26"
                                                            value="C"
                                                            class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                                        />
                                                        <span
                                                            class="text-xs leading-relaxed font-medium text-gray-800 sm:text-sm md:text-base lg:text-lg"
                                                            :data-segment-text="'C. They are helping her to develop her study skills'"
                                                            v-html="getHighlightedSegment('C. They are helping her to develop her study skills')"
                                                        ></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Questions 27-30 -->
                        <div class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-8 shadow-lg">
                            <div class="mb-8">
                                <div class="mb-6 flex items-center space-x-4">
                                    <div>
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-xl font-bold text-transparent"
                                            :data-segment-text="'Questions 27-30'"
                                            v-html="getHighlightedSegment('Questions 27-30')"
                                        ></h3>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-green-200 bg-white p-6 shadow-sm">
                                    <p
                                        class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                        :data-segment-text="'Complete the flow chart below.'"
                                        v-html="getHighlightedSegment('Complete the flow chart below.')"
                                    ></p>
                                    <p class="text-base sm:text-lg">
                                        <span
                                            :data-segment-text="'Write NO MORE THAN TWO WORDS OR A NUMBER.'"
                                            v-html="getHighlightedSegment('Write NO MORE THAN TWO WORDS OR A NUMBER.')"
                                        ></span>
                                    </p>
                                    <h1
                                        class="text-center text-[24px] font-bold text-green-600"
                                        :data-segment-text="'Advice on exam preparations'"
                                        v-html="getHighlightedSegment('Advice on exam preparations')"
                                    ></h1>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="text-center text-base leading-relaxed text-gray-800 sm:text-lg">
                                    <p
                                        :data-segment-text="'Make sure you know the exam requirements'"
                                        v-html="getHighlightedSegment('Make sure you know the exam requirements')"
                                    ></p>
                                    <!-- Down Arrow -->
                                    <div class="flex justify-center">
                                        <div class="text-[12px]">⬇️</div>
                                    </div>
                                    <p :data-segment-text="'Find some past papers'" v-html="getHighlightedSegment('Find some past papers')"></p>
                                    <!-- Down Arrow -->
                                    <div class="flex justify-center">
                                        <div class="text-[12px]">⬇️</div>
                                    </div>

                                    <!-- Step 27 -->
                                    <p id="question-27" class="flex flex-col items-center">
                                        <span class="my-2 inline-flex items-center space-x-2">
                                            <span :data-segment-text="'Work out your'" v-html="getHighlightedSegment('Work out your')"></span>
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 text-base font-bold text-white shadow-md"
                                                >27</span
                                            >

                                            <input
                                                v-model="answers.q27"
                                                type="text"
                                                class="w-36 rounded-full border border-green-300 bg-emerald-50 px-3 py-1 text-center text-sm font-medium transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none"
                                                placeholder="____"
                                            /><span
                                                :data-segment-text="'for revision and write them on a card.'"
                                                v-html="getHighlightedSegment('for revision and write them on a card.')"
                                            ></span>
                                        </span>
                                    </p>

                                    <!-- Down Arrow -->
                                    <div class="flex justify-center">
                                        <div class="text-[12px]">⬇️</div>
                                    </div>

                                    <!-- Step 28 -->
                                    <p id="question-28" class="flex flex-col items-center">
                                        <span class="my-2 inline-flex items-center space-x-2">
                                            <span :data-segment-text="'Make a'" v-html="getHighlightedSegment('Make a')"></span>
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 text-base font-bold text-white shadow-md"
                                                >28</span
                                            >

                                            <input
                                                v-model="answers.q28"
                                                type="text"
                                                class="w-36 rounded-full border border-green-300 bg-emerald-50 px-3 py-1 text-center text-sm font-medium transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none"
                                                placeholder="____"
                                            /><span
                                                :data-segment-text="'and keep it in view.'"
                                                v-html="getHighlightedSegment('and keep it in view.')"
                                            ></span>
                                        </span>
                                    </p>

                                    <!-- Down Arrow -->
                                    <div class="flex justify-center">
                                        <div class="text-[12px]">⬇️</div>
                                    </div>

                                    <!-- Step 29 -->
                                    <p id="question-29" class="flex flex-col items-center">
                                        <span class="my-2 inline-flex items-center space-x-2">
                                            <span
                                                :data-segment-text="'Divide revision into'"
                                                v-html="getHighlightedSegment('Divide revision into')"
                                            ></span>
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 text-base font-bold text-white shadow-md"
                                                >29</span
                                            >
                                            <input
                                                v-model="answers.q29"
                                                type="text"
                                                class="w-36 rounded-full border border-green-300 bg-emerald-50 px-3 py-1 text-center text-sm font-medium transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none"
                                                placeholder="____"
                                            /><span :data-segment-text="'for each day.'" v-html="getHighlightedSegment('for each day.')"></span>
                                        </span>
                                    </p>

                                    <!-- Down Arrow -->
                                    <div class="flex justify-center">
                                        <div class="text-[12px]">⬇️</div>
                                    </div>

                                    <!-- Step 30 -->
                                    <p id="question-30" class="flex flex-col items-center">
                                        <span class="my-2 inline-flex items-center space-x-2">
                                            <span :data-segment-text="'Write one'" v-html="getHighlightedSegment('Write one')"></span>
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600 text-base font-bold text-white shadow-md"
                                                >30</span
                                            >
                                            <input
                                                v-model="answers.q30"
                                                type="text"
                                                class="w-36 rounded-full border border-green-300 bg-emerald-50 px-3 py-1 text-center text-sm font-medium transition-colors focus:border-emerald-500 focus:bg-white focus:outline-none"
                                                placeholder="____"
                                            /><span
                                                :data-segment-text="'about each topic.'"
                                                v-html="getHighlightedSegment('about each topic.')"
                                            ></span>
                                        </span>
                                    </p>
                                    <!-- Down Arrow -->
                                    <div class="flex justify-center">
                                        <div class="text-[12px]">⬇️</div>
                                    </div>
                                    <p
                                        :data-segment-text="'Practice writing some exam answers'"
                                        v-html="getHighlightedSegment('Practice writing some exam answers')"
                                    ></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
