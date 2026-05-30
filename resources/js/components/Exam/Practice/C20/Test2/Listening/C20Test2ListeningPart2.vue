<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
interface Props {}

const props = withDefaults(defineProps<Props>(), {
    phone: '',
    examId: '',
});

// Listening Part 2 component

// Single choice answers for questions 11-20
const answers = ref({
    q11: '',
    q12: '',
    q13: '',
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
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
    { text: 'Questions 11-16', offset: 0 },
    { text: 'What is the role of the volunteers in each of the following activities?', offset: 18 },
    { text: 'Choose SIX answers from the box and write the correct letter, A-I, next to 11-16.', offset: 95 },
    { text: 'providing entertainment', offset: 180 },
    { text: 'providing publicity about a council service', offset: 205 },
    { text: 'contacting local businesses', offset: 255 },
    { text: 'giving advice to visitors', offset: 285 },
    { text: 'collecting feedback on events', offset: 315 },
    { text: 'selling tickets', offset: 348 },
    { text: 'introducing guest speakers at an event', offset: 368 },
    { text: 'encouraging cooperation between local organisations', offset: 415 },
    { text: 'helping people find their seats', offset: 475 },

    { text: 'walking around the town centre', offset: 510 },
    { text: 'helping at concerts', offset: 545 },
    { text: 'getting involved with community groups', offset: 568 },
    { text: 'helping with a magazine', offset: 610 },
    { text: 'participating at lunches for retired people', offset: 638 },
    { text: 'helping with the website', offset: 685 },

    { text: 'Questions 17-20', offset: 715 },
    { text: 'Choose the correct letter, A, B or C.', offset: 733 },

    { text: 'Which event requires the largest number of volunteers?', offset: 775 },
    { text: 'the music festival', offset: 835 },
    { text: 'the science festival', offset: 858 },
    { text: 'the book festival', offset: 882 },

    { text: 'What is the most important requirement for volunteers at the festivals?', offset: 905 },
    { text: 'interpersonal skills', offset: 980 },
    { text: 'personal interest in the event', offset: 1005 },
    { text: 'flexibility', offset: 1040 },

    { text: 'New volunteers will start working in the week beginning', offset: 1055 },
    { text: '2 September.', offset: 1115 },
    { text: '9 September.', offset: 1132 },
    { text: '23 September.', offset: 1150 },

    { text: 'What is the next annual event for volunteers?', offset: 1170 },
    { text: 'a boat trip', offset: 1220 },
    { text: 'a barbecue', offset: 1238 },
    { text: 'a party', offset: 1255 },
]);

