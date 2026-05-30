<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
    { id: 'part1-title', text: 'Part 1', offset: 0 },
    { id: 'part1-desc', text: 'Listen and answer questions 1–10.', offset: 6 },
    { text: 'PART 1', offset: 0 },
    { text: 'Questions 1 – 10', offset: 7 },
    { text: 'Complete the notes below.', offset: 24 },
    { text: 'Write ONE WORD AND/OR A NUMBER for each answer.', offset: 50 },
    { text: 'Children’s Engineering Workshops', offset: 98 },
    { text: 'Tiny Engineers (ages 4-5)', offset: 132 },
    { text: 'Activities:', offset: 158 },
    { text: 'Create a cover for an ', offset: 170 },
    { text: ' so they can drop it from a height without breaking it', offset: 203 },
    { text: 'Take part in a competition to build the tallest ', offset: 260 },
    { text: '', offset: 306 },
    { text: 'Make a ', offset: 307 },
    { text: ' powered by a balloon', offset: 316 },
    { text: 'Junior Engineers (ages 6-8)', offset: 338 },
    { text: 'Activities:', offset: 366 },
    { text: 'Build model cars, trucks and ', offset: 378 },
    { text: ' and learn how to program them so they can move', offset: 411 },
    { text: 'Take part in a competition to build the longest ', offset: 465 },
    { text: ' using card and wood', offset: 512 },
    { text: 'Create a short ', offset: 535 },
    { text: ' with special software', offset: 552 },
    { text: 'Build, ', offset: 575 },
    { text: ' and program a humanoid robot', offset: 586 },
    { text: 'Cost for a five-week block: £50', offset: 618 },
    { text: 'Held on ', offset: 651 },
    { text: ' from 10 am to 11 am', offset: 662 },
    { text: 'Location', offset: 685 },
    { text: 'Building 10A, ', offset: 694 },
    { text: ' Industrial Estate, Grasford', offset: 710 },
    { text: 'Plenty of ', offset: 739 },
    { text: ' is available', offset: 750 },
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
    return answers.value;
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
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
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="container mx-auto px-2 py-4 pb-32 sm:px-4">
        <div class="">
            <!-- Questions Section -->
            <div class="flex min-h-screen w-full flex-col rounded-lg bg-white shadow-lg">
                <!-- Questions Header -->
                <div class="flex-shrink-0 border-b border-gray-200 bg-white p-3 sm:p-4 lg:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center space-x-2 sm:space-x-3">
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 sm:h-8 sm:w-8"
                            >
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
                                <p class="text-xs font-semibold tracking-wide text-gray-700 uppercase sm:text-sm">
                                    <span :data-segment-text="'PART 1'" v-html="getHighlightedSegment('PART 1')"></span>
                                </p>
                                <p class="text-xs text-gray-600">
                                    <span :data-segment-text="'Questions 1 – 10'" v-html="getHighlightedSegment('Questions 1 – 10')"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Content -->
                <div class="space-y-6 p-6">
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <p class="mb-3 text-sm font-medium text-gray-800 sm:text-base">
                            <span :data-segment-text="'Complete the notes below.'" v-html="getHighlightedSegment('Complete the notes below.')"></span>
                            <br />
                            <span
                                :data-segment-text="'Write ONE WORD AND/OR A NUMBER for each answer.'"
                                v-html="getHighlightedSegment('Write ONE WORD AND/OR A NUMBER for each answer.')"
                                class="font-semibold text-blue-800"
                            ></span>
                        </p>
                    </div>

                    <div class="space-y-4 text-sm sm:text-base">
                        <h3
                            class="text-lg font-bold text-blue-700"
                            :data-segment-text="'Children’s Engineering Workshops'"
                            v-html="getHighlightedSegment('Children’s Engineering Workshops')"
                        ></h3>

                        <div class="pl-4">
                            <h4
                                class="text-md font-semibold text-blue-600"
                                :data-segment-text="'Tiny Engineers (ages 4-5)'"
                                v-html="getHighlightedSegment('Tiny Engineers (ages 4-5)')"
                            ></h4>
                            <div class="mt-2 space-y-4 pl-4">
                                <p>
                                    <span
                                        class="font-semibold"
                                        :data-segment-text="'Activities:'"
                                        v-html="getHighlightedSegment('Activities:')"
                                    ></span>
                                </p>
                                <div class="flex items-center">
                                    <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">1</span>
                                    <span
                                        :data-segment-text="'Create a cover for an '"
                                        v-html="getHighlightedSegment('Create a cover for an ')"
                                    ></span>
                                    <input
                                        v-model="answers.q1"
                                        type="text"
                                        class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <span
                                        :data-segment-text="' so they can drop it from a height without breaking it'"
                                        v-html="getHighlightedSegment(' so they can drop it from a height without breaking it')"
                                    ></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">2</span>
                                    <span
                                        :data-segment-text="'Take part in a competition to build the tallest '"
                                        v-html="getHighlightedSegment('Take part in a competition to build the tallest ')"
                                    ></span>
                                    <input
                                        v-model="answers.q2"
                                        type="text"
                                        class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    />
                                </div>
                                <div class="flex items-center">
                                    <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">3</span>
                                    <span :data-segment-text="'Make a '" v-html="getHighlightedSegment('Make a ')"></span>
                                    <input
                                        v-model="answers.q3"
                                        type="text"
                                        class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <span :data-segment-text="' powered by a balloon'" v-html="getHighlightedSegment(' powered by a balloon')"></span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 pl-4">
                            <h4
                                class="text-md font-semibold text-blue-600"
                                :data-segment-text="'Junior Engineers (ages 6-8)'"
                                v-html="getHighlightedSegment('Junior Engineers (ages 6-8)')"
                            ></h4>
                            <div class="mt-2 space-y-4 pl-4">
                                <p>
                                    <span
                                        class="font-semibold"
                                        :data-segment-text="'Activities:'"
                                        v-html="getHighlightedSegment('Activities:')"
                                    ></span>
                                </p>
                                <div class="flex items-center">
                                    <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">4</span>
                                    <span
                                        :data-segment-text="'Build model cars, trucks and '"
                                        v-html="getHighlightedSegment('Build model cars, trucks and ')"
                                    ></span>
                                    <input
                                        v-model="answers.q4"
                                        type="text"
                                        class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <span
                                        :data-segment-text="' and learn how to program them so they can move'"
                                        v-html="getHighlightedSegment(' and learn how to program them so they can move')"
                                    ></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">5</span>
                                    <span
                                        :data-segment-text="'Take part in a competition to build the longest '"
                                        v-html="getHighlightedSegment('Take part in a competition to build the longest ')"
                                    ></span>
                                    <input
                                        v-model="answers.q5"
                                        type="text"
                                        class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <span :data-segment-text="' using card and wood'" v-html="getHighlightedSegment(' using card and wood')"></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">6</span>
                                    <span :data-segment-text="'Create a short '" v-html="getHighlightedSegment('Create a short ')"></span>
                                    <input
                                        v-model="answers.q6"
                                        type="text"
                                        class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <span
                                        :data-segment-text="' with special software'"
                                        v-html="getHighlightedSegment(' with special software')"
                                    ></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">7</span>
                                    <span :data-segment-text="'Build, '" v-html="getHighlightedSegment('Build, ')"></span>
                                    <input
                                        v-model="answers.q7"
                                        type="text"
                                        class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <span
                                        :data-segment-text="' and program a humanoid robot'"
                                        v-html="getHighlightedSegment(' and program a humanoid robot')"
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <p>
                                <span
                                    :data-segment-text="'Cost for a five-week block: £50'"
                                    v-html="getHighlightedSegment('Cost for a five-week block: £50')"
                                ></span>
                            </p>
                            <div class="mt-2 flex items-center">
                                <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">8</span>
                                <span :data-segment-text="'Held on '" v-html="getHighlightedSegment('Held on ')"></span>
                                <input
                                    v-model="answers.q8"
                                    type="text"
                                    class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                />
                                <span :data-segment-text="' from 10 am to 11 am'" v-html="getHighlightedSegment(' from 10 am to 11 am')"></span>
                            </div>
                        </div>

                        <div class="pt-4">
                            <p><span :data-segment-text="'Location'" v-html="getHighlightedSegment('Location')"></span></p>
                            <div class="mt-2 flex items-center">
                                <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">9</span>
                                <span :data-segment-text="'Building 10A, '" v-html="getHighlightedSegment('Building 10A, ')"></span>
                                <input
                                    v-model="answers.q9"
                                    type="text"
                                    class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                />
                                <span
                                    :data-segment-text="' Industrial Estate, Grasford'"
                                    v-html="getHighlightedSegment(' Industrial Estate, Grasford')"
                                ></span>
                            </div>
                            <div class="mt-2 flex items-center">
                                <span class="mr-4 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white shadow-md">10</span>
                                <span :data-segment-text="'Plenty of '" v-html="getHighlightedSegment('Plenty of ')"></span>
                                <input
                                    v-model="answers.q10"
                                    type="text"
                                    class="mx-2 w-32 rounded border border-blue-300 px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                />
                                <span :data-segment-text="' is available'" v-html="getHighlightedSegment(' is available')"></span>
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
