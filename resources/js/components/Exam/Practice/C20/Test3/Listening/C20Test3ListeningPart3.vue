<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
interface Props {}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

// Answers State
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
    { text: 'Questions 21-26', offset: 0 },
    { text: 'Choose the correct letter, A, B or C.', offset: 18 },
    { text: 'Finn was pleased to discover that their topic', offset: 60 },
    { text: 'was not familiar to their module leader.', offset: 108 },
    { text: 'had not been chosen by other students.', offset: 153 },
    { text: 'did not prove to be difficult to research.', offset: 195 },
    { text: 'Maya says a mistaken belief about theatre programmes is that', offset: 245 },
    { text: 'theatres pay companies to produce them.', offset: 310 },
    { text: 'few theatre-goers buy them nowadays.', offset: 355 },
    { text: 'they contain far more adverts than previously.', offset: 395 },
    { text: 'Finn was surprised that, in early British theatre, programmes', offset: 450 },
    { text: 'were difficult for audiences to obtain.', offset: 515 },
    { text: 'were given out free of charge.', offset: 560 },
    { text: 'were seen as a kind of contract.', offset: 595 },
    { text: 'Maya feels their project should include an explanation of why companies of actors', offset: 630 },
    { text: 'promoted their own plays.', offset: 710 },
    { text: 'performed plays outdoors.', offset: 738 },
    { text: 'had to tour with their plays.', offset: 768 },
    { text: 'Finn and Maya both think that, compared to nineteenth-century programmes, those from the eighteenth century', offset: 805 },
    { text: 'were more original.', offset: 910 },
    { text: 'were more colourful.', offset: 932 },
    { text: 'were more informative.', offset: 955 },
    { text: "Maya doesn't fully understand why, in the twentieth century,", offset: 980 },
    { text: 'very few theatre programmes were printed in the USA.', offset: 1045 },
    { text: 'British theatre programmes failed to develop for so long.', offset: 1100 },
    { text: 'theatre programmes in Britain copied fashions from the USA.', offset: 1160 },
    { text: 'Questions 27-30', offset: 1225 },
    { text: 'What comment is made about the programme for each of the following shows?', offset: 1243 },
    { text: 'Choose FOUR answers from the box and write the correct letter, A-F, next to Questions 27-30.', offset: 1315 },
    { text: 'Comments about programmes', offset: 1405 },
    { text: 'Its origin is somewhat controversial.', offset: 1435 },
    { text: 'It is historically significant for a country.', offset: 1475 },
    { text: 'It was effective at attracting audiences.', offset: 1525 },
    { text: 'It is included in a recent project.', offset: 1570 },
    { text: 'It contains insights into the show.', offset: 1610 },
    { text: 'It resembles an artwork.', offset: 1650 },
    { text: 'Ruy Blas', offset: 1678 },
    { text: 'Man of La Mancha', offset: 1688 },
    { text: 'The Tragedy of Jane Shore', offset: 1708 },
    { text: "The Sailors' Festival", offset: 1735 },
]);

const mcqQuestions = [
    {
        num: 21,
        question: 'Finn was pleased to discover that their topic',
        options: [
            { label: 'A', text: 'was not familiar to their module leader.' },
            { label: 'B', text: 'had not been chosen by other students.' },
            { label: 'C', text: 'did not prove to be difficult to research.' },
        ],
    },
    {
        num: 22,
        question: 'Maya says a mistaken belief about theatre programmes is that',
        options: [
            { label: 'A', text: 'theatres pay companies to produce them.' },
            { label: 'B', text: 'few theatre-goers buy them nowadays.' },
            { label: 'C', text: 'they contain far more adverts than previously.' },
        ],
    },
    {
        num: 23,
        question: 'Finn was surprised that, in early British theatre, programmes',
        options: [
            { label: 'A', text: 'were difficult for audiences to obtain.' },
            { label: 'B', text: 'were given out free of charge.' },
            { label: 'C', text: 'were seen as a kind of contract.' },
        ],
    },
    {
        num: 24,
        question: 'Maya feels their project should include an explanation of why companies of actors',
        options: [
            { label: 'A', text: 'promoted their own plays.' },
            { label: 'B', text: 'performed plays outdoors.' },
            { label: 'C', text: 'had to tour with their plays.' },
        ],
    },
    {
        num: 25,
        question: 'Finn and Maya both think that, compared to nineteenth-century programmes, those from the eighteenth century',
        options: [
            { label: 'A', text: 'were more original.' },
            { label: 'B', text: 'were more colourful.' },
            { label: 'C', text: 'were more informative.' },
        ],
    },
    {
        num: 26,
        question: "Maya doesn't fully understand why, in the twentieth century,",
        options: [
            { label: 'A', text: 'very few theatre programmes were printed in the USA.' },
            { label: 'B', text: 'British theatre programmes failed to develop for so long.' },
            { label: 'C', text: 'theatre programmes in Britain copied fashions from the USA.' },
        ],
    },
];

const boxOptions = [
    { label: 'A', text: 'Its origin is somewhat controversial.' },
    { label: 'B', text: 'It is historically significant for a country.' },
    { label: 'C', text: 'It was effective at attracting audiences.' },
    { label: 'D', text: 'It is included in a recent project.' },
    { label: 'E', text: 'It contains insights into the show.' },
    { label: 'F', text: 'It resembles an artwork.' },
];