const boxOptions = [
    { label: 'A', text: 'providing entertainment' },
    { label: 'B', text: 'providing publicity about a council service' },
    { label: 'C', text: 'contacting local businesses' },
    { label: 'D', text: 'giving advice to visitors' },
    { label: 'E', text: 'collecting feedback on events' },
    { label: 'F', text: 'selling tickets' },
    { label: 'G', text: 'introducing guest speakers at an event' },
    { label: 'H', text: 'encouraging cooperation between local organisations' },
    { label: 'I', text: 'helping people find their seats' },
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
        <div class="mb-10 flex min-h-screen w-full flex-col rounded-2xl bg-white/80 shadow-2xl backdrop-blur-lg">
            <!-- Questions Header -->
            <div class="flex-shrink-0 border-b-2 border-dashed border-gray-200 p-4 lg:p-6">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-purple-600 to-pink-500 text-white shadow-lg"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <h2
                            class="text-lg font-bold text-purple-800"
                            :data-segment-text="'Questions 11-16'"
                            v-html="getHighlightedSegment('Questions 11-16')"
                        ></h2>
                        <p
                            class="text-sm text-gray-600"
                            :data-segment-text="'What is the role of the volunteers in each of the following activities?'"
                            v-html="getHighlightedSegment('What is the role of the volunteers in each of the following activities?')"
                        ></p>
                    </div>
                </div>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto bg-pink-50/50 p-4 lg:p-6">
                <div class="space-y-10">
                    <!-- Box with options -->
                    <div class="rounded-2xl border border-purple-200 bg-white p-6 shadow-lg">
                        <p
                            class="mb-4 font-semibold text-purple-700"
                            :data-segment-text="'Choose SIX answers from the box and write the correct letter, A-I, next to 11-16.'"
                            v-html="getHighlightedSegment('Choose SIX answers from the box and write the correct letter, A-I, next to 11-16.')"
                        ></p>
                        <div class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="option in boxOptions" :key="option.label" class="flex items-start">
                                <span class="mr-3 font-bold text-purple-600">{{ option.label }}</span>
                                <span class="text-gray-700" :data-segment-text="option.text" v-html="getHighlightedSegment(option.text)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Questions 11-16 with Selects -->
                    <div class="space-y-6">
                        <div
                            v-for="i in 6"
                            :key="i"
                            :id="`question-${10 + i}`"
                            class="flex items-center justify-between rounded-xl bg-white p-4 shadow-md transition-shadow hover:shadow-xl"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-lg font-bold text-white shadow-md"
                                    style="box-shadow: 3px 3px 8px rgba(192, 38, 211, 0.4)"
                                >
                                    {{ 10 + i }}
                                </div>
                                <p
                                    class="text-gray-800"
                                    :data-segment-text="textSegments.find((s) => s.offset === [510, 545, 568, 610, 638, 685][i - 1])?.text"
                                    v-html="
                                        getHighlightedSegment(
                                            textSegments.find((s) => s.offset === [510, 545, 568, 610, 638, 685][i - 1])?.text || '',
                                        )
                                    "
                                ></p>
                            </div>
                            <select
                                v-model="answers['q' + (10 + i)]"
                                class="w-24 rounded-lg border-2 border-purple-300 bg-white text-center font-bold text-purple-800 shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500/50"
                            >
                                <option disabled value="">Select</option>
                                <option v-for="opt in boxOptions.map((o) => o.label)" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Questions 17-20 with Radios -->
                    <div class="pt-8">
                        <div class="mb-6 flex items-center space-x-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-purple-600 to-pink-500 text-white shadow-lg"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <h2
                                    class="text-lg font-bold text-purple-800"
                                    :data-segment-text="'Questions 17-20'"
                                    v-html="getHighlightedSegment('Questions 17-20')"
                                ></h2>
                                <p
                                    class="text-sm text-gray-600"
                                    :data-segment-text="'Choose the correct letter, A, B or C.'"
                                    v-html="getHighlightedSegment('Choose the correct letter, A, B or C.')"
                                ></p>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div
                                id="question-17"
                                class="transform rounded-2xl border-2 border-purple-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-purple-300 hover:shadow-2xl"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-lg font-bold text-white shadow-md"
                                        style="box-shadow: 3px 3px 8px rgba(192, 38, 211, 0.4)"
                                    >
                                        17
                                    </div>
                                    <div class="w-full">
                                        <p
                                            class="mb-4 font-semibold text-gray-800"
                                            :data-segment-text="'Which event requires the largest number of volunteers?'"
                                            v-html="getHighlightedSegment('Which event requires the largest number of volunteers?')"
                                        ></p>
                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q17"
                                                    value="A"
                                                    v-model="answers.q17"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'the music festival'"
                                                    v-html="getHighlightedSegment('the music festival')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q17"
                                                    value="B"
                                                    v-model="answers.q17"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'the science festival'"
                                                    v-html="getHighlightedSegment('the science festival')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q17"
                                                    value="C"
                                                    v-model="answers.q17"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'the book festival'"
                                                    v-html="getHighlightedSegment('the book festival')"
                                                ></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                id="question-18"
                                class="transform rounded-2xl border-2 border-purple-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-purple-300 hover:shadow-2xl"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-lg font-bold text-white shadow-md"
                                        style="box-shadow: 3px 3px 8px rgba(192, 38, 211, 0.4)"
                                    >
                                        18
                                    </div>
                                    <div class="w-full">
                                        <p
                                            class="mb-4 font-semibold text-gray-800"
                                            :data-segment-text="'What is the most important requirement for volunteers at the festivals?'"
                                            v-html="getHighlightedSegment('What is the most important requirement for volunteers at the festivals?')"
                                        ></p>
                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q18"
                                                    value="A"
                                                    v-model="answers.q18"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'interpersonal skills'"
                                                    v-html="getHighlightedSegment('interpersonal skills')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q18"
                                                    value="B"
                                                    v-model="answers.q18"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'personal interest in the event'"
                                                    v-html="getHighlightedSegment('personal interest in the event')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q18"
                                                    value="C"
                                                    v-model="answers.q18"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'flexibility'"
                                                    v-html="getHighlightedSegment('flexibility')"
                                                ></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                id="question-19"
                                class="transform rounded-2xl border-2 border-purple-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-purple-300 hover:shadow-2xl"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-lg font-bold text-white shadow-md"
                                        style="box-shadow: 3px 3px 8px rgba(192, 38, 211, 0.4)"
                                    >
                                        19
                                    </div>
                                    <div class="w-full">
                                        <p
                                            class="mb-4 font-semibold text-gray-800"
                                            :data-segment-text="'New volunteers will start working in the week beginning'"
                                            v-html="getHighlightedSegment('New volunteers will start working in the week beginning')"
                                        ></p>
                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q19"
                                                    value="A"
                                                    v-model="answers.q19"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'2 September.'"
                                                    v-html="getHighlightedSegment('2 September.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q19"
                                                    value="B"
                                                    v-model="answers.q19"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'9 September.'"
                                                    v-html="getHighlightedSegment('9 September.')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q19"
                                                    value="C"
                                                    v-model="answers.q19"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'23 September.'"
                                                    v-html="getHighlightedSegment('23 September.')"
                                                ></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                id="question-20"
                                class="transform rounded-2xl border-2 border-purple-200 bg-white p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-purple-300 hover:shadow-2xl"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-purple-600 text-lg font-bold text-white shadow-md"
                                        style="box-shadow: 3px 3px 8px rgba(192, 38, 211, 0.4)"
                                    >
                                        20
                                    </div>
                                    <div class="w-full">
                                        <p
                                            class="mb-4 font-semibold text-gray-800"
                                            :data-segment-text="'What is the next annual event for volunteers?'"
                                            v-html="getHighlightedSegment('What is the next annual event for volunteers?')"
                                        ></p>
                                        <div class="space-y-3">
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q20"
                                                    value="A"
                                                    v-model="answers.q20"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'a boat trip'"
                                                    v-html="getHighlightedSegment('a boat trip')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q20"
                                                    value="B"
                                                    v-model="answers.q20"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'a barbecue'"
                                                    v-html="getHighlightedSegment('a barbecue')"
                                                ></span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-purple-100/50"
                                            >
                                                <input
                                                    type="radio"
                                                    name="q20"
                                                    value="C"
                                                    v-model="answers.q20"
                                                    class="h-5 w-5 border-gray-300 text-purple-600 focus:ring-purple-500"
                                                />
                                                <span
                                                    class="text-gray-700"
                                                    :data-segment-text="'a party'"
                                                    v-html="getHighlightedSegment('a party')"
                                                ></span>
                                            </label>
                                        </div>
                                    </div>
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
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(168, 85, 247, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(168, 85, 247, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(168, 85, 247, 0.1);
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
    background: linear-gradient(to bottom, #a855f7, #ec4899);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #9333ea, #db2777);
}
</style>
