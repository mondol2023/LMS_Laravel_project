<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Props
interface Props {
    correctAnswers?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    correctAnswers: () => ({}),
});

// Answers State
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

// Highlighting & Notes
const contentTextRef = ref<HTMLDivElement | null>(null);
const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// Text segments with continuous offsets for multiline support
const textSegments = ref([
    { id: 'header', text: 'PART 1', offset: 0 },
    { id: 'questions', text: 'Questions 1 - 10', offset: 7 },
    { id: 'instruction1', text: 'Complete the notes below.', offset: 24 },
    { id: 'instruction2', text: 'Write ONE WORD AND/OR A NUMBER for each answer.', offset: 50 },
    { id: 'title', text: 'Hinchingbrooke Country Park', offset: 98 },
    { id: 'section1', text: 'The park', offset: 126 },
    { id: 'area', text: 'Area:', offset: 135 },
    { id: 'hectares', text: 'hectares', offset: 141 },
    { id: 'habitats', text: 'Habitats: wetland, grassland and woodland', offset: 150 },
    { id: 'wetland', text: 'Wetland: lakes, ponds and a', offset: 192 },
    { id: 'wildlife', text: 'Wildlife includes birds, insects and animals', offset: 220 },
    { id: 'section2', text: 'Subjects studied in educational visits include', offset: 265 },
    { id: 'science', text: 'Science: Children look at', offset: 312 },
    { id: 'plants', text: 'about plants, etc.', offset: 338 },
    { id: 'geography', text: 'Geography: includes learning to use a', offset: 357 },
    { id: 'compass', text: 'and compass', offset: 395 },
    { id: 'history', text: 'History: changes in land use', offset: 407 },
    { id: 'leisure', text: "Leisure and tourism: mostly concentrates on the park's", offset: 436 },
    { id: 'music', text: 'Music: Children make', offset: 491 },
    { id: 'natural', text: 'with natural materials, and experiment with rhythm and speed.', offset: 512 },
    { id: 'section3', text: 'Benefits of outdoor educational visits', offset: 574 },
    { id: 'feeling', text: 'They give children a feeling of', offset: 613 },
    { id: 'elsewhere', text: 'that they may not have elsewhere.', offset: 645 },
    { id: 'learn', text: 'Children learn new', offset: 679 },
    { id: 'confidence', text: 'and gain self-confidence.', offset: 698 },
    { id: 'section4', text: 'Practical issues', offset: 724 },
    { id: 'cost', text: 'Cost per child: £', offset: 741 },
    { id: 'adults', text: 'Adults, such as', offset: 759 },
    { id: 'free', text: ', free', offset: 775 },
]);

// Get highlighted segment with multiline support
const getHighlightedSegment = (segmentText: string, segmentId?: string) => {
    const segment = segmentId ? textSegments.value.find((s) => s.id === segmentId) : textSegments.value.find((s) => s.text === segmentText);

    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;

    // Find highlights that overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    if (overlappingHighlights.length === 0) {
        return segmentText;
    }

    // Sort highlights by start position (descending for safe replacement)
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

