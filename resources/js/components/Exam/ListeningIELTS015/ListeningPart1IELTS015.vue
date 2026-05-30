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

const textSegments = ref([
    { text: 'Section 1', offset: 0 },
    { text: 'Questions 1-10', offset: 10 },
    { text: 'Complete the form below using', offset: 25 },
    { text: 'Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.', offset: 56 },
    { text: 'HOST FAMILY APPLICATION', offset: 125 },
    { text: 'Name:', offset: 150 },
    { text: 'Jenny Chan', offset: 156 },
    { text: 'Present address:', offset: 170 },
    { text: 'Sea View Guest House, ', offset: 187 },
    { text: 'Daytime phone number:', offset: 230 },
    { text: '2237676', offset: 252 },
    { text: '[NB Best time to contact is ', offset: 265 },
    { text: ']', offset: 295 },
    { text: 'Age:', offset: 315 },
    { text: '19', offset: 320 },
    { text: 'Intended length of stay:', offset: 325 },
    { text: 'Occupation while in UK:', offset: 375 },
    { text: 'student', offset: 399 },
    { text: 'General level of English:', offset: 410 },
    { text: 'Preferred location:', offset: 455 },
    { text: 'in the ', offset: 475 },
    { text: 'Special diet:', offset: 505 },
    { text: 'Other requirements:', offset: 540 },
    { text: 'own facilities', offset: 560 },
    { text: 'own television', offset: 578 },
    { text: '• to be ', offset: 620 },
    { text: 'Maximum price:', offset: 650 },
    { text: '£ ', offset: 665 },
    { text: ' a week', offset: 670 },
    { text: 'Preferred starting date:', offset: 695 },
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="mx-auto px-2 py-4 pb-32 sm:px-4">
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
                <div class="space-y-6 bg-blue-50 p-4 sm:p-6 lg:p-8">
                    <div class="rounded-2xl border-2 border-blue-200 bg-white p-6 shadow-lg">
                        <div class="mb-6 text-center">
                            <p
                                class="text-sm font-semibold tracking-wider text-blue-700 uppercase"
                                :data-segment-text="'Complete the form below using'"
                                v-html="getHighlightedSegment('Complete the form below using')"
                            ></p>
                            <p
                                class="text-sm font-bold tracking-wider text-blue-800 uppercase"
                                :data-segment-text="'Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.'"
                                v-html="getHighlightedSegment('Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.')"
                            ></p>
                        </div>
                        <h2
                            class="mb-8 text-center text-2xl font-bold tracking-wider text-blue-900"
                            :data-segment-text="'HOST FAMILY APPLICATION'"
                            v-html="getHighlightedSegment('HOST FAMILY APPLICATION')"
                        ></h2>

                        <div class="mx-auto max-w-2xl space-y-5 text-base text-gray-800">
                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Name:'"
                                    v-html="getHighlightedSegment('Name:')"
                                ></label>
                                <span
                                    class="flex-1 rounded-md bg-gray-100 p-2"
                                    :data-segment-text="'Jenny Chan'"
                                    v-html="getHighlightedSegment('Jenny Chan')"
                                ></span>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Present address:'"
                                    v-html="getHighlightedSegment('Present address:')"
                                ></label>
                                <div class="inline-flex flex-1 flex-wrap items-center gap-2">
                                    <span
                                        :data-segment-text="'Sea View Guest House, '"
                                        v-html="getHighlightedSegment('Sea View Guest House, ')"
                                    ></span>
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >1</span
                                    >
                                    <input
                                        v-model="answers.q1"
                                        type="text"
                                        class="w-48 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Daytime phone number:'"
                                    v-html="getHighlightedSegment('Daytime phone number:')"
                                ></label>
                                <span
                                    class="flex-1 rounded-md bg-gray-100 p-2"
                                    :data-segment-text="'2237676'"
                                    v-html="getHighlightedSegment('2237676')"
                                ></span>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Best time to contact:'"
                                    v-html="getHighlightedSegment('Best time to contact:')"
                                ></label>
                                <div class="inline-flex flex-1 flex-wrap items-center gap-2">
                                    <span
                                        :data-segment-text="'[NB Best time to contact is '"
                                        v-html="getHighlightedSegment('[NB Best time to contact is ')"
                                    ></span>
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >2</span
                                    >
                                    <input
                                        v-model="answers.q2"
                                        type="text"
                                        class="w-48 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
                                    <span :data-segment-text="']'" v-html="getHighlightedSegment(']')"></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Age:'"
                                    v-html="getHighlightedSegment('Age:')"
                                ></label>
                                <span class="flex-1 rounded-md bg-gray-100 p-2" :data-segment-text="'19'" v-html="getHighlightedSegment('19')"></span>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Intended length of stay:'"
                                    v-html="getHighlightedSegment('Intended length of stay:')"
                                ></label>
                                <div class="inline-flex flex-1 flex-wrap items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >3</span
                                    >
                                    <input
                                        v-model="answers.q3"
                                        type="text"
                                        class="w-48 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Occupation while in UK:'"
                                    v-html="getHighlightedSegment('Occupation while in UK:')"
                                ></label>
                                <span
                                    class="flex-1 rounded-md bg-gray-100 p-2"
                                    :data-segment-text="'student'"
                                    v-html="getHighlightedSegment('student')"
                                ></span>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'General level of English:'"
                                    v-html="getHighlightedSegment('General level of English:')"
                                ></label>
                                <div class="inline-flex flex-1 flex-wrap items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >4</span
                                    >
                                    <input
                                        v-model="answers.q4"
                                        type="text"
                                        class="w-48 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Preferred location:'"
                                    v-html="getHighlightedSegment('Preferred location:')"
                                ></label>
                                <div class="inline-flex flex-1 flex-wrap items-center gap-2">
                                    <span :data-segment-text="'in the '" v-html="getHighlightedSegment('in the ')"></span>
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >5</span
                                    >
                                    <input
                                        v-model="answers.q5"
                                        type="text"
                                        class="w-48 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Special diet:'"
                                    v-html="getHighlightedSegment('Special diet:')"
                                ></label>
                                <div class="inline-flex flex-1 flex-wrap items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >6</span
                                    >
                                    <input
                                        v-model="answers.q6"
                                        type="text"
                                        class="w-48 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <label
                                    class="w-48 pt-2 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Other requirements:'"
                                    v-html="getHighlightedSegment('Other requirements:')"
                                ></label>
                                <div class="flex-1 space-y-2">
                                    <p
                                        class="rounded-md bg-gray-100 p-2"
                                        :data-segment-text="'own facilities'"
                                        v-html="getHighlightedSegment('own facilities')"
                                    ></p>
                                    <p
                                        class="rounded-md bg-gray-100 p-2"
                                        :data-segment-text="'own television'"
                                        v-html="getHighlightedSegment('own television')"
                                    ></p>
                                    <div class="inline-flex items-center gap-2">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                            >7</span
                                        >
                                        <input
                                            v-model="answers.q7"
                                            type="text"
                                            class="w-48 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                        />
                                    </div>
                                    <div class="inline-flex items-center gap-2">
                                        <span :data-segment-text="'• to be '" v-html="getHighlightedSegment('• to be ')"></span>
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                            style="
                                                box-shadow:
                                                    0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                            "
                                            >8</span
                                        >
                                        <input
                                            v-model="answers.q8"
                                            type="text"
                                            class="w-48 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Maximum price:'"
                                    v-html="getHighlightedSegment('Maximum price:')"
                                ></label>
                                <div class="inline-flex flex-1 flex-wrap items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >9</span
                                    >
                                    <span :data-segment-text="'£ '" v-html="getHighlightedSegment('£ ')"></span>
                                    <input
                                        v-model="answers.q9"
                                        type="text"
                                        class="w-40 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
                                    <span :data-segment-text="' a week'" v-html="getHighlightedSegment(' a week')"></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <label
                                    class="w-48 text-right font-semibold text-gray-600"
                                    :data-segment-text="'Preferred starting date:'"
                                    v-html="getHighlightedSegment('Preferred starting date:')"
                                ></label>
                                <div class="inline-flex flex-1 flex-wrap items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-lg"
                                        style="
                                            box-shadow:
                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                        "
                                        >10</span
                                    >
                                    <input
                                        v-model="answers.q10"
                                        type="text"
                                        class="w-48 rounded-md border-2 border-blue-300 bg-blue-50 px-3 py-1 text-base focus:border-blue-500 focus:bg-white focus:outline-none"
                                    />
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