const matchingQuestions = [
    { num: 27, text: 'Ruy Blas' },
    { num: 28, text: 'Man of La Mancha' },
    { num: 29, text: 'The Tragedy of Jane Shore' },
    { num: 30, text: "The Sailors' Festival" },
];

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
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

defineExpose({
    getAnswers,
    answers,
    scrollToQuestion,
    scrollToHighlight,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="container mx-auto px-1 py-2 pb-32 sm:px-2 sm:py-4 md:px-4">
        <div class="mb-10 flex min-h-screen w-full flex-col rounded-2xl bg-white/80 shadow-2xl backdrop-blur-lg">
            <!-- Header -->
            <div class="flex-shrink-0 border-b-2 border-dashed border-gray-200 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-lg"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M12 14a3 3 0 100-6 3 3 0 000 6z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-green-800">Questions 21-30</h2>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto bg-green-50/30 p-4 lg:p-6">
                <div class="space-y-12">
                    <!-- Section 21-26 -->
                    <div>
                        <p
                            class="mb-4 font-semibold text-gray-800"
                            :data-segment-text="'Choose the correct letter, A, B or C.'"
                            v-html="getHighlightedSegment('Choose the correct letter, A, B or C.')"
                        ></p>

                        <div class="space-y-8">
                            <div
                                v-for="q in mcqQuestions"
                                :key="q.num"
                                :id="`question-${q.num}`"
                                class="transform rounded-2xl border-2 border-green-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-green-300 hover:shadow-2xl"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-green-600 text-lg font-bold text-white shadow-md"
                                        style="box-shadow: 3px 3px 8px rgba(16, 185, 129, 0.4)"
                                    >
                                        {{ q.num }}
                                    </div>
                                    <div class="w-full">
                                        <p
                                            class="mb-4 font-semibold text-gray-800"
                                            :data-segment-text="q.question"
                                            v-html="getHighlightedSegment(q.question)"
                                        ></p>
                                        <div class="space-y-3">
                                            <label
                                                v-for="option in q.options"
                                                :key="option.label"
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-green-100/70"
                                            >
                                                <input
                                                    type="radio"
                                                    :name="`q${q.num}`"
                                                    :value="option.label"
                                                    v-model="answers['q' + q.num]"
                                                    class="h-5 w-5 border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span class="text-gray-700">
                                                    <span class="mr-2 font-bold">{{ option.label }}</span>
                                                    <span :data-segment-text="option.text" v-html="getHighlightedSegment(option.text)"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 27-30 -->
                    <div class="pt-8">
                        <h3
                            class="text-lg font-bold text-green-800"
                            :data-segment-text="'Questions 27-30'"
                            v-html="getHighlightedSegment('Questions 27-30')"
                        ></h3>
                        <p
                            class="mt-4 font-semibold text-gray-800"
                            :data-segment-text="'What comment is made about the programme for each of the following shows?'"
                            v-html="getHighlightedSegment('What comment is made about the programme for each of the following shows?')"
                        ></p>
                        <p
                            class="text-sm text-gray-600"
                            :data-segment-text="'Choose FOUR answers from the box and write the correct letter, A-F, next to Questions 27-30.'"
                            v-html="
                                getHighlightedSegment('Choose FOUR answers from the box and write the correct letter, A-F, next to Questions 27-30.')
                            "
                        ></p>

                        <div class="my-6 rounded-2xl border-2 border-green-200 bg-white p-6 shadow-lg">
                            <h4
                                class="mb-4 font-bold text-green-900"
                                :data-segment-text="'Comments about programmes'"
                                v-html="getHighlightedSegment('Comments about programmes')"
                            ></h4>
                            <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                                <div v-for="option in boxOptions" :key="option.label" class="flex items-start">
                                    <span class="mr-3 font-bold text-green-600">{{ option.label }}</span>
                                    <span class="text-gray-700" :data-segment-text="option.text" v-html="getHighlightedSegment(option.text)"></span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="q in matchingQuestions"
                                :key="q.num"
                                :id="`question-${q.num}`"
                                class="flex items-center justify-between rounded-xl bg-white p-4 shadow-md transition-shadow hover:shadow-xl"
                            >
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-green-600 text-lg font-bold text-white shadow-md"
                                        style="box-shadow: 3px 3px 8px rgba(16, 185, 129, 0.4)"
                                    >
                                        {{ q.num }}
                                    </div>
                                    <p class="font-semibold text-gray-800" :data-segment-text="q.text" v-html="getHighlightedSegment(q.text)"></p>
                                </div>
                                <select
                                    v-model="answers['q' + q.num]"
                                    class="w-28 rounded-lg border-2 border-green-300 bg-white p-2 text-center font-bold text-green-800 shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/50"
                                >
                                    <option value="">Select an answer</option>
                                    <option v-for="opt in boxOptions" :key="opt.label" :value="opt.label">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu & Note Input -->
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
        background-color: rgba(16, 185, 129, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(16, 185, 129, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(16, 185, 129, 0.1);
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
    background: #f0fdf4; /* Light green track */
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #10b981, #059669);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #059669, #047857);
}
</style>
