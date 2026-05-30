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

// Listening Part 1 component
const answers = ref({
    q1: '',
    q2: '',
    q3: '',
    q4: '',
    q5: '',
    q6: '',
    q7: '',
    q8: '',
    q9: '',
    q10: '',
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
    { text: 'QUESTIONS 1-10', offset: 0 },
    { text: 'Choose the correct answer for each question.', offset: 15 },
    { text: 'Complete the form below.', offset: 60 },
    { text: 'Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.', offset: 84 },
    { text: 'WEST BAY HOTEL – DETAILS OF JOB', offset: 155 },
    { text: '• Newspaper advert for temporary staff', offset: 188 },
    { text: '• Vacancies for', offset: 226 },
    { text: '• Two shifts', offset: 244 },
    { text: '• Can choose your', offset: 258 },
    { text: '(must be the same each week)', offset: 277 },
    { text: '• Pay: £5.50 per hour including a', offset: 306 },
    { text: '• A', offset: 341 },
    { text: 'is provided in the hotel', offset: 346 },
    { text: '• Total weekly pay: £231', offset: 371 },
    { text: '• Dress: a white shirt and', offset: 396 },
    { text: 'trousers (not supplied), a', offset: 425 },
    { text: '(supplied)', offset: 452 },
    { text: '• Starting date:', offset: 463 },
    { text: '• Call Jane', offset: 481 },
    { text: '(Service Manager) before', offset: 494 },
    { text: 'tomorrow (Tel: 832009)', offset: 521 },
    { text: '• She’ll require a', offset: 546 },
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

    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part1');

    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part1) {
            Object.assign(answers.value, draftsResponse.data.part1);
            console.log('Loaded Listening Part 1 drafts');
        }
    } catch (error) {
        console.error('Failed to load Listening Part 1 drafts:', error);
    }
};

const saveAnswers = () => {
    if (autoSaver.value) {
        autoSaver.value.saveBatch(answers.value);
    }
};

const getAnswers = () => {
    return answers.value;
};

