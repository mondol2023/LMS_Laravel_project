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

// Local types for this component
interface AnswersRecord {
    q21: string;
    q22: string;
    q23: string;
    q24: string;
    q25: string;
    q26: string;
}

interface MultipleAnswersRecord {
    q27_28: string[];
    q29_30: string[];
}

// Listening Part 3 component
const answers = ref<AnswersRecord>({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

const multipleAnswers = ref<MultipleAnswersRecord>({
    q27_28: [],
    q29_30: [],
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
    { text: 'Section 3', offset: 0 },
    { text: 'Questions 21-26', offset: 9 },
    { text: 'What is the tutor’s opinion of the following company projects?', offset: 25 },
    { text: 'Choose FIVE answers from the box, and write the correct letter, A-H, next to questions 21-26.', offset: 88 },
    { text: 'Tutor’s opinion', offset: 178 },
    { text: 'A. It would be very rewarding for the student.', offset: 195 },
    { text: 'B. It is too ambitious.', offset: 239 },
    { text: 'C. It would be difficult to evaluate.', offset: 261 },
    { text: 'D. It wouldn’t be sufficiently challenging.', offset: 298 },
    { text: 'E. It would involve extra costs.', offset: 341 },
    { text: 'F. It is beyond the student’s current ability.', offset: 371 },
    { text: 'G. It is already being done by another student.', offset: 418 },
    { text: 'H. It would probably have the greatest impact on the company.', offset: 465 },
    { text: 'Company projects', offset: 529 },
    { text: 'Customer database', offset: 546 },
    { text: 'Online sales catalogue', offset: 569 },
    { text: 'Payroll', offset: 595 },
    { text: 'Stock inventory', offset: 608 },
    { text: 'Internal security', offset: 628 },
    { text: 'Customer services', offset: 649 },
    { text: 'Questions 27-28', offset: 672 },
    { text: 'Choose TWO letters, A-E.', offset: 689 },
    { text: 'Which TWO problems do Sam and the tutor identify concerning group assignments?', offset: 716 },
    { text: 'A. Personal relationships.', offset: 799 },
    { text: 'B. Cultural differences.', offset: 826 },
    { text: 'C. Division of labour.', offset: 852 },
    { text: 'D. Group leadership.', offset: 876 },
    { text: 'E. Group size.', offset: 897 },
    { text: 'Questions 29-30', offset: 912 },
    { text: 'Choose TWO letters, A-E.', offset: 929 },
    { text: 'Which TWO problems does Sam identify concerning the lecturers?', offset: 956 },
    { text: 'A. Punctuality.', offset: 1020 },
    { text: 'B. Organisation.', offset: 1036 },
    { text: 'C. Accessibility.', offset: 1053 },
    { text: 'D. Helpfulness.', offset: 1071 },
    { text: 'E. Teaching materials.', offset: 1087 },
]);

const handleMultipleChoice = (questionGroup: keyof MultipleAnswersRecord, option: string) => {
    const key = questionGroup;
    const currentAnswers = multipleAnswers.value[key];

    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        // Remove if already selected
        currentAnswers.splice(index, 1);
    } else {
        // 'choose TWO' in the UI
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

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentIndex: number) => {
    const segment = textSegments.value[segmentIndex];
    if (!segment) return '';

    const segmentText = segment.text;
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
    const examId = props.examId || '1234567';

    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part3');

    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part3) {
            Object.assign(answers.value, draftsResponse.data.part3);
            console.log('Loaded Listening Part 3 drafts');
        }
    } catch (error) {
        console.error('Failed to load Listening Part 3 drafts:', error);
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
    const allAnswers: Record<string, any> = { ...answers.value };

    if (Array.isArray(multipleAnswers.value.q27_28) && multipleAnswers.value.q27_28.length > 0) {
        const selectedOptions = multipleAnswers.value.q27_28;
        allAnswers.q27 = selectedOptions[0] || '';
        allAnswers.q28 = selectedOptions[1] || '';
    }

    if (Array.isArray(multipleAnswers.value.q29_30) && multipleAnswers.value.q29_30.length > 0) {
        const selectedOptions = multipleAnswers.value.q29_30;
        allAnswers.q29 = selectedOptions[0] || '';
        allAnswers.q30 = selectedOptions[1] || '';
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

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
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
            const segmentEl = (container as Element).closest('[data-segment-index]');

            if (!segmentEl) {
                return null;
            }

            const segmentIndex = parseInt(segmentEl.getAttribute('data-segment-index') || '-1');
            if (segmentIndex === -1) return null;

            const segment = textSegments.value[segmentIndex];
            if (!segment) {
                console.error('Segment not found for index:', segmentIndex);
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
                                <span :data-segment-index="0" v-html="getHighlightedSegment(0)"></span> -
                                <span :data-segment-index="1" v-html="getHighlightedSegment(1)"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto bg-green-50 pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="space-y-8">
                        <div class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-100 p-6 shadow-lg">
                            <p class="mb-4 font-medium text-gray-800" :data-segment-index="2" v-html="getHighlightedSegment(2)"></p>
                            <p class="mb-4 font-medium text-gray-800" :data-segment-index="3" v-html="getHighlightedSegment(3)"></p>

                            <div class="mb-6 rounded-xl border border-green-200 bg-white p-6 shadow-md">
                                <h4 class="mb-3 text-lg font-bold text-green-800" :data-segment-index="4" v-html="getHighlightedSegment(4)"></h4>
                                <ul class="space-y-2">
                                    <li
                                        v-for="i in 8"
                                        :key="i"
                                        class="text-gray-700"
                                        :data-segment-index="4 + i"
                                        v-html="getHighlightedSegment(4 + i)"
                                    ></li>
                                </ul>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="i in 6"
                                    :key="i"
                                    class="flex items-center gap-4 rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-sm"
                                >
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-semibold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >{{ 20 + i }}</span
                                    >
                                    <label
                                        :for="`q${20 + i}`"
                                        class="flex-1 font-medium text-gray-800"
                                        :data-segment-index="13 + i"
                                        v-html="getHighlightedSegment(13 + i)"
                                    ></label>
                                    <select
                                        :id="`q${20 + i}`"
                                        v-model="answers[`q${20 + i}`]"
                                        class="w-24 rounded-md border-2 border-green-300 bg-green-50 px-3 py-1 text-sm focus:border-green-500 focus:bg-white focus:outline-none"
                                    >
                                        <option disabled value="">Select</option>
                                        <option v-for="char in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :key="char" :value="char">{{ char }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-100 p-6 shadow-lg">
                            <div class="mb-4 flex items-start gap-4">
                                <div>
                                    <p
                                        class="my-2 text-xl font-semibold text-green-500"
                                        :data-segment-index="20"
                                        v-html="getHighlightedSegment(20)"
                                    ></p>
                                    <p class="font-medium text-gray-800" :data-segment-index="21" v-html="getHighlightedSegment(21)"></p>
                                </div>
                            </div>
                            <p class="mb-4 text-lg font-semibold text-gray-800" :data-segment-index="22" v-html="getHighlightedSegment(22)"></p>

                            <div class="mt-4 space-y-3">
                                <label
                                    v-for="(option, index) in ['A', 'B', 'C', 'D', 'E']"
                                    :key="'27-28-' + option"
                                    @click="handleMultipleChoice('q27_28', option)"
                                    :class="[
                                        'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all sm:rounded-xl sm:p-4',
                                        isSelected('q27_28', option)
                                            ? 'border-green-500 bg-green-100 shadow-md'
                                            : 'border-green-200 bg-white hover:bg-green-50',
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        :value="option"
                                        :checked="isSelected('q27_28', option)"
                                        class="h-5 w-5 flex-shrink-0 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base font-medium text-gray-800 sm:text-lg">
                                        <span :data-segment-index="23 + index" v-html="getHighlightedSegment(23 + index)"></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-100 p-6 shadow-lg">
                            <div class="mb-4 flex items-start gap-4">
                                <div>
                                    <p
                                        class="my-2 text-xl font-semibold text-green-500"
                                        :data-segment-index="28"
                                        v-html="getHighlightedSegment(28)"
                                    ></p>
                                    <p class="font-medium text-gray-800" :data-segment-index="29" v-html="getHighlightedSegment(29)"></p>
                                </div>
                            </div>
                            <p class="mb-4 text-lg font-semibold text-gray-800" :data-segment-index="30" v-html="getHighlightedSegment(30)"></p>

                            <div class="mt-4 space-y-3">
                                <label
                                    v-for="(option, index) in ['A', 'B', 'C', 'D', 'E']"
                                    :key="'29-30-' + option"
                                    @click="handleMultipleChoice('q29_30', option)"
                                    :class="[
                                        'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all sm:rounded-xl sm:p-4',
                                        isSelected('q29_30', option)
                                            ? 'border-green-500 bg-green-100 shadow-md'
                                            : 'border-green-200 bg-white hover:bg-green-50',
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        :value="option"
                                        :checked="isSelected('q29_30', option)"
                                        class="h-5 w-5 flex-shrink-0 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                    />
                                    <span class="text-base font-medium text-gray-800 sm:text-lg">
                                        <span :data-segment-index="31 + index" v-html="getHighlightedSegment(31 + index)"></span>
                                    </span>
                                </label>
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
