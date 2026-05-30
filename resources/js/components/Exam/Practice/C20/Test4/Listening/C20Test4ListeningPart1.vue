<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
interface Props {}

const props = withDefaults(defineProps<Props>(), {});

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

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    { text: 'QUESTIONS 1-10', offset: 0 },
    { text: 'Complete the notes below.', offset: 14 },
    { text: 'Write ONE WORD AND/OR A NUMBER for each answer.', offset: 39 },
    { text: 'Advice on family visit', offset: 88 },

    { text: 'Accommodation', offset: 110 },
    { text: 'Hotel on George Street', offset: 123 },
    { text: 'cost of family room per night: £', offset: 146 },
    { text: '(approx.)', offset: 177 },

    { text: 'Recommended trips', offset: 186 },
    { text: 'a', offset: 203 },
    { text: 'tour of the city centre (starts in Carlton Square)', offset: 204 },
    { text: 'a trip by', offset: 254 },
    { text: 'to the old fort', offset: 263 },

    { text: 'Science Museum', offset: 278 },
    { text: 'best day to visit:', offset: 292 },
    { text: 'see the exhibition about', offset: 310 },
    { text: 'which opens soon', offset: 334 },

    { text: 'Food', offset: 350 },
    { text: 'Clacton Market:', offset: 354 },
    { text: 'good for', offset: 369 },
    { text: 'food', offset: 377 },
    { text: 'need to have lunch before', offset: 381 },
    { text: 'p.m.', offset: 407 },

    { text: 'Theatre tickets', offset: 411 },
    { text: 'save up to', offset: 426 },
    { text: '% on ticket prices at bargaintickets.com', offset: 436 },

    { text: 'Free activities', offset: 476 },
    { text: 'Blakewell Gardens:', offset: 491 },
    { text: 'Roots Music Festival', offset: 509 },
    { text: 'climb Telegraph Hill to see a view of the', offset: 529 },
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

// Save answers to drafts

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
};

// Watch for changes and auto-save
watch(answers, () => {}, { deep: true });

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

