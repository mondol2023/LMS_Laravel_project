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

const emit = defineEmits<{
    notesChange: [notes: Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>];
}>();

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

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    { text: 'QUESTIONS 11-20', offset: 0 },
    { text: 'Choose the correct letter, A, B or C.', offset: 16 },
    { text: 'Improvements to Red Hill Suburb', offset: 54 },
    { text: 'Community groups are mainly concerned about', offset: 88 },
    { text: 'A. pedestrian safety', offset: 135 },
    { text: 'B. traffic jams', offset: 157 },
    { text: 'C.increased pollution', offset: 174 },
    { text: 'It has been decided that the overhead power lines will be', offset: 196 },
    { text: 'A. extended', offset: 256 },
    { text: 'B. buried', offset: 268 },
    { text: 'C. repaired', offset: 278 },
    { text: 'The expenses related to the power lines will be paid by', offset: 290 },
    { text: 'A. the council', offset: 348 },
    { text: 'B. the power company', offset: 364 },
    { text: 'C. local businesses', offset: 386 },
    { text: 'Questions 14-20', offset: 408 },
    { text: 'Label the map below.', offset: 425 },
    { text: 'Write the correct letter A-H next to questions 14-20.', offset: 447 },
    { text: 'Trees', offset: 504 },
    { text: 'Wider footpaths', offset: 510 },
    { text: 'Coloured road surface', offset: 527 },
    { text: 'New sign', offset: 549 },
    { text: 'Traffic lights', offset: 558 },
    { text: 'Artwork', offset: 573 },
    { text: 'Children’s playground', offset: 581 },
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
                            <p
                                class="text-xs font-semibold tracking-wide text-gray-700 uppercase sm:text-sm"
                                :data-segment-text="'QUESTIONS 11-20'"
                                v-html="getHighlightedSegment('QUESTIONS 11-20')"
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

            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-3 sm:space-y-4 md:space-y-6">
                        <div>
                            <h3
                                class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-xl font-bold text-transparent"
                                :data-segment-text="'Questions 11-13'"
                                v-html="getHighlightedSegment('Questions 11-13')"
                            ></h3>
                            <p class="text-base text-gray-900 sm:text-lg">
                                <span
                                    :data-segment-text="'Choose the correct letter'"
                                    v-html="getHighlightedSegment('Choose the correct letter')"
                                ></span
                                >,
                                <strong class="text-purple-600"
                                    ><span :data-segment-text="'A, B or C'" v-html="getHighlightedSegment('A, B or C')"></span></strong
                                ><span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                            </p>
                            <h3
                                class="text-center text-xl font-bold"
                                :data-segment-text="'Improvements to Red Hill Suburb'"
                                v-html="getHighlightedSegment('Improvements to Red Hill Suburb')"
                            ></h3>
                        </div>
                        <div
                            id="question-11"
                            class="rounded-lg border border-purple-200 bg-white p-3 shadow-md transition-all duration-200 hover:shadow-lg sm:rounded-xl sm:p-4 md:p-6"
                        >
                            <div class="flex items-start gap-2 sm:gap-3 md:gap-4">
                                <div
                                    class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-500 to-pink-600 shadow-lg sm:h-8 sm:w-8 md:h-10 md:w-10"
                                >
                                    <span class="text-xs font-bold text-white sm:text-sm md:text-base">11</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4
                                        class="mb-2 text-base leading-tight font-semibold text-gray-800 sm:mb-3 sm:text-lg md:mb-4"
                                        :data-segment-text="'Community groups are mainly concerned about'"
                                        v-html="getHighlightedSegment('Community groups are mainly concerned about')"
                                    ></h4>

                                    <div class="space-y-1.5 sm:space-y-2 md:space-y-3">
                                        <label
                                            class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:p-2 md:p-3"
                                        >
                                            <input
                                                type="radio"
                                                v-model="answers.q11"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                            />
                                            <span class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                                <strong
                                                    :data-segment-text="'A. pedestrian safety'"
                                                    v-html="getHighlightedSegment('A. pedestrian safety')"
                                                ></strong>
                                            </span>
                                        </label>

                                        <label
                                            class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:p-2 md:p-3"
                                        >
                                            <input
                                                type="radio"
                                                v-model="answers.q11"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                            />
                                            <span class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                                <strong
                                                    :data-segment-text="'B. traffic jams'"
                                                    v-html="getHighlightedSegment('B. traffic jams')"
                                                ></strong>
                                            </span>
                                        </label>

                                        <label
                                            class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:p-2 md:p-3"
                                        >
                                            <input
                                                type="radio"
                                                v-model="answers.q11"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                            />
                                            <span class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                                <strong
                                                    :data-segment-text="'C.increased pollution'"
                                                    v-html="getHighlightedSegment('C.increased pollution')"
                                                ></strong>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            id="question-12"
                            class="rounded-lg border border-purple-200 bg-white p-3 shadow-md transition-all duration-200 hover:shadow-lg sm:rounded-xl sm:p-4 md:p-6"
                        >
                            <div class="flex items-start gap-2 sm:gap-3 md:gap-4">
                                <div
                                    class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-500 to-pink-600 shadow-lg sm:h-8 sm:w-8 md:h-10 md:w-10"
                                >
                                    <span class="text-xs font-bold text-white sm:text-sm md:text-base">12</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4
                                        class="mb-2 text-base leading-tight font-semibold text-gray-800 sm:mb-3 sm:text-lg md:mb-4"
                                        :data-segment-text="'It has been decided that the overhead power lines will be'"
                                        v-html="getHighlightedSegment('It has been decided that the overhead power lines will be')"
                                    ></h4>
                                    <div class="space-y-1.5 sm:space-y-2 md:space-y-3">
                                        <label
                                            class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:p-2 md:p-3"
                                        >
                                            <input
                                                type="radio"
                                                v-model="answers.q12"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                            />
                                            <span
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'A. extended'"
                                                v-html="getHighlightedSegment('A. extended')"
                                            >
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:p-2 md:p-3"
                                        >
                                            <input
                                                type="radio"
                                                v-model="answers.q12"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                            />
                                            <span
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'B. buried'"
                                                v-html="getHighlightedSegment('B. buried')"
                                            >
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:p-2 md:p-3"
                                        >
                                            <input
                                                type="radio"
                                                v-model="answers.q12"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                            />
                                            <span
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'C. repaired'"
                                                v-html="getHighlightedSegment('C. repaired')"
                                            >
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            id="question-13"
                            class="rounded-lg border border-purple-200 bg-white p-3 shadow-md transition-all duration-200 hover:shadow-lg sm:rounded-xl sm:p-4 md:p-6"
                        >
                            <div class="flex items-start gap-2 sm:gap-3 md:gap-4">
                                <div
                                    class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-purple-500 to-pink-600 shadow-lg sm:h-8 sm:w-8 md:h-10 md:w-10"
                                >
                                    <span class="text-xs font-bold text-white sm:text-sm md:text-base">13</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4
                                        class="mb-2 text-base leading-tight font-semibold text-gray-800 sm:mb-3 sm:text-lg md:mb-4"
                                        :data-segment-text="'The expenses related to the power lines will be paid by'"
                                        v-html="getHighlightedSegment('The expenses related to the power lines will be paid by')"
                                    ></h4>
                                    <div class="space-y-1.5 sm:space-y-2 md:space-y-3">
                                        <label
                                            class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:p-2 md:p-3"
                                        >
                                            <input
                                                type="radio"
                                                v-model="answers.q13"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                            />
                                            <span
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'A. the council'"
                                                v-html="getHighlightedSegment('A. the council')"
                                            >
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:p-2 md:p-3"
                                        >
                                            <input
                                                type="radio"
                                                v-model="answers.q13"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                            />
                                            <span
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'B. the power company'"
                                                v-html="getHighlightedSegment('B. the power company')"
                                            >
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1.5 rounded-lg p-1.5 transition-colors hover:bg-purple-50 sm:p-2 md:p-3"
                                        >
                                            <input
                                                type="radio"
                                                v-model="answers.q13"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 focus:ring-purple-500 sm:h-5 sm:w-5"
                                            />
                                            <span
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'C. local businesses'"
                                                v-html="getHighlightedSegment('C. local businesses')"
                                            >
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 14-20 -->
                        <div class="rounded-2xl border border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 p-8 shadow-lg">
                            <div class="mb-8">
                                <div class="mb-6 flex items-center space-x-4">
                                    <div>
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-xl font-bold text-transparent"
                                            :data-segment-text="'Questions 14-20'"
                                            v-html="getHighlightedSegment('Questions 14-20')"
                                        ></h3>
                                    </div>
                                </div>
                                <p
                                    class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                    :data-segment-text="'Label the map below.'"
                                    v-html="getHighlightedSegment('Label the map below.')"
                                ></p>
                                <p class="text-base sm:text-lg">
                                    <span
                                        :data-segment-text="'Write the correct letter'"
                                        v-html="getHighlightedSegment('Write the correct letter')"
                                    ></span>
                                    <strong :data-segment-text="'A-H'" v-html="getHighlightedSegment('A-H')"></strong>
                                    <span :data-segment-text="'next to questions'" v-html="getHighlightedSegment('next to questions')"></span>
                                    <strong :data-segment-text="'14-20'" v-html="getHighlightedSegment('14-20')"></strong
                                    ><span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                                </p>
                            </div>

                            <div class="space-y-6">
                                <div class="flex items-center justify-center">
                                    <img src="/images/listening/IELTS009.png" alt="Map of Red Hill" class="max-w-full rounded-lg shadow-md" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div id="question-14" class="rounded-xl border border-purple-200 bg-white p-4 shadow-md">
                                        <div class="mb-2 flex items-center justify-start gap-2">
                                            <p class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 shadow-md">
                                                <span class="text-base font-bold text-white">14</span>
                                            </p>
                                            <p
                                                class="mb-2 text-base font-semibold text-gray-800 sm:text-lg"
                                                :data-segment-text="'Trees'"
                                                v-html="getHighlightedSegment('Trees')"
                                            ></p>
                                        </div>
                                        <select
                                            v-model="answers.q14"
                                            class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-sm transition-colors focus:border-purple-500 focus:bg-white focus:outline-none"
                                        >
                                            <option value="">Select...</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                    </div>
                                    <div id="question-15" class="rounded-xl border border-purple-200 bg-white p-4 shadow-md">
                                        <div class="mb-2 flex items-center justify-start gap-2">
                                            <p class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 shadow-md">
                                                <span class="text-base font-bold text-white">15</span>
                                            </p>
                                            <p
                                                class="mb-2 text-base font-semibold text-gray-800 sm:text-lg"
                                                :data-segment-text="'Wider footpaths'"
                                                v-html="getHighlightedSegment('Wider footpaths')"
                                            ></p>
                                        </div>
                                        <select
                                            v-model="answers.q15"
                                            class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-sm transition-colors focus:border-purple-500 focus:bg-white focus:outline-none"
                                        >
                                            <option value="">Select...</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                    </div>
                                    <div id="question-16" class="rounded-xl border border-purple-200 bg-white p-4 shadow-md">
                                        <div class="mb-2 flex items-center justify-start gap-2">
                                            <p class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 shadow-md">
                                                <span class="text-base font-bold text-white">16</span>
                                            </p>
                                            <p
                                                class="mb-2 text-base font-semibold text-gray-800 sm:text-lg"
                                                :data-segment-text="'Coloured road surface'"
                                                v-html="getHighlightedSegment('Coloured road surface')"
                                            ></p>
                                        </div>
                                        <select
                                            v-model="answers.q16"
                                            class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-sm transition-colors focus:border-purple-500 focus:bg-white focus:outline-none"
                                        >
                                            <option value="">Select...</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                    </div>
                                    <div id="question-17" class="rounded-xl border border-purple-200 bg-white p-4 shadow-md">
                                        <div class="mb-2 flex items-center justify-start gap-2">
                                            <p class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 shadow-md">
                                                <span class="text-base font-bold text-white">17</span>
                                            </p>
                                            <p
                                                class="mb-2 text-base font-semibold text-gray-800 sm:text-lg"
                                                :data-segment-text="'New sign'"
                                                v-html="getHighlightedSegment('New sign')"
                                            ></p>
                                        </div>
                                        <select
                                            v-model="answers.q17"
                                            class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-sm transition-colors focus:border-purple-500 focus:bg-white focus:outline-none"
                                        >
                                            <option value="">Select...</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                    </div>
                                    <div id="question-18" class="rounded-xl border border-purple-200 bg-white p-4 shadow-md">
                                        <div class="mb-2 flex items-center justify-start gap-2">
                                            <p class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 shadow-md">
                                                <span class="text-base font-bold text-white">18</span>
                                            </p>
                                            <p
                                                class="mb-2 text-base font-semibold text-gray-800 sm:text-lg"
                                                :data-segment-text="'Traffic lights'"
                                                v-html="getHighlightedSegment('Traffic lights')"
                                            ></p>
                                        </div>
                                        <select
                                            v-model="answers.q18"
                                            class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-sm transition-colors focus:border-purple-500 focus:bg-white focus:outline-none"
                                        >
                                            <option value="">Select...</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                    </div>
                                    <div id="question-19" class="rounded-xl border border-purple-200 bg-white p-4 shadow-md">
                                        <div class="mb-2 flex items-center justify-start gap-2">
                                            <p class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 shadow-md">
                                                <span class="text-base font-bold text-white">19</span>
                                            </p>
                                            <p
                                                class="mb-2 text-base font-semibold text-gray-800 sm:text-lg"
                                                :data-segment-text="'Artwork'"
                                                v-html="getHighlightedSegment('Artwork')"
                                            ></p>
                                        </div>
                                        <select
                                            v-model="answers.q19"
                                            class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-sm transition-colors focus:border-purple-500 focus:bg-white focus:outline-none"
                                        >
                                            <option value="">Select...</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
                                    </div>
                                    <div id="question-20" class="rounded-xl border border-purple-200 bg-white p-4 shadow-md">
                                        <div class="mb-2 flex items-center justify-start gap-2">
                                            <p class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 shadow-md">
                                                <span class="text-base font-bold text-white">20</span>
                                            </p>
                                            <p
                                                class="mb-2 text-base font-semibold text-gray-800 sm:text-lg"
                                                :data-segment-text="'Children’s playground'"
                                                v-html="getHighlightedSegment('Children’s playground')"
                                            ></p>
                                        </div>
                                        <select
                                            v-model="answers.q20"
                                            class="w-full rounded-full border-2 border-purple-200 bg-purple-50 px-3 py-2 text-sm transition-colors focus:border-purple-500 focus:bg-white focus:outline-none"
                                        >
                                            <option value="">Select...</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                            <option value="F">F</option>
                                            <option value="G">G</option>
                                            <option value="H">H</option>
                                        </select>
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