// Handle text selection for highlighting
const handleContentTextSelect = (event: MouseEvent) => {
    if (!contentTextRef.value?.contains(event.target as Node)) return;

    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0 || selection.toString().trim().length === 0) {
            showContextMenu.value = false;
            return;
        }

        const range = selection.getRangeAt(0);

        // Calculate absolute offset across segments
        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container: Node | null = node;
            if (container.nodeType !== Node.ELEMENT_NODE) container = container.parentNode;

            const segmentEl = (container as Element)?.closest('[data-segment-id]');
            if (!segmentEl) return null;

            const segmentId = segmentEl.getAttribute('data-segment-id');
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) return null;

            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT, null);
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
        contextMenuPosition.value = { x: event.clientX, y: event.clientY };
        showContextMenu.value = true;
        selectedText.value = selection.toString();
        selectionRange.value = { start: startAbsOffset, end: endAbsOffset };
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 50 };
    showNoteInput.value = true;
    showContextMenu.value = false;
    nextTick(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus());
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    notes.value.push({
        id: Date.now(),
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: selectionRange.value.start,
        end: selectionRange.value.end,
        part: 'Part 1',
    });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => {
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const deleteNote = (id: number) => {
    notes.value = notes.value.filter((note) => note.id !== id);
};

const handleClickOutside = (event: MouseEvent) => {
    if (showContextMenu.value) {
        const contextMenu = document.querySelector('.context-menu');
        if (contextMenu && !contextMenu.contains(event.target as Node)) {
            showContextMenu.value = false;
        }
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        if (showContextMenu.value) showContextMenu.value = false;
        if (showNoteInput.value) showNoteInput.value = false;
    }
};

// Scroll to question functionality
const scrollToQuestion = (questionNumber: number) => {
    const inputEl = document.querySelector(`input[data-question="${questionNumber}"]`);
    if (inputEl) {
        inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        (inputEl as HTMLInputElement).focus();
    }
};

// Lifecycle
onMounted(() => {
    document.addEventListener('mouseup', handleContentTextSelect);
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('mouseup', handleContentTextSelect);
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

// Expose
defineExpose({
    getAnswers: () => answers.value,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" class="mx-auto mb-16 px-4 py-8">
        <div class="w-full rounded-2xl bg-white/70 p-6 shadow-2xl backdrop-blur-xl">
            <!-- Header -->
            <div class="flex-shrink-0 border-b border-gray-200 bg-white p-4 lg:p-6">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center space-x-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600">
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                ></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold tracking-widest text-blue-700 uppercase">
                                <span data-segment-id="header" v-html="getHighlightedSegment('PART 1', 'header')"></span>
                            </h2>
                            <h1 class="text-base font-bold text-gray-900">
                                <span data-segment-id="questions" v-html="getHighlightedSegment('Questions 1 - 10', 'questions')"></span>
                            </h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="mb-6 border-b-2 border-dashed border-gray-300 pt-4 pb-6">
                <p class="text-sm text-gray-700">
                    <span data-segment-id="instruction1" v-html="getHighlightedSegment('Complete the notes below.', 'instruction1')"></span>
                </p>
                <p class="font-bold text-blue-800">
                    <span
                        data-segment-id="instruction2"
                        v-html="getHighlightedSegment('Write ONE WORD AND/OR A NUMBER for each answer.', 'instruction2')"
                    ></span>
                </p>
            </div>

            <!-- Title -->
            <h3 class="mb-6 text-center text-xl font-bold text-blue-900">
                <span data-segment-id="title" v-html="getHighlightedSegment('Hinchingbrooke Country Park', 'title')"></span>
            </h3>

            <!-- Content Sections -->
            <div class="space-y-6">
                <!-- The park Section -->
                <div class="rounded-lg bg-blue-50/50 p-4">
                    <h4 class="mb-3 text-lg font-semibold text-blue-800">
                        <span data-segment-id="section1" v-html="getHighlightedSegment('The park', 'section1')"></span>
                    </h4>

                    <div class="space-y-3 text-gray-700">
                        <p class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="area" v-html="getHighlightedSegment('Area:', 'area')"></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >1</span
                            >
                            <input
                                v-model="answers.q1"
                                data-question="1"
                                type="text"
                                class="w-24 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                            <span data-segment-id="hectares" v-html="getHighlightedSegment('hectares', 'hectares')"></span>
                        </p>

                        <p>
                            <span
                                data-segment-id="habitats"
                                v-html="getHighlightedSegment('Habitats: wetland, grassland and woodland', 'habitats')"
                            ></span>
                        </p>

                        <p class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="wetland" v-html="getHighlightedSegment('Wetland: lakes, ponds and a', 'wetland')"></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >2</span
                            >
                            <input
                                v-model="answers.q2"
                                data-question="2"
                                type="text"
                                class="w-24 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                        </p>

                        <p>
                            <span
                                data-segment-id="wildlife"
                                v-html="getHighlightedSegment('Wildlife includes birds, insects and animals', 'wildlife')"
                            ></span>
                        </p>
                    </div>
                </div>

                <!-- Subjects studied Section -->
                <div class="rounded-lg bg-green-50/50 p-4">
                    <h4 class="mb-3 text-lg font-semibold text-green-800">
                        <span
                            data-segment-id="section2"
                            v-html="getHighlightedSegment('Subjects studied in educational visits include', 'section2')"
                        ></span>
                    </h4>

                    <div class="ml-4 space-y-3 text-gray-700">
                        <p class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="science" v-html="getHighlightedSegment('Science: Children look at', 'science')"></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >3</span
                            >
                            <input
                                v-model="answers.q3"
                                data-question="3"
                                type="text"
                                class="w-24 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                            <span data-segment-id="plants" v-html="getHighlightedSegment('about plants, etc.', 'plants')"></span>
                        </p>

                        <p class="flex flex-wrap items-center gap-2">
                            <span
                                data-segment-id="geography"
                                v-html="getHighlightedSegment('Geography: includes learning to use a', 'geography')"
                            ></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >4</span
                            >
                            <input
                                v-model="answers.q4"
                                data-question="4"
                                type="text"
                                class="w-24 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                            <span data-segment-id="compass" v-html="getHighlightedSegment('and compass', 'compass')"></span>
                        </p>

                        <p>
                            <span data-segment-id="history" v-html="getHighlightedSegment('History: changes in land use', 'history')"></span>
                        </p>

                        <p class="flex flex-wrap items-center gap-2">
                            <span
                                data-segment-id="leisure"
                                v-html="getHighlightedSegment('Leisure and tourism: mostly concentrates on the park\'s', 'leisure')"
                            ></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >5</span
                            >
                            <input
                                v-model="answers.q5"
                                data-question="5"
                                type="text"
                                class="w-24 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                        </p>

                        <p class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="music" v-html="getHighlightedSegment('Music: Children make', 'music')"></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >6</span
                            >
                            <input
                                v-model="answers.q6"
                                data-question="6"
                                type="text"
                                class="w-24 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                            <span
                                data-segment-id="natural"
                                v-html="getHighlightedSegment('with natural materials, and experiment with rhythm and speed.', 'natural')"
                            ></span>
                        </p>
                    </div>
                </div>

                <!-- Benefits Section -->
                <div class="rounded-lg bg-purple-50/50 p-4">
                    <h4 class="mb-3 text-lg font-semibold text-purple-800">
                        <span data-segment-id="section3" v-html="getHighlightedSegment('Benefits of outdoor educational visits', 'section3')"></span>
                    </h4>

                    <div class="ml-4 space-y-3 text-gray-700">
                        <p class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="feeling" v-html="getHighlightedSegment('They give children a feeling of', 'feeling')"></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >7</span
                            >
                            <input
                                v-model="answers.q7"
                                data-question="7"
                                type="text"
                                class="w-24 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                            <span data-segment-id="elsewhere" v-html="getHighlightedSegment('that they may not have elsewhere.', 'elsewhere')"></span>
                        </p>

                        <p class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="learn" v-html="getHighlightedSegment('Children learn new', 'learn')"></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >8</span
                            >
                            <input
                                v-model="answers.q8"
                                data-question="8"
                                type="text"
                                class="w-24 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                            <span data-segment-id="confidence" v-html="getHighlightedSegment('and gain self-confidence.', 'confidence')"></span>
                        </p>
                    </div>
                </div>

                <!-- Practical issues Section -->
                <div class="rounded-lg bg-orange-50/50 p-4">
                    <h4 class="mb-3 text-lg font-semibold text-orange-800">
                        <span data-segment-id="section4" v-html="getHighlightedSegment('Practical issues', 'section4')"></span>
                    </h4>

                    <div class="ml-4 space-y-3 text-gray-700">
                        <p class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="cost" v-html="getHighlightedSegment('Cost per child: £', 'cost')"></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >9</span
                            >
                            <input
                                v-model="answers.q9"
                                data-question="9"
                                type="text"
                                class="w-20 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                        </p>

                        <p class="flex flex-wrap items-center gap-2">
                            <span data-segment-id="adults" v-html="getHighlightedSegment('Adults, such as', 'adults')"></span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                                >10</span
                            >
                            <input
                                v-model="answers.q10"
                                data-question="10"
                                type="text"
                                class="w-24 rounded-md border border-blue-400 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="......"
                            />
                            <span data-segment-id="free" v-html="getHighlightedSegment(', free', 'free')"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu for Highlighting -->
        <Teleport to="body">
            <div
                v-if="showContextMenu"
                class="pointer-events-none fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px` }"
            >
                <div class="context-menu pointer-events-auto rounded-lg border border-gray-200 bg-white p-2 shadow-xl" @click.stop>
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

            <!-- Note Input Modal -->
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
    background-color: rgba(254, 240, 138, 0.7);
}
.highlight-green {
    background-color: rgba(187, 247, 208, 0.7);
}
.highlight-blue {
    background-color: rgba(191, 219, 254, 0.7);
}
.highlight-pink {
    background-color: rgba(251, 207, 232, 0.7);
}
.highlight-orange {
    background-color: rgba(254, 215, 170, 0.7);
}
</style>
