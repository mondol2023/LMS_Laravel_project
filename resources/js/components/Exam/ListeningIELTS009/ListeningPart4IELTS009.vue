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
    q31: string;
    q32: string;
    q33: string;
    q34: string;
    q35: string;
    q36: string;
    q37: string;
    q38: string;
    q39: string;
    q40: string;
}

interface MultipleAnswersRecord {
    q31_32: string[];
    q33_34: string[];
}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

const emit = defineEmits<{
    notesChange: [notes: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>];
}>();

// Listening Part 4 component
const answers = ref<AnswersRecord>({
    q31: '',
    q32: '',
    q33: '',
    q34: '',
    q35: '',
    q36: '',
    q37: '',
    q38: '',
    q39: '',
    q40: '',
});

// Multiple choice answers for grouped questions
const multipleAnswers = ref<MultipleAnswersRecord>({
    q31_32: [],
    q33_34: [],
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
    { text: 'QUESTIONS 31-40', offset: 0 },
    { text: 'Complete the notes below.', offset: 16 },
    { text: 'Australian Aboriginal Rock Paintings', offset: 42 },
    { text: 'Which painting styles have the following features?', offset: 78 },
    { text: 'Write the correct letter, A, B, C next to questions 31-36.', offset: 128 },
    { text: 'Features', offset: 185 },
    { text: 'figures revealing bones', offset: 194 },
    { text: 'rounded figures', offset: 218 },
    { text: 'figures with parts missing', offset: 234 },
    { text: 'figures smaller than life size', offset: 260 },
    { text: 'sea creatures', offset: 291 },
    { text: 'plants', offset: 305 },
    { text: 'RAINBOW SERPENT PROJECT', offset: 312 },
    { text: 'Aim of project: to identify the', offset: 336 },
    { text: 'used as the basis for the Rainbow Serpent', offset: 368 },
    { text: 'Yam Period:', offset: 412 },
    { text: 'Environmental changes led to higher', offset: 424 },
    { text: 'Traditional activities were affected especially', offset: 461 },
    { text: 'Rainbow Serpent Image', offset: 509 },
    { text: 'Similar to a sea horse', offset: 531 },
    { text: 'Unusual because it appeared in inland areas', offset: 555 },
    { text: 'Symbolises', offset: 601 },
    { text: 'in Aboriginal culture', offset: 612 },
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
    const examId = props.examId || 'IELTS009';

    // Initialize auto-saver
    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part4');

    // Load saved drafts
    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part4) {
            // Load single answers
            Object.keys(answers.value).forEach((key) => {
                if (draftsResponse.data.part4[key]) {
                    answers.value[key as keyof typeof answers.value] = draftsResponse.data.part4[key];
                }
            });
            // Load multiple answers (deserialize from JSON strings)
            Object.keys(multipleAnswers.value).forEach((key) => {
                if (draftsResponse.data.part4[key]) {
                    try {
                        const parsedValue = JSON.parse(draftsResponse.data.part4[key]);
                        multipleAnswers.value[key as keyof typeof multipleAnswers.value] = Array.isArray(parsedValue) ? parsedValue : [];
                    } catch (e) {
                        console.warn(`Failed to parse ${key} data:`, e);
                        multipleAnswers.value[key as keyof typeof multipleAnswers.value] = [];
                    }
                }
            });
            console.log('Loaded Listening Part 4 drafts');
        }
    } catch (error) {
        console.error('Failed to load Listening Part 4 drafts:', error);
    }
};

// Save answers to drafts
const saveAnswers = () => {
    if (autoSaver.value) {
        const allAnswers = { ...answers.value };

        Object.entries(multipleAnswers.value).forEach(([key, value]) => {
            allAnswers[key] = JSON.stringify(value);
        });

        autoSaver.value.saveBatch(allAnswers);
    }
};

// Expose methods for parent component
const getAnswers = () => {
    const allAnswers = { ...answers.value };

    if (multipleAnswers.value.q31_32?.length > 0) {
        allAnswers.q31 = multipleAnswers.value.q31_32[0] || '';
        allAnswers.q32 = multipleAnswers.value.q31_32[1] || '';
    }

    if (multipleAnswers.value.q33_34?.length > 0) {
        allAnswers.q33 = multipleAnswers.value.q33_34[0] || '';
        allAnswers.q34 = multipleAnswers.value.q33_34[1] || '';
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

ref<string | null>(null);

const handleMultipleChoice = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        currentAnswers.splice(index, 1);
    } else if (currentAnswers.length < 2) {
        currentAnswers.push(option);
    }
};

