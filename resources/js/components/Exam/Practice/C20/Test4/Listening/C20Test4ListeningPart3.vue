<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

// Props for draft system
interface Props {}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

// Single choice answers for questions 25-30
const answers = ref({
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
const multipleAnswers = reactive({
    q21_22: [] as string[],
    q23_24: [] as string[],
});

watch(multipleAnswers, (newVal) => {
    answers.value.q21 = newVal.q21_22[0] || '';
    answers.value.q22 = newVal.q21_22[1] || '';
    answers.value.q23 = newVal.q23_24[0] || '';
    answers.value.q24 = newVal.q23_24[1] || '';
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

const q21_22_options = [
    { label: 'A', text: 'improved fine motor skills' },
    { label: 'B', text: 'improved memory' },
    { label: 'C', text: 'improved concentration' },
    { label: 'D', text: 'improved imagination' },
    { label: 'E', text: 'improved spatial awareness' },
];

const q23_24_options = [
    { label: 'A', text: 'not spacing letters correctly' },
    { label: 'B', text: 'not writing in a straight line' },
    { label: 'C', text: 'applying too much pressure when writing' },
    { label: 'D', text: 'confusing letter shapes' },
    { label: 'E', text: 'writing very slowly' },
];

const q25_30_questions = [
    {
        num: 25,
        question: 'What does the woman say about using laptops to teach writing to children with dyslexia?',
        options: [
            'Children often lack motivation to learn that way.',
            'Children become fluent relatively quickly.',
            'Children react more positively if they make a mistake.',
        ],
    },
    {
        num: 26,
        question: 'When discussing whether to teach cursive or print writing, the woman thinks that',
        options: [
            'cursive writing disadvantages a certain group of children.',
            'print writing is associated with lower academic performance.',
            'most teachers in the UK prefer a traditional approach to handwriting.',
        ],
    },
    {
        num: 27,
        question: 'According to the students, what impact does poor handwriting have on exam performance?',
        options: [
            'There is evidence to suggest grades are affected by poor handwriting.',
            'Neat handwriting is less important now than it used to be.',
            'Candidates write more slowly and produce shorter answers.',
        ],
    },
    {
        num: 28,
        question: 'What prediction does the man make about the future of handwriting?',
        options: [
            'Touch typing will be taught before writing by hand.',
            'Children will continue to learn to write by hand.',
            'People will dislike handwriting on digital devices.',
        ],
    },
    {
        num: 29,
        question: 'The woman is concerned that relying on digital devices has made it difficult for her to',
        options: ['take detailed notes.', 'spell and punctuate.', 'read old documents.'],
    },
    {
        num: 30,
        question: 'How do the students feel about their own handwriting?',
        options: ['concerned they are unable to write quickly', 'embarrassed by comments made about it', 'regretful that they have lost the habit'],
    },
];

const textSegments = ref([
    { text: 'QUESTIONS 21-30', offset: 0 },
    { text: 'Choose the correct answer for each question.', offset: 15 },
    { text: 'Questions 21 and 22', offset: 58 },
    { text: 'Choose TWO letters, A-E.', offset: 77 },
    { text: 'Which TWO features of the stadium tour are new this year?', offset: 102 },
    ...q21_22_options.flatMap((o, i) => [{ text: `A ${o.text}`, offset: 161 + i * 30 }]),
    { text: 'Questions 23 and 24', offset: 281 },
    ...q23_24_options.flatMap((o, i) => [{ text: `A ${o.text}`, offset: 440 + i * 30 }]),
    { text: 'For children with dyspraxia, which TWO problems with handwriting do the students think are easiest to correct?', offset: 325 },
    { text: 'Questions 25-30', offset: 585 },
    { text: 'Choose the correct letter, A, B or C.', offset: 601 },
    { text: 'Teaching handwriting', offset: 637 },
    ...q25_30_questions.flatMap((q, i) => [
        { text: q.question, offset: 657 + i * 200 },
        ...q.options.map((opt, j) => ({ text: `${String.fromCharCode(65 + j)} ${opt}`, offset: 742 + i * 200 + j * 50 })),
    ]),
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

// Save answers to drafts

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
};

// Watch for changes and auto-save

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
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 60 };
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
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

defineExpose({
    getAnswers,
    scrollToQuestion,
    scrollToHighlight,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="container mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
        <div class="flex min-h-screen w-full flex-col rounded-lg bg-white shadow-lg">
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
            <div class="flex-1 overflow-y-auto pb-32">
                <div class="space-y-8 p-4 sm:p-6 lg:p-8">
                    <div id="question-21-22" class="rounded-2xl border border-green-200 bg-white p-6 shadow-lg">
                        <div class="mb-4">
                            <h3
                                class="text-lg font-bold text-green-700"
                                :data-segment-text="'Questions 21 and 22'"
                                v-html="getHighlightedSegment('Questions 21 and 22')"
                            ></h3>
                            <p
                                class="text-sm text-gray-600"
                                :data-segment-text="'Choose TWO letters, A-E.'"
                                v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                            ></p>
                        </div>
                        <p
                            class="mb-4 font-medium text-gray-800"
                            :data-segment-text="'Which TWO features of the stadium tour are new this year?'"
                            v-html="getHighlightedSegment('Which TWO features of the stadium tour are new this year?')"
                        ></p>
                        <div class="space-y-3">
                            <label
                                v-for="opt in q21_22_options"
                                :key="opt.label"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 bg-white p-3 shadow-sm hover:bg-green-50"
                            >
                                <input
                                    type="checkbox"
                                    :value="opt.label"
                                    v-model="multipleAnswers.q21_22"
                                    :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes(opt.label)"
                                    class="h-5 w-5 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                />
                                <span
                                    class="font-medium"
                                    :data-segment-text="opt.label + ' ' + opt.text"
                                    v-html="getHighlightedSegment(opt.label + ' ' + opt.text)"
                                ></span>
                            </label>
                        </div>
                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3">
                            <p class="text-sm font-medium text-green-700">Selected: {{ multipleAnswers.q21_22.length }}/2 answers</p>
                        </div>
                    </div>

                    <div id="question-23-24" class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-lg">
                        <div class="mb-4">
                            <h3
                                class="text-lg font-bold text-emerald-700"
                                :data-segment-text="'Questions 23 and 24'"
                                v-html="getHighlightedSegment('Questions 23 and 24')"
                            ></h3>
                            <p
                                class="text-sm text-gray-600"
                                :data-segment-text="'Choose TWO letters, A-E.'"
                                v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                            ></p>
                        </div>
                        <p
                            class="mb-4 font-medium text-gray-800"
                            :data-segment-text="'For children with dyspraxia, which TWO problems with handwriting do the students think are easiest to correct?'"
                            v-html="
                                getHighlightedSegment(
                                    'For children with dyspraxia, which TWO problems with handwriting do the students think are easiest to correct?',
                                )
                            "
                        ></p>
                        <div class="space-y-3">
                            <label
                                v-for="opt in q23_24_options"
                                :key="opt.label"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-emerald-200 bg-white p-3 shadow-sm hover:bg-emerald-50"
                            >
                                <input
                                    type="checkbox"
                                    :value="opt.label"
                                    v-model="multipleAnswers.q23_24"
                                    :disabled="multipleAnswers.q23_24.length >= 2 && !multipleAnswers.q23_24.includes(opt.label)"
                                    class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span
                                    class="font-medium"
                                    :data-segment-text="opt.label + ' ' + opt.text"
                                    v-html="getHighlightedSegment(opt.label + ' ' + opt.text)"
                                ></span>
                            </label>
                        </div>
                        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                            <p class="text-sm font-medium text-emerald-700">Selected: {{ multipleAnswers.q23_24.length }}/2 answers</p>
                        </div>
                    </div>

                    <div
                        id="question-25-30"
                        class="rounded-2xl border-2 border-green-200 bg-gradient-to-br from-green-50 via-white to-emerald-50 p-6 shadow-2xl"
                    >
                        <div class="mb-6">
                            <h3
                                class="mb-2 bg-gradient-to-r from-green-600 to-emerald-700 bg-clip-text text-xl font-bold text-transparent"
                                :data-segment-text="'Questions 25-30'"
                                v-html="getHighlightedSegment('Questions 25-30')"
                            ></h3>
                            <p
                                class="font-semibold text-gray-600"
                                :data-segment-text="'Choose the correct letter, A, B or C.'"
                                v-html="getHighlightedSegment('Choose the correct letter, A, B or C.')"
                            ></p>
                        </div>

                        <h4
                            class="mb-6 bg-gradient-to-r from-green-500 to-emerald-500 bg-clip-text text-center text-2xl font-bold text-transparent"
                            :data-segment-text="'Teaching handwriting'"
                            v-html="getHighlightedSegment('Teaching handwriting')"
                        ></h4>

                        <div class="space-y-8">
                            <div v-for="q in q25_30_questions" :key="q.num" class="rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-lg">
                                <p class="mb-3 font-semibold text-gray-800">
                                    <span
                                        class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-500 text-white shadow-md"
                                        >{{ q.num }}</span
                                    ><span :data-segment-text="q.question" v-html="getHighlightedSegment(q.question)"></span>
                                </p>
                                <div class="space-y-2 pl-10">
                                    <label v-for="(opt, i) in q.options" :key="i" class="flex cursor-pointer items-center gap-3">
                                        <input
                                            type="radio"
                                            :name="`q${q.num}`"
                                            :value="String.fromCharCode(65 + i)"
                                            v-model="answers[`q${q.num}`]"
                                            class="h-4 w-4 text-green-600 focus:ring-green-500"
                                        />
                                        <span
                                            :data-segment-text="`${String.fromCharCode(65 + i)} ${opt}`"
                                            v-html="getHighlightedSegment(`${String.fromCharCode(65 + i)} ${opt}`)"
                                        ></span>
                                    </label>
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
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
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
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
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
