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

const { highlights, selectedText, selectionRange, colors, addHighlight } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    { text: 'Section 1', offset: 0 },
    { text: 'Questions 1-10', offset: 9 },
    { text: 'Complete the notes below. Write NO MORE THAN ONE WORD for each answer.', offset: 23 },
    { text: 'Advice on plumbers and decorators', offset: 93 },
    { text: "Don't call a plumber during the ", offset: 126 },
    { text: 'Look at trade website: www. ', offset: 162 },
    { text: '.com', offset: 193 },
    { text: 'Questions 3-10', offset: 197 },
    { text: 'Complete the table below. Write NO MORE THAN ONE WORD for each answer.', offset: 211 },
    { text: 'Name', offset: 281 },
    { text: 'Positive points', offset: 285 },
    { text: 'Negative points', offset: 300 },
    { text: "Peake's Plumbing", offset: 315 },
    { text: 'pleasant and friendly', offset: 331 },
    { text: 'give ', offset: 354 },
    { text: 'information', offset: 361 },
    { text: 'good quality work', offset: 373 },
    { text: 'always ', offset: 393 },
    { text: 'John Damerol Plumbing Services', offset: 402 },
    { text: '–  ', offset: 433 },
    { text: ' than other companies', offset: 436 },
    { text: 'reliable', offset: 458 },
    { text: 'not very polite', offset: 469 },
    { text: 'tends to be ', offset: 487 },
    { text: 'Simonson Platerers', offset: 502 },
    { text: 'able to do lots of different ', offset: 521 },
    { text: 'more ', offset: 553 },
    { text: ' than other companies', offset: 560 },
    { text: 'H.L. Plaster', offset: 582 },
    { text: 'reliable', offset: 594 },
    { text: 'also able to do ', offset: 605 },
    { text: 'prefers not to use long ', offset: 624 },
]);

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentIndex: number) => {
    const segment = textSegments.value[segmentIndex];
    if (!segment) return '';

    const segmentText = segment.text;
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
const loadSavedAnswers = async () => {
    const userPhone = props.phone || draftService.getUserPhone();
    const examId = props.examId || '1234567';

    // Initialize auto-saver
    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part1');

    // Load saved drafts
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

// Save answers to drafts
const saveAnswers = () => {
    if (autoSaver.value) {
        autoSaver.value.saveBatch(answers.value);
    }
};

// Expose methods for parent component
const getAnswers = () => {
    return answers.value;
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
            const segmentEl = (container as Element).closest('[data-segment-index]');

            if (!segmentEl) {
                // It's possible the selection is on non-segmented text, like inside a label for a checkbox.
                // In this case, we don't want to show the context menu.
                return null;
            }

            const segmentIndex = parseInt(segmentEl.getAttribute('data-segment-index') || '-1');
            if (segmentIndex === -1) {
                return null;
            }
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
                                <span :data-segment-index="0" v-html="getHighlightedSegment(0)"></span> -
                                <span :data-segment-index="1" v-html="getHighlightedSegment(1)"></span>
                            </p>
                            <p class="text-xs text-gray-600"><span :data-segment-index="2" v-html="getHighlightedSegment(2)"></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions Content -->
            <div class="flex-1 bg-blue-50 p-6">
                <div class="space-y-8">
                    <!-- Questions 1-2 -->
                    <div class="rounded-xl border border-blue-200 bg-white p-6 shadow-md">
                        <h3 class="mb-4 text-lg font-semibold text-blue-800" :data-segment-index="3" v-html="getHighlightedSegment(3)"></h3>
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                    style="
                                        box-shadow:
                                            0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                    "
                                    >1</span
                                >
                                <div class="flex-1 text-gray-700">
                                    <span :data-segment-index="4" v-html="getHighlightedSegment(4)"></span>
                                    <input
                                        v-model="answers.q1"
                                        type="text"
                                        class="w-32 rounded-md border-2 border-blue-200 bg-blue-50 px-3 py-1 text-sm transition-colors focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                    style="
                                        box-shadow:
                                            0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                    "
                                    >2</span
                                >
                                <div class="flex-1 text-gray-700">
                                    <span :data-segment-index="5" v-html="getHighlightedSegment(5)"></span>
                                    <input
                                        v-model="answers.q2"
                                        type="text"
                                        class="w-48 rounded-md border-2 border-blue-200 bg-blue-50 px-3 py-1 text-sm transition-colors focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
                                    <span :data-segment-index="6" v-html="getHighlightedSegment(6)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Questions 3-10 -->
                    <div class="rounded-xl border border-blue-200 bg-white p-6 shadow-md">
                        <h3 class="mb-2 text-lg font-semibold text-blue-800" :data-segment-index="7" v-html="getHighlightedSegment(7)"></h3>
                        <p class="mb-4 text-gray-700" :data-segment-index="8" v-html="getHighlightedSegment(8)"></p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse border border-blue-300">
                                <thead>
                                    <tr class="bg-blue-100">
                                        <th
                                            class="border border-blue-300 p-3 text-left font-semibold text-blue-900"
                                            :data-segment-index="9"
                                            v-html="getHighlightedSegment(9)"
                                        ></th>
                                        <th
                                            class="border border-blue-300 p-3 text-left font-semibold text-blue-900"
                                            :data-segment-index="10"
                                            v-html="getHighlightedSegment(10)"
                                        ></th>
                                        <th
                                            class="border border-blue-300 p-3 text-left font-semibold text-blue-900"
                                            :data-segment-index="11"
                                            v-html="getHighlightedSegment(11)"
                                        ></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border border-blue-300 p-3" :data-segment-index="12" v-html="getHighlightedSegment(12)"></td>
                                        <td class="border border-blue-300 p-3">
                                            <ul class="list-inside list-disc space-y-2">
                                                <li :data-segment-index="13" v-html="getHighlightedSegment(13)"></li>
                                                <li>
                                                    <span
                                                        class="float-left mr-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >3</span
                                                    >
                                                    <span :data-segment-index="14" v-html="getHighlightedSegment(14)"></span>
                                                    <input
                                                        v-model="answers.q3"
                                                        type="text"
                                                        class="inline-block w-24 rounded-md border-2 border-blue-200 bg-blue-50 px-2 py-1 text-sm"
                                                    />
                                                    <span :data-segment-index="15" v-html="getHighlightedSegment(15)"></span>
                                                </li>
                                                <li :data-segment-index="16" v-html="getHighlightedSegment(16)"></li>
                                            </ul>
                                        </td>
                                        <td class="border border-blue-300 p-3">
                                            <span
                                                class="float-left mr-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >4</span
                                            >
                                            <span :data-segment-index="17" v-html="getHighlightedSegment(17)"></span>
                                            <input
                                                v-model="answers.q4"
                                                type="text"
                                                class="inline-block w-24 rounded-md border-2 border-blue-200 bg-blue-50 px-2 py-1 text-sm"
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border border-blue-300 p-3" :data-segment-index="18" v-html="getHighlightedSegment(18)"></td>
                                        <td class="border border-blue-300 p-3">
                                            <ul class="list-inside list-disc space-y-2">
                                                <li>
                                                    <span
                                                        class="float-left mr-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >5</span
                                                    >
                                                    <span :data-segment-index="19" v-html="getHighlightedSegment(19)"></span>
                                                    <input
                                                        v-model="answers.q5"
                                                        type="text"
                                                        class="inline-block w-24 rounded-md border-2 border-blue-200 bg-blue-50 px-2 py-1 text-sm"
                                                    />
                                                    <span :data-segment-index="20" v-html="getHighlightedSegment(20)"></span>
                                                </li>
                                                <li :data-segment-index="21" v-html="getHighlightedSegment(21)"></li>
                                            </ul>
                                        </td>
                                        <td class="border border-blue-300 p-3">
                                            <ul class="list-inside list-disc space-y-2">
                                                <li :data-segment-index="22" v-html="getHighlightedSegment(22)"></li>
                                                <li>
                                                    <span
                                                        class="float-left mr-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >6</span
                                                    >
                                                    <span :data-segment-index="23" v-html="getHighlightedSegment(23)"></span>
                                                    <input
                                                        v-model="answers.q6"
                                                        type="text"
                                                        class="inline-block w-24 rounded-md border-2 border-blue-200 bg-blue-50 px-2 py-1 text-sm"
                                                    />
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border border-blue-300 p-3" :data-segment-index="24" v-html="getHighlightedSegment(24)"></td>
                                        <td class="border border-blue-300 p-3">
                                            <span
                                                class="float-left mr-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >7</span
                                            >
                                            <span :data-segment-index="25" v-html="getHighlightedSegment(25)"></span>
                                            <input
                                                v-model="answers.q7"
                                                type="text"
                                                class="inline-block w-24 rounded-md border-2 border-blue-200 bg-blue-50 px-2 py-1 text-sm"
                                            />
                                        </td>
                                        <td class="border border-blue-300 p-3">
                                            <span
                                                class="float-left mr-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >8</span
                                            >
                                            <span :data-segment-index="26" v-html="getHighlightedSegment(26)"></span>
                                            <input
                                                v-model="answers.q8"
                                                type="text"
                                                class="inline-block w-24 rounded-md border-2 border-blue-200 bg-blue-50 px-2 py-1 text-sm"
                                            />
                                            <span :data-segment-index="27" v-html="getHighlightedSegment(27)"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border border-blue-300 p-3" :data-segment-index="28" v-html="getHighlightedSegment(28)"></td>
                                        <td class="border border-blue-300 p-3">
                                            <ul class="list-inside list-disc space-y-2">
                                                <li :data-segment-index="29" v-html="getHighlightedSegment(29)"></li>
                                                <li>
                                                    <span
                                                        class="float-left mr-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >9</span
                                                    >
                                                    <span :data-segment-index="30" v-html="getHighlightedSegment(30)"></span>
                                                    <input
                                                        v-model="answers.q9"
                                                        type="text"
                                                        class="inline-block w-24 rounded-md border-2 border-blue-200 bg-blue-50 px-2 py-1 text-sm"
                                                    />
                                                </li>
                                            </ul>
                                        </td>
                                        <td class="border border-blue-300 p-3">
                                            <span
                                                class="float-left mr-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 font-semibold text-white shadow-lg"
                                                style="
                                                    box-shadow:
                                                        0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                "
                                                >10</span
                                            >
                                            <span :data-segment-index="31" v-html="getHighlightedSegment(31)"></span>
                                            <input
                                                v-model="answers.q10"
                                                type="text"
                                                class="inline-block w-24 rounded-md border-2 border-blue-200 bg-blue-50 px-2 py-1 text-sm"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
        <!--        <div class="mt-4 rounded-lg border border-purple-200 bg-purple-50 p-4">-->
        <!--            <div class="text-base sm:text-lg flex flex-center justify-center ">-->
        <!--                <img src="/images/listening/IELTS011.png">-->
        <!--            </div>-->
        <!--        </div>-->
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