const isSelected = (questionGroup: string, option: string) => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    let targetId = `question-${questionNumber}`;
    if (questionNumber >= 31 && questionNumber <= 32) {
        targetId = 'question-31-32';
    } else if (questionNumber >= 33 && questionNumber <= 34) {
        targetId = 'question-33-34';
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
        <div class="flex min-h-screen w-full flex-col rounded-lg bg-white shadow-lg">
            <div class="flex-shrink-0 border-b border-gray-200 bg-white p-2 sm:p-3 md:p-4 lg:p-6">
                <div class="flex flex-col gap-2 sm:gap-3">
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-r from-orange-500 to-red-600 sm:h-7 sm:w-7 md:h-8 md:w-8"
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
                                :data-segment-text="'QUESTIONS 31-40'"
                                v-html="getHighlightedSegment('QUESTIONS 31-40')"
                            ></p>
                            <p
                                class="text-xs text-gray-600"
                                :data-segment-text="'Complete the notes below.'"
                                v-html="getHighlightedSegment('Complete the notes below.')"
                            ></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div
                        id="question-31-40"
                        class="rounded-xl border-2 border-orange-300 bg-gradient-to-br from-orange-50 to-red-50 p-4 shadow-lg transition-all duration-200 hover:shadow-xl sm:rounded-2xl sm:p-6 md:p-8"
                    >
                        <h1
                            class="text-center text-xl font-bold"
                            :data-segment-text="'Australian Aboriginal Rock Paintings'"
                            v-html="getHighlightedSegment('Australian Aboriginal Rock Paintings')"
                        ></h1>
                        <p
                            class="mt-4 text-base leading-tight text-gray-800 sm:text-lg"
                            :data-segment-text="'Which painting styles have the following features?'"
                            v-html="getHighlightedSegment('Which painting styles have the following features?')"
                        ></p>
                        <p class="mb-3 text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                            <span
                                :data-segment-text="'Write the correct letter, A, B, C next to questions 31-36.'"
                                v-html="getHighlightedSegment('Write the correct letter, A, B, C next to questions 31-36.')"
                            ></span>
                        </p>
                        <div class="rounded-xl border border-orange-200 bg-white p-4 shadow-sm sm:p-5 md:p-6">
                            <h1 class="text-center text-xl font-bold">Painting Styles</h1>
                            <div class="flex items-center justify-center">
                                <div class="mt-4 space-y-3 text-gray-800 sm:text-lg">
                                    <div class="flex gap-4 px-4">
                                        <strong class="text-gray-700">A</strong>
                                        <span>Dynamic</span>
                                    </div>
                                    <div class="flex gap-4 px-4">
                                        <strong class="text-gray-700">B</strong>
                                        <span>Yam</span>
                                    </div>
                                    <div class="flex gap-4 px-4">
                                        <strong class="text-gray-700">C</strong>
                                        <span>Modern</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2 sm:space-y-3">
                            <h1 :data-segment-text="'Features'" v-html="getHighlightedSegment('Features')"></h1>
                            <div
                                id="question-31"
                                class="rounded-xl border border-orange-300 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-lg"
                                    >
                                        <span class="text-sm font-bold text-white">31</span>
                                    </div>
                                    <div class="grid grid-cols-12 gap-4">
                                        <div class="col-span-3">
                                            <p
                                                class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                :data-segment-text="'figures revealing bones'"
                                                v-html="getHighlightedSegment('figures revealing bones')"
                                            ></p>
                                        </div>
                                        <div class="col-span-9">
                                            <select
                                                v-model="answers.q31"
                                                class="w-full rounded-full border-2 border-orange-300 bg-gradient-to-br from-orange-50 to-red-50 px-3 py-2 text-sm transition-colors focus:border-orange-300 focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select...</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                id="question-32"
                                class="mt-4 rounded-xl border border-orange-300 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-lg"
                                    >
                                        <span class="text-sm font-bold text-white">32</span>
                                    </div>
                                    <div class="grid grid-cols-12 gap-4">
                                        <div class="col-span-3">
                                            <p
                                                class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                :data-segment-text="'rounded figures'"
                                                v-html="getHighlightedSegment('rounded figures')"
                                            ></p>
                                        </div>
                                        <div class="col-span-9">
                                            <select
                                                v-model="answers.q32"
                                                class="w-full rounded-full border-2 border-orange-300 bg-gradient-to-br from-orange-50 to-red-50 px-3 py-2 text-sm transition-colors focus:border-orange-300 focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select...</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                id="question-33"
                                class="mt-4 rounded-xl border border-orange-300 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-lg"
                                    >
                                        <span class="text-sm font-bold text-white">33</span>
                                    </div>
                                    <div class="grid grid-cols-12 gap-4">
                                        <div class="col-span-3">
                                            <p
                                                class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                :data-segment-text="'figures with parts missing'"
                                                v-html="getHighlightedSegment('figures with parts missing')"
                                            ></p>
                                        </div>
                                        <div class="col-span-9">
                                            <select
                                                v-model="answers.q33"
                                                class="w-full rounded-full border-2 border-orange-300 bg-gradient-to-br from-orange-50 to-red-50 px-3 py-2 text-sm transition-colors focus:border-orange-300 focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select...</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                id="question-34"
                                class="mt-4 rounded-xl border border-orange-300 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-lg"
                                    >
                                        <span class="text-sm font-bold text-white">34</span>
                                    </div>
                                    <div class="grid grid-cols-12 gap-4">
                                        <div class="col-span-3">
                                            <p
                                                class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                :data-segment-text="'figures smaller than life size'"
                                                v-html="getHighlightedSegment('figures smaller than life size')"
                                            ></p>
                                        </div>
                                        <div class="col-span-9">
                                            <select
                                                v-model="answers.q34"
                                                class="w-full rounded-full border-2 border-orange-300 bg-gradient-to-br from-orange-50 to-red-50 px-3 py-2 text-sm transition-colors focus:border-orange-300 focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select...</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                id="question-35"
                                class="mt-4 rounded-xl border border-orange-300 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-lg"
                                    >
                                        <span class="text-sm font-bold text-white">35</span>
                                    </div>
                                    <div class="grid grid-cols-12 gap-4">
                                        <div class="col-span-3">
                                            <p
                                                class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                :data-segment-text="'sea creatures'"
                                                v-html="getHighlightedSegment('sea creatures')"
                                            ></p>
                                        </div>
                                        <div class="col-span-9">
                                            <select
                                                v-model="answers.q35"
                                                class="w-full rounded-full border-2 border-orange-300 bg-gradient-to-br from-orange-50 to-red-50 px-3 py-2 text-sm transition-colors focus:border-orange-300 focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select...</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                id="question-36"
                                class="mt-4 rounded-xl border border-orange-300 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-lg"
                                    >
                                        <span class="text-sm font-bold text-white">36</span>
                                    </div>
                                    <div class="grid grid-cols-12 gap-4">
                                        <div class="col-span-3">
                                            <p
                                                class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                :data-segment-text="'plants'"
                                                v-html="getHighlightedSegment('plants')"
                                            ></p>
                                        </div>
                                        <div class="col-span-9">
                                            <select
                                                v-model="answers.q36"
                                                class="w-full rounded-full border-2 border-orange-300 bg-gradient-to-br from-orange-50 to-red-50 px-3 py-2 text-sm transition-colors focus:border-orange-300 focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select...</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4 rounded-xl border border-orange-200 bg-white p-4 shadow-lg sm:p-6 md:p-8">
                                <p
                                    class="text-center text-base font-semibold text-gray-800 sm:text-lg"
                                    :data-segment-text="'RAINBOW SERPENT PROJECT'"
                                    v-html="getHighlightedSegment('RAINBOW SERPENT PROJECT')"
                                ></p>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-medium text-gray-700"
                                        :data-segment-text="'Aim of project: to identify the'"
                                        v-html="getHighlightedSegment('Aim of project: to identify the')"
                                    ></span>
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-md"
                                    >
                                        <span class="text-base font-bold text-white">37</span>
                                    </div>
                                    <input
                                        v-model="answers.q37"
                                        type="text"
                                        class="min-w-24 rounded-lg border-2 border-orange-300 bg-white px-3 py-2 text-center focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none"
                                        placeholder="..............."
                                    />
                                    <span
                                        class="font-medium text-gray-700"
                                        :data-segment-text="'used as the basis for the Rainbow Serpent'"
                                        v-html="getHighlightedSegment('used as the basis for the Rainbow Serpent')"
                                    ></span>
                                </div>

                                <p :data-segment-text="'Yam Period:'" v-html="getHighlightedSegment('Yam Period:')"></p>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="mt-1 inline-block h-1 w-1 rounded-full bg-gray-700"></span>
                                        <span
                                            class="font-medium text-gray-700"
                                            :data-segment-text="'Environmental changes led to higher'"
                                            v-html="getHighlightedSegment('Environmental changes led to higher')"
                                        ></span>
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-md"
                                        >
                                            <span class="text-base font-bold text-white">38</span>
                                        </div>
                                        <input
                                            v-model="answers.q38"
                                            type="text"
                                            class="min-w-24 rounded-lg border-2 border-orange-300 bg-white px-3 py-2 text-center focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none"
                                            placeholder="..............."
                                        />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="mt-1 inline-block h-1 w-1 rounded-full bg-gray-700"></span>
                                        <span
                                            class="font-medium text-gray-700"
                                            :data-segment-text="'Traditional activities were affected especially'"
                                            v-html="getHighlightedSegment('Traditional activities were affected especially')"
                                        ></span>
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-md"
                                        >
                                            <span class="text-base font-bold text-white">39</span>
                                        </div>
                                        <input
                                            v-model="answers.q39"
                                            type="text"
                                            class="min-w-24 rounded-lg border-2 border-orange-300 bg-white px-3 py-2 text-center focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none"
                                            placeholder="..............."
                                        />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <p
                                        class="text-base font-semibold text-gray-800 sm:text-lg"
                                        :data-segment-text="'Rainbow Serpent Image'"
                                        v-html="getHighlightedSegment('Rainbow Serpent Image')"
                                    ></p>
                                    <div class="ml-4 space-y-2">
                                        <div class="flex items-start gap-2">
                                            <span class="mt-1 inline-block h-1 w-1 rounded-full bg-gray-700"></span>
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'Similar to a sea horse'"
                                                v-html="getHighlightedSegment('Similar to a sea horse')"
                                            ></span>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="mt-1 inline-block h-1 w-1 rounded-full bg-gray-700"></span>
                                            <span
                                                class="text-gray-700"
                                                :data-segment-text="'Unusual because it appeared in inland areas'"
                                                v-html="getHighlightedSegment('Unusual because it appeared in inland areas')"
                                            ></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="mt-1 inline-block h-1 w-1 rounded-full bg-gray-700"></span>
                                            <span
                                                class="font-medium text-gray-700"
                                                :data-segment-text="'Symbolises'"
                                                v-html="getHighlightedSegment('Symbolises')"
                                            ></span>
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-orange-500 to-red-600 shadow-md"
                                            >
                                                <span class="text-base font-bold text-white">40</span>
                                            </div>
                                            <input
                                                v-model="answers.q40"
                                                type="text"
                                                class="min-w-24 rounded-lg border-2 border-orange-300 bg-white px-3 py-2 text-center focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none"
                                                placeholder="..............."
                                            />
                                            <span
                                                class="font-medium text-gray-700"
                                                :data-segment-text="'in Aboriginal culture'"
                                                v-html="getHighlightedSegment('in Aboriginal culture')"
                                            ></span>
                                        </div>
                                    </div>
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
                        :class="[color.class, 'h-10 w-10 rounded-md border border-gray-300 transition-transform hover:scale-110']"
                        :title="`Highlight ${color.name}`"
                    ></button>
                    <button
                        @click="openNoteInput"
                        class="flex h-10 w-10 items-center justify-center rounded-md border border-gray-300 bg-blue-50 transition-all hover:scale-110 hover:bg-blue-100"
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

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #f97316, #ea580c);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ea580c, #c2410c);
}
</style>