// Load saved answers when component mounts
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
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="container mx-auto px-2 py-4 pb-32 sm:px-4">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col rounded-lg bg-white shadow-lg">
            <!-- Questions Header (Fixed) -->
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
                            <p class="text-xs font-semibold tracking-wide text-gray-700 uppercase sm:text-sm">
                                <span :data-segment-text="'QUESTIONS 1-10'" v-html="getHighlightedSegment('QUESTIONS 1-10')"></span>
                            </p>
                            <p class="text-xs text-gray-600">
                                <span
                                    :data-segment-text="'Complete the notes below.'"
                                    v-html="getHighlightedSegment('Complete the notes below.')"
                                ></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions Content -->
            <div class="space-y-6 p-4 sm:p-6 lg:p-8">
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-center">
                    <p class="font-semibold text-blue-800">
                        <span
                            :data-segment-text="'Write ONE WORD AND/OR A NUMBER for each answer.'"
                            v-html="getHighlightedSegment('Write ONE WORD AND/OR A NUMBER for each answer.')"
                        ></span>
                    </p>
                </div>

                <h2 class="text-center text-2xl font-bold text-blue-600">
                    <span :data-segment-text="'Advice on family visit'" v-html="getHighlightedSegment('Advice on family visit')"></span>
                </h2>

                <div class="space-y-8">
                    <!-- Accommodation -->
                    <div class="space-y-3">
                        <h3
                            class="text-xl font-semibold text-gray-800"
                            :data-segment-text="'Accommodation'"
                            v-html="getHighlightedSegment('Accommodation')"
                        ></h3>
                        <ul class="list-inside list-disc space-y-3 pl-4 text-gray-700">
                            <li>
                                <div class="inline-flex items-baseline gap-3">
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                        >1</span
                                    >
                                    <input
                                        v-model="answers.q1"
                                        type="text"
                                        class="w-36 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <span
                                        :data-segment-text="'Hotel on George Street'"
                                        v-html="getHighlightedSegment('Hotel on George Street')"
                                    ></span>
                                </div>
                            </li>
                            <li>
                                <div class="inline-flex items-baseline gap-3">
                                    <span
                                        :data-segment-text="'cost of family room per night: £'"
                                        v-html="getHighlightedSegment('cost of family room per night: £')"
                                    ></span>
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                        >2</span
                                    >
                                    <input
                                        v-model="answers.q2"
                                        type="text"
                                        class="w-32 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <span :data-segment-text="'(approx.)'" v-html="getHighlightedSegment('(approx.)')"></span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <!-- Recommended trips -->
                    <div class="space-y-3">
                        <h3
                            class="text-xl font-semibold text-gray-800"
                            :data-segment-text="'Recommended trips'"
                            v-html="getHighlightedSegment('Recommended trips')"
                        ></h3>
                        <ul class="list-inside list-disc space-y-3 pl-4 text-gray-700">
                            <li>
                                <div class="inline-flex items-baseline gap-3">
                                    <span :data-segment-text="'a'" v-html="getHighlightedSegment('a')"></span>
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                        >3</span
                                    >
                                    <input
                                        v-model="answers.q3"
                                        type="text"
                                        class="w-32 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <span
                                        :data-segment-text="'tour of the city centre (starts in Carlton Square)'"
                                        v-html="getHighlightedSegment('tour of the city centre (starts in Carlton Square)')"
                                    ></span>
                                </div>
                            </li>
                            <li>
                                <div class="inline-flex items-baseline gap-3">
                                    <span :data-segment-text="'a trip by'" v-html="getHighlightedSegment('a trip by')"></span>
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                        >4</span
                                    >
                                    <input
                                        v-model="answers.q4"
                                        type="text"
                                        class="w-32 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <span :data-segment-text="'to the old fort'" v-html="getHighlightedSegment('to the old fort')"></span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <!-- Science Museum -->
                    <div class="space-y-3">
                        <h3
                            class="text-xl font-semibold text-gray-800"
                            :data-segment-text="'Science Museum'"
                            v-html="getHighlightedSegment('Science Museum')"
                        ></h3>
                        <ul class="list-inside list-disc space-y-3 pl-4 text-gray-700">
                            <li>
                                <div class="inline-flex items-baseline gap-3">
                                    <span :data-segment-text="'best day to visit:'" v-html="getHighlightedSegment('best day to visit:')"></span>
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                        >5</span
                                    >
                                    <input
                                        v-model="answers.q5"
                                        type="text"
                                        class="w-32 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                </div>
                            </li>
                            <li>
                                <div class="inline-flex items-baseline gap-3">
                                    <span
                                        :data-segment-text="'see the exhibition about'"
                                        v-html="getHighlightedSegment('see the exhibition about')"
                                    ></span>
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                        >6</span
                                    >
                                    <input
                                        v-model="answers.q6"
                                        type="text"
                                        class="w-32 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <span :data-segment-text="'which opens soon'" v-html="getHighlightedSegment('which opens soon')"></span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <!-- Food -->
                    <div class="space-y-3">
                        <h3 class="text-xl font-semibold text-gray-800" :data-segment-text="'Food'" v-html="getHighlightedSegment('Food')"></h3>
                        <ul class="list-inside list-disc space-y-3 pl-4 text-gray-700">
                            <li>
                                <span :data-segment-text="'Clacton Market:'" v-html="getHighlightedSegment('Clacton Market:')"></span>
                                <ul class="mt-2 list-inside list-[circle] space-y-3 pl-4 text-gray-600">
                                    <li>
                                        <div class="inline-flex items-baseline gap-3">
                                            <span :data-segment-text="'good for'" v-html="getHighlightedSegment('good for')"></span>
                                            <span
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                                >7</span
                                            >
                                            <input
                                                v-model="answers.q7"
                                                type="text"
                                                class="w-32 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            />
                                            <span :data-segment-text="'food'" v-html="getHighlightedSegment('food')"></span>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="inline-flex items-baseline gap-3">
                                            <span
                                                :data-segment-text="'need to have lunch before'"
                                                v-html="getHighlightedSegment('need to have lunch before')"
                                            ></span>
                                            <span
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                                >8</span
                                            >
                                            <input
                                                v-model="answers.q8"
                                                type="text"
                                                class="w-32 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            />
                                            <span :data-segment-text="'p.m.'" v-html="getHighlightedSegment('p.m.')"></span>
                                        </div>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <!-- Theatre tickets -->
                    <div class="space-y-3">
                        <h3
                            class="text-xl font-semibold text-gray-800"
                            :data-segment-text="'Theatre tickets'"
                            v-html="getHighlightedSegment('Theatre tickets')"
                        ></h3>
                        <ul class="list-inside list-disc space-y-3 pl-4 text-gray-700">
                            <li>
                                <div class="inline-flex items-baseline gap-3">
                                    <span :data-segment-text="'save up to'" v-html="getHighlightedSegment('save up to')"></span>
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                        >9</span
                                    >
                                    <input
                                        v-model="answers.q9"
                                        type="text"
                                        class="w-32 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <span
                                        :data-segment-text="'% on ticket prices at bargaintickets.com'"
                                        v-html="getHighlightedSegment('% on ticket prices at bargaintickets.com')"
                                    ></span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <!-- Free activities -->
                    <div class="space-y-3">
                        <h3
                            class="text-xl font-semibold text-gray-800"
                            :data-segment-text="'Free activities'"
                            v-html="getHighlightedSegment('Free activities')"
                        ></h3>
                        <ul class="list-inside list-disc space-y-3 pl-4 text-gray-700">
                            <li>
                                <span :data-segment-text="'Blakewell Gardens:'" v-html="getHighlightedSegment('Blakewell Gardens:')"></span>
                                <ul class="mt-2 list-inside list-[circle] space-y-3 pl-4 text-gray-600">
                                    <li>
                                        <span
                                            :data-segment-text="'Roots Music Festival'"
                                            v-html="getHighlightedSegment('Roots Music Festival')"
                                        ></span>
                                    </li>
                                    <li>
                                        <div class="inline-flex items-baseline gap-3">
                                            <span
                                                :data-segment-text="'climb Telegraph Hill to see a view of the'"
                                                v-html="getHighlightedSegment('climb Telegraph Hill to see a view of the')"
                                            ></span>
                                            <span
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_10px_rgba(59,130,246,0.3)]"
                                                >10</span
                                            >
                                            <input
                                                v-model="answers.q10"
                                                type="text"
                                                class="w-32 rounded-md border border-blue-300 p-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            />
                                        </div>
                                    </li>
                                </ul>
                            </li>
                        </ul>
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
                            :class="[color.class, 'h-8 w-8 rounded-md border border-blue-300 p-1 transition-transform hover:scale-110']"
                            :title="`Highlight ${color.name}`"
                        ></button>
                        <!-- Note Button -->
                        <button
                            @click="openNoteInput"
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-blue-300 bg-blue-50 p-1 transition-all hover:scale-110 hover:bg-blue-100"
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