watch(
    answers,
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

const handleContentTextSelect = (event: MouseEvent) => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        if (!range) return;

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
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="mx-auto px-2 py-4 pb-32 sm:px-4">
        <div class="flex min-h-screen w-full flex-col rounded-lg bg-white shadow-lg">
            <div class="flex-shrink-0 border-b border-gray-200 bg-white p-3 sm:p-4 lg:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 sm:h-8 sm:w-8">
                            <svg class="h-3 w-3 text-white sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-sm"
                                :data-segment-text="'QUESTIONS 1-10'"
                                v-html="getHighlightedSegment('QUESTIONS 1-10')"
                            ></p>
                            <p
                                class="text-xs text-gray-600"
                                :data-segment-text="'Choose the correct answer for each question.'"
                                v-html="getHighlightedSegment('Choose the correct answer for each question.')"
                            ></p>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <p
                        class="text-base text-gray-900 sm:text-lg"
                        :data-segment-text="'Complete the form below.'"
                        v-html="getHighlightedSegment('Complete the form below.')"
                    ></p>
                    <p class="text-xs text-gray-900 sm:text-lg">
                        <span
                            class="text-sm text-blue-600"
                            :data-segment-text="'Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.'"
                            v-html="getHighlightedSegment('Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.')"
                        ></span>
                    </p>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-3 sm:p-4 lg:p-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:p-8">
                        <div class="space-y-6 sm:space-y-8">
                            <h1
                                class="text-center text-xl font-bold text-blue-600"
                                :data-segment-text="'WEST BAY HOTEL – DETAILS OF JOB'"
                                v-html="getHighlightedSegment('WEST BAY HOTEL – DETAILS OF JOB')"
                            ></h1>
                            <div>
                                <div class="space-y-3 text-base leading-relaxed text-gray-800 sm:space-y-4 sm:text-lg">
                                    <div class="flex items-start gap-2">
                                        <span
                                            :data-segment-text="'• Newspaper advert for temporary staff'"
                                            v-html="getHighlightedSegment('• Newspaper advert for temporary staff')"
                                        ></span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span class="flex flex-wrap items-center gap-1">
                                            <span :data-segment-text="'• Vacancies for'" v-html="getHighlightedSegment('• Vacancies for')"></span>
                                            <span id="question-1" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">1</span>
                                                </span>
                                                <input
                                                    v-model="answers.q1"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                        </span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span :data-segment-text="'• Two shifts'" v-html="getHighlightedSegment('• Two shifts')"></span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span class="flex flex-wrap items-center gap-1">
                                            <span :data-segment-text="'• Can choose your'" v-html="getHighlightedSegment('• Can choose your')"></span>
                                            <span id="question-2" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">2</span>
                                                </span>
                                                <input
                                                    v-model="answers.q2"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                            <span
                                                :data-segment-text="'(must be the same each week)'"
                                                v-html="getHighlightedSegment('(must be the same each week)')"
                                            ></span>
                                        </span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span class="flex flex-wrap items-center gap-1">
                                            <span
                                                :data-segment-text="'• Pay: £5.50 per hour including a'"
                                                v-html="getHighlightedSegment('• Pay: £5.50 per hour including a')"
                                            ></span>
                                            <span id="question-3" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">3</span>
                                                </span>
                                                <input
                                                    v-model="answers.q3"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                        </span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span class="flex flex-wrap items-center gap-1">
                                            <span :data-segment-text="'• A'" v-html="getHighlightedSegment('• A')"></span>
                                            <span id="question-4" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">4</span>
                                                </span>
                                                <input
                                                    v-model="answers.q4"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                            <span
                                                :data-segment-text="'is provided in the hotel'"
                                                v-html="getHighlightedSegment('is provided in the hotel')"
                                            ></span>
                                        </span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span
                                            :data-segment-text="'• Total weekly pay: £231'"
                                            v-html="getHighlightedSegment('• Total weekly pay: £231')"
                                        ></span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span class="flex flex-wrap items-center gap-1">
                                            <span
                                                :data-segment-text="'• Dress: a white shirt and'"
                                                v-html="getHighlightedSegment('• Dress: a white shirt and')"
                                            ></span>
                                            <span id="question-5" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">5</span>
                                                </span>
                                                <input
                                                    v-model="answers.q5"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                            <span
                                                :data-segment-text="'trousers (not supplied), a'"
                                                v-html="getHighlightedSegment('trousers (not supplied), a')"
                                            ></span>
                                            <span id="question-6" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">6</span>
                                                </span>
                                                <input
                                                    v-model="answers.q6"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                            <span :data-segment-text="'(supplied)'" v-html="getHighlightedSegment('(supplied)')"></span>
                                        </span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span class="flex flex-wrap items-center gap-1">
                                            <span :data-segment-text="'• Starting date:'" v-html="getHighlightedSegment('• Starting date:')"></span>
                                            <span id="question-7" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">7</span>
                                                </span>
                                                <input
                                                    v-model="answers.q7"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                        </span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span class="flex flex-wrap items-center gap-1">
                                            <span :data-segment-text="'• Call Jane'" v-html="getHighlightedSegment('• Call Jane')"></span>
                                            <span id="question-8" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">8</span>
                                                </span>
                                                <input
                                                    v-model="answers.q8"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                            <span
                                                :data-segment-text="'(Service Manager) before'"
                                                v-html="getHighlightedSegment('(Service Manager) before')"
                                            ></span>
                                            <span id="question-9" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">9</span>
                                                </span>
                                                <input
                                                    v-model="answers.q9"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                            <span
                                                :data-segment-text="'tomorrow (Tel: 832009)'"
                                                v-html="getHighlightedSegment('tomorrow (Tel: 832009)')"
                                            ></span>
                                        </span>
                                    </div>

                                    <div class="flex items-start gap-2">
                                        <span class="flex flex-wrap items-center gap-1">
                                            <span
                                                :data-segment-text="'• She’ll require a'"
                                                v-html="getHighlightedSegment('• She’ll require a')"
                                            ></span>
                                            <span id="question-10" class="inline-flex items-center gap-1">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 shadow-md"
                                                >
                                                    <span class="text-base font-bold text-white">10</span>
                                                </span>
                                                <input
                                                    v-model="answers.q10"
                                                    type="text"
                                                    class="min-w-[90px] border-b-2 border-blue-300 bg-transparent px-2 py-1 text-center focus:border-blue-600 focus:outline-none"
                                                    placeholder="_________"
                                                />
                                            </span>
                                        </span>
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
                <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-base text-gray-600 italic">"{{ selectedText }}"</p>
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
                    class="rounded-md bg-gray-100 px-3 py-1.5 text-base font-medium text-gray-700 transition-colors hover:bg-gray-200"
                >
                    Cancel
                </button>
                <button
                    @click="saveNote"
                    class="rounded-md bg-blue-600 px-3 py-1.5 text-base font-medium text-white transition-colors hover:bg-blue-700"
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
    background-color: rgba(59, 130, 246, 0.15);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(59, 130, 246, 0.15);
        transform: scale(1);
    }

    50% {
        background-color: rgba(59, 130, 246, 0.3);
        transform: scale(1.05);
    }

    100% {
        background-color: rgba(59, 130, 246, 0.15);
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
    background: linear-gradient(to bottom, #3b82f6, #6366f1);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #2563eb, #4f46e5);
}
</style>
