<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
interface Props {}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

// Listening Part 4 component

// Answers for questions 31-40
const answers = ref({
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
    { text: 'PART 4', offset: 0 },
    { text: 'Questions 31-40', offset: 7 },
    { text: 'Complete the notes below', offset: 23 },
    { text: 'Choose ONE word only', offset: 48 },
    { text: 'Reclaiming Urban Rivers', offset: 69 },
    { text: 'Historical Background', offset: 93 },
    { text: 'Nearly all major cities were built on a river.', offset: 115 },
    { text: 'Rivers were traditionally used for transport, fishing, and recreation.', offset: 162 },
    { text: 'Industrial development and rising populations later led to:', offset: 233 },
    { text: '-More sewage from houses being discharged into the river.', offset: 293 },
    { text: '-Pollution from', offset: 353 }, // 353 + 15 = 368
    { text: 'on the river bank.', offset: 368 }, // 368 + 18 = 386
    { text: 'In 1957, the River Thames in London was declared biologically', offset: 387 }, // 387 + 62 = 449
    { text: '.', offset: 450 }, // 450 + 1 = 451
    { text: 'Recent Improvements', offset: 452 }, // 452 + 19 = 471
    { text: 'Seals and even a', offset: 472 }, // 472 + 16 = 488
    { text: 'have been seen in the River Thames.', offset: 489 }, // 489 + 35 = 524
    { text: 'Riverside warehouses are converted to restaurants and', offset: 525 }, // 525 + 52 = 577
    { text: '.', offset: 578 }, // 578 + 1 = 579
    { text: 'In Los Angeles, there are plans to:', offset: 580 }, // 580 + 34 = 614
    { text: 'Build a riverside', offset: 615 }, // 615 + 17 = 632
    { text: '.', offset: 633 }, // 633 + 1 = 634
    { text: 'Display', offset: 635 }, // 635 + 7 = 642
    { text: 'projects.', offset: 643 }, // 643 + 9 = 652
    { text: 'In Paris,', offset: 653 }, // 653 + 9 = 662
    { text: 'are created on the sides of the river every summer.', offset: 663 }, // 663 + 52 = 715
    { text: 'Transport Possibilities', offset: 716 }, // 716 + 23 = 739
    { text: 'Over 2 billion passengers already travel by', offset: 741 }, // 741 + 42 = 783
    { text: 'in cities around the world.', offset: 784 }, // 784 + 28 = 812
    { text: 'Changes in shopping habits mean the number of deliveries that are made is increasing.', offset: 813 }, // 813 + 85 = 898
    { text: 'Instead of road transport, goods can be transported by large freight barges and electric', offset: 899 }, // 899 + 89 = 988
    { text: ', or, in future, by', offset: 989 }, // 989 + 19 = 1008
    { text: '.', offset: 1009 }, // 1009 + 1 = 1010
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
                            <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">
                                <span :data-segment-text="'QUESTIONS 31-40'" v-html="getHighlightedSegment('QUESTIONS 31-40')"></span>
                            </p>
                            <p class="text-sm text-gray-600">
                                <span
                                    :data-segment-text="'Complete the notes below'"
                                    v-html="getHighlightedSegment('Complete the notes below')"
                                ></span>
                            </p>
                            <p class="text-sm text-gray-600">
                                <span :data-segment-text="'Choose ONE word only'" v-html="getHighlightedSegment('Choose ONE word only')"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto bg-orange-50 pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <div class="mb-8 text-center">
                        <h2 class="text-2xl font-bold text-orange-800">
                            <span :data-segment-text="'Reclaiming Urban Rivers'" v-html="getHighlightedSegment('Reclaiming Urban Rivers')"></span>
                        </h2>
                    </div>
                    <div class="space-y-8">
                        <div class="rounded-2xl border-2 border-orange-200 bg-white p-6 shadow-lg">
                            <h3 class="mb-4 text-xl font-bold text-orange-700">
                                <span :data-segment-text="'Historical Background'" v-html="getHighlightedSegment('Historical Background')"></span>
                            </h3>
                            <ul class="list-disc space-y-4 pl-5">
                                <li>
                                    <span
                                        :data-segment-text="'Nearly all major cities were built on a river.'"
                                        v-html="getHighlightedSegment('Nearly all major cities were built on a river.')"
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'Rivers were traditionally used for transport, fishing, and recreation.'"
                                        v-html="getHighlightedSegment('Rivers were traditionally used for transport, fishing, and recreation.')"
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'Industrial development and rising populations later led to:'"
                                        v-html="getHighlightedSegment('Industrial development and rising populations later led to:')"
                                    ></span>
                                    <ul class="mt-2 list-disc space-y-2 pl-5">
                                        <li>
                                            <span
                                                :data-segment-text="'-More sewage from houses being discharged into the river.'"
                                                v-html="getHighlightedSegment('-More sewage from houses being discharged into the river.')"
                                            ></span>
                                        </li>
                                        <li>
                                            <span :data-segment-text="'-Pollution from'" v-html="getHighlightedSegment('-Pollution from')"></span>
                                            <span id="question-31" class="inline-flex items-center">
                                                <span
                                                    class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                                    >31</span
                                                >
                                                <input
                                                    type="text"
                                                    v-model="answers.q31"
                                                    class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                                />
                                            </span>
                                            <span
                                                :data-segment-text="' on the river bank.'"
                                                v-html="getHighlightedSegment(' on the river bank.')"
                                            ></span>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'In 1957, the River Thames in London was declared biologically'"
                                        v-html="getHighlightedSegment('In 1957, the River Thames in London was declared biologically')"
                                    ></span>
                                    <span id="question-32" class="inline-flex items-center">
                                        <span
                                            class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                            >32</span
                                        >
                                        <input
                                            type="text"
                                            v-model="answers.q32"
                                            class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                        />
                                    </span>
                                    <span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-2xl border-2 border-orange-200 bg-white p-6 shadow-lg">
                            <h3 class="mb-4 text-xl font-bold text-orange-700">
                                <span :data-segment-text="'Recent Improvements'" v-html="getHighlightedSegment('Recent Improvements')"></span>
                            </h3>
                            <ul class="list-disc space-y-4 pl-5">
                                <li>
                                    <span :data-segment-text="'Seals and even a'" v-html="getHighlightedSegment('Seals and even a')"></span>
                                    <span id="question-33" class="inline-flex items-center">
                                        <span
                                            class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                            >33</span
                                        >
                                        <input
                                            type="text"
                                            v-model="answers.q33"
                                            class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                        />
                                    </span>
                                    <span
                                        :data-segment-text="' have been seen in the River Thames.'"
                                        v-html="getHighlightedSegment(' have been seen in the River Thames.')"
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'Riverside warehouses are converted to restaurants and'"
                                        v-html="getHighlightedSegment('Riverside warehouses are converted to restaurants and')"
                                    ></span>
                                    <span id="question-34" class="inline-flex items-center">
                                        <span
                                            class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                            >34</span
                                        >
                                        <input
                                            type="text"
                                            v-model="answers.q34"
                                            class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                        />
                                    </span>
                                    <span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'In Los Angeles, there are plans to:'"
                                        v-html="getHighlightedSegment('In Los Angeles, there are plans to:')"
                                    ></span>
                                    <ul class="mt-2 list-disc space-y-2 pl-5">
                                        <li>
                                            <span :data-segment-text="'Build a riverside'" v-html="getHighlightedSegment('Build a riverside')"></span>
                                            <span id="question-35" class="inline-flex items-center">
                                                <span
                                                    class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                                    >35</span
                                                >
                                                <input
                                                    type="text"
                                                    v-model="answers.q35"
                                                    class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                                />
                                            </span>
                                            <span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                                        </li>
                                        <li>
                                            <span :data-segment-text="'Display'" v-html="getHighlightedSegment('Display')"></span>
                                            <span id="question-36" class="inline-flex items-center">
                                                <span
                                                    class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                                    >36</span
                                                >
                                                <input
                                                    type="text"
                                                    v-model="answers.q36"
                                                    class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                                />
                                            </span>
                                            <span :data-segment-text="' projects.'" v-html="getHighlightedSegment(' projects.')"></span>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <span :data-segment-text="'In Paris,'" v-html="getHighlightedSegment('In Paris,')"></span>
                                    <span id="question-37" class="inline-flex items-center">
                                        <span
                                            class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                            >37</span
                                        >
                                        <input
                                            type="text"
                                            v-model="answers.q37"
                                            class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                        />
                                    </span>
                                    <span
                                        :data-segment-text="' are created on the sides of the river every summer.'"
                                        v-html="getHighlightedSegment(' are created on the sides of the river every summer.')"
                                    ></span>
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-2xl border-2 border-orange-200 bg-white p-6 shadow-lg">
                            <h3 class="mb-4 text-xl font-bold text-orange-700">
                                <span :data-segment-text="'Transport Possibilities'" v-html="getHighlightedSegment('Transport Possibilities')"></span>
                            </h3>
                            <ul class="list-disc space-y-4 pl-5">
                                <li>
                                    <span
                                        :data-segment-text="'Over 2 billion passengers already travel by'"
                                        v-html="getHighlightedSegment('Over 2 billion passengers already travel by')"
                                    ></span>
                                    <span id="question-38" class="inline-flex items-center">
                                        <span
                                            class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                            >38</span
                                        >
                                        <input
                                            type="text"
                                            v-model="answers.q38"
                                            class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                        />
                                    </span>
                                    <span
                                        :data-segment-text="' in cities around the world.'"
                                        v-html="getHighlightedSegment(' in cities around the world.')"
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'Changes in shopping habits mean the number of deliveries that are made is increasing.'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Changes in shopping habits mean the number of deliveries that are made is increasing.',
                                            )
                                        "
                                    ></span>
                                </li>
                                <li>
                                    <span
                                        :data-segment-text="'Instead of road transport, goods can be transported by large freight barges and electric'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Instead of road transport, goods can be transported by large freight barges and electric',
                                            )
                                        "
                                    ></span>
                                    <span id="question-39" class="inline-flex items-center">
                                        <span
                                            class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                            >39</span
                                        >
                                        <input
                                            type="text"
                                            v-model="answers.q39"
                                            class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                        />
                                    </span>
                                    <span :data-segment-text="', or, in future, by'" v-html="getHighlightedSegment(', or, in future, by')"></span>
                                    <span id="question-40" class="inline-flex items-center">
                                        <span
                                            class="mx-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-base font-bold text-white shadow-lg"
                                            >40</span
                                        >
                                        <input
                                            type="text"
                                            v-model="answers.q40"
                                            class="w-40 rounded-md border border-orange-300 px-2 py-1 text-base shadow focus:ring-2 focus:ring-orange-500 focus:outline-none"
                                        />
                                    </span>
                                    <span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                                </li>
                            </ul>
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
    background: linear-gradient(to bottom, #f97316, #ea580c);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ea580c, #c2410c);
}
</style>
