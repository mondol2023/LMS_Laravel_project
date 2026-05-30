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
    { text: 'QUESTIONS 31-40', offset: 0 },
    { text: 'Complete the notes below.', offset: 15 },
    { text: 'Write ONE WORD ONLY for each answer.', offset: 41 },
    { text: 'SEMINAR ON ROCK ART', offset: 79 },
    { text: 'Preparation for fieldwork trip to Namibia in', offset: 100 },
    { text: 'Rock art in Namibia may be', offset: 143 },
    { text: 'Paintings', offset: 171 },
    { text: 'Engravings', offset: 183 },
    { text: 'Earliest explanation of engravings of animal footprints', offset: 196 },
    { text: 'They were used to help', offset: 251 },
    { text: 'learn about tracking.', offset: 274 },
    { text: 'But:', offset: 295 },
    { text: 'Why are the tracks usually', offset: 300 },
    { text: '?', offset: 330 },
    { text: 'Why are some engravings realistic and other realistic?', offset: 331 },
    { text: 'Why are the unrealistic animals sometimes half', offset: 386 },
    { text: '?', offset: 432 },
    { text: 'More recent explanation', offset: 433 },
    { text: 'Wise men may have been trying to control wild animals with', offset: 458 },
    { text: 'Comment:', offset: 518 },
    { text: 'Earlier explanation was due to scholars over-generalising from their experience of a different culture.', offset: 527 },
    { text: 'Questions 36-40', offset: 628 },
    { text: 'Complete the sentences below. Write ONE WORD ONLY for each answer.', offset: 644 },
    { text: 'If you look at a site from a', offset: 713 },
    { text: ', you reduce visitor pressure.', offset: 741 },
    { text: 'To camp on a site may be disrespectful to people from that', offset: 772 },
    { text: '.', offset: 836 },
    { text: 'Undiscovered material may be damaged by', offset: 837 },
    { text: '.', offset: 880 },
    { text: 'You should avoid', offset: 881 },
    { text: 'or tracing rock art as it is so fragile.', offset: 900 },
    { text: 'In general, your aim is to leave the site', offset: 941 },
    { text: '.', offset: 984 },
]);

const sentence36_40 = ref([
    {
        num: 36,
        key: 'q36' as keyof typeof answers.value,
        part1: 'If you look at a site from a',
        part2: ', you reduce visitor pressure.',
    },
    {
        num: 37,
        key: 'q37' as keyof typeof answers.value,
        part1: 'To camp on a site may be disrespectful to people from that',
        part2: '.',
    },
    {
        num: 38,
        key: 'q38' as keyof typeof answers.value,
        part1: 'Undiscovered material may be damaged by',
        part2: '.',
    },
    {
        num: 39,
        key: 'q39' as keyof typeof answers.value,
        part1: 'You should avoid',
        part2: 'or tracing rock art as it is so fragile.',
    },
    {
        num: 40,
        key: 'q40' as keyof typeof answers.value,
        part1: 'In general, your aim is to leave the site',
        part2: '.',
    },
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
    autoSaver.value = draftService.createAutoSaver(userPhone, examId, 'listening', 'part4');

    // Load saved drafts
    try {
        const draftsResponse = await autoSaver.value.getDrafts();
        if (draftsResponse.success && draftsResponse.data.part4) {
            // Load single answers
            Object.keys(answers.value).forEach((key) => {
                if (draftsResponse.data.part4[key]) {
                    answers.value[key as keyof typeof answers.value] = draftsResponse.data.part4[key];
                }
            });
            console.log('Loaded Listening Part 4 drafts');
        }
    } catch (error) {
        console.error('Failed to load Listening Part 4 drafts:', error);
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
    return { ...answers.value };
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
                            <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase sm:text-base">
                                <span :data-segment-text="'QUESTIONS 31-40'" v-html="getHighlightedSegment('QUESTIONS 31-40')"></span>
                            </p>
                            <p class="text-sm text-gray-600">
                                <span
                                    :data-segment-text="'Complete the notes below.'"
                                    v-html="getHighlightedSegment('Complete the notes below.')"
                                ></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrollable Questions Content -->
            <div class="flex-1 overflow-y-auto pb-32">
                <div class="p-2 sm:p-3 md:p-4 lg:p-6">
                    <section id="question-31-35" class="space-y-4">
                        <div class="rounded-xl border-2 border-orange-300 bg-gradient-to-br from-orange-50 to-red-50 p-4 shadow-lg sm:p-6 md:p-8">
                            <div class="mb-4 sm:mb-6">
                                <h3 class="bg-gradient-to-r from-orange-500 to-red-600 bg-clip-text text-xl font-bold text-transparent md:text-2xl">
                                    <span :data-segment-text="'SEMINAR ON ROCK ART'" v-html="getHighlightedSegment('SEMINAR ON ROCK ART')"></span>
                                </h3>
                                <p class="mt-2 text-base font-medium text-gray-800 sm:text-lg">
                                    <span
                                        :data-segment-text="'Complete the notes below.'"
                                        v-html="getHighlightedSegment('Complete the notes below.')"
                                    ></span>
                                    <strong
                                        class="text-orange-600"
                                        :data-segment-text="'Write ONE WORD ONLY for each answer.'"
                                        v-html="getHighlightedSegment('Write ONE WORD ONLY for each answer.')"
                                    ></strong>
                                </p>
                            </div>

                            <div class="space-y-6 text-gray-800">
                                <div id="question-31" class="">
                                    <p class="flex flex-wrap items-center gap-2">
                                        <span
                                            :data-segment-text="'Preparation for fieldwork trip to Namibia in'"
                                            v-html="getHighlightedSegment('Preparation for fieldwork trip to Namibia in')"
                                        ></span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-md ring-2 ring-white"
                                            >31</span
                                        >
                                        <input
                                            v-model="answers.q31"
                                            type="text"
                                            class="inline-block w-48 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        />
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="font-semibold"
                                        :data-segment-text="'Rock art in Namibia may be'"
                                        v-html="getHighlightedSegment('Rock art in Namibia may be')"
                                    ></p>
                                    <ul class="ml-6 list-disc space-y-1 pt-2">
                                        <li><span :data-segment-text="'Paintings'" v-html="getHighlightedSegment('Paintings')"></span></li>
                                        <li><span :data-segment-text="'Engravings'" v-html="getHighlightedSegment('Engravings')"></span></li>
                                    </ul>
                                </div>

                                <div class="">
                                    <p
                                        class="font-bold"
                                        :data-segment-text="'Earliest explanation of engravings of animal footprints'"
                                        v-html="getHighlightedSegment('Earliest explanation of engravings of animal footprints')"
                                    ></p>
                                    <p id="question-32" class="mt-2 flex flex-wrap items-center gap-2">
                                        <span
                                            :data-segment-text="'They were used to help'"
                                            v-html="getHighlightedSegment('They were used to help')"
                                        ></span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-md ring-2 ring-white"
                                            >32</span
                                        >
                                        <input
                                            v-model="answers.q32"
                                            type="text"
                                            class="inline-block w-48 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        />
                                        <span
                                            :data-segment-text="'learn about tracking.'"
                                            v-html="getHighlightedSegment('learn about tracking.')"
                                        ></span>
                                    </p>
                                </div>

                                <div>
                                    <p class="font-semibold" :data-segment-text="'But:'" v-html="getHighlightedSegment('But:')"></p>
                                    <ul class="ml-6 list-disc space-y-2 pt-2">
                                        <li id="question-33" class="flex flex-wrap items-center gap-2">
                                            <span
                                                :data-segment-text="'Why are the tracks usually'"
                                                v-html="getHighlightedSegment('Why are the tracks usually')"
                                            ></span>
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-md ring-2 ring-white"
                                                >33</span
                                            >
                                            <input
                                                v-model="answers.q33"
                                                type="text"
                                                class="inline-block w-48 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                            />
                                            <span :data-segment-text="'?'" v-html="getHighlightedSegment('?')"></span>
                                        </li>
                                        <li>
                                            <span
                                                :data-segment-text="'Why are some engravings realistic and other realistic?'"
                                                v-html="getHighlightedSegment('Why are some engravings realistic and other realistic?')"
                                            ></span>
                                        </li>
                                        <li id="question-34" class="flex flex-wrap items-center gap-2">
                                            <span
                                                :data-segment-text="'Why are the unrealistic animals sometimes half'"
                                                v-html="getHighlightedSegment('Why are the unrealistic animals sometimes half')"
                                            ></span>
                                            <span
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-md ring-2 ring-white"
                                                >34</span
                                            >
                                            <input
                                                v-model="answers.q34"
                                                type="text"
                                                class="inline-block w-48 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                            />
                                            <span :data-segment-text="'?'" v-html="getHighlightedSegment('?')"></span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="">
                                    <p
                                        class="font-bold"
                                        :data-segment-text="'More recent explanation'"
                                        v-html="getHighlightedSegment('More recent explanation')"
                                    ></p>
                                    <p id="question-35" class="mt-2 flex flex-wrap items-center gap-2">
                                        <span
                                            :data-segment-text="'Wise men may have been trying to control wild animals with'"
                                            v-html="getHighlightedSegment('Wise men may have been trying to control wild animals with')"
                                        ></span>
                                        <span
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 font-bold text-white shadow-md ring-2 ring-white"
                                            >35</span
                                        >
                                        <input
                                            v-model="answers.q35"
                                            type="text"
                                            class="inline-block w-48 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        />
                                    </p>
                                </div>

                                <div>
                                    <p class="font-semibold" :data-segment-text="'Comment:'" v-html="getHighlightedSegment('Comment:')"></p>
                                    <p
                                        class="pt-2"
                                        :data-segment-text="'Earlier explanation was due to scholars over-generalising from their experience of a different culture.'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Earlier explanation was due to scholars over-generalising from their experience of a different culture.',
                                            )
                                        "
                                    ></p>
                                </div>
                            </div>
                        </div>
                    </section>
                    <section id="question-36-40" class="my-8 space-y-3 sm:space-y-4">
                        <div class="rounded-xl border-2 border-orange-300 bg-gradient-to-br from-orange-50 to-red-50 p-4 shadow-lg sm:p-6 md:p-8">
                            <div class="mb-4 flex items-center space-x-3">
                                <div
                                    class="flex h-10 w-16 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-orange-600 to-red-600 shadow-[0_4px_10px_rgba(0,0,0,0.2)]"
                                >
                                    <span class="text-base font-bold text-white">36–40</span>
                                </div>
                                <h3 class="bg-gradient-to-r from-orange-700 to-red-700 bg-clip-text text-xl font-bold text-transparent">
                                    <span :data-segment-text="'Questions 36-40'" v-html="getHighlightedSegment('Questions 36-40')"></span>
                                </h3>
                            </div>
                            <p class="mb-4 text-base font-medium text-gray-800 sm:text-lg">
                                <span
                                    :data-segment-text="'Complete the sentences below. Write ONE WORD ONLY for each answer.'"
                                    v-html="getHighlightedSegment('Complete the sentences below. Write ONE WORD ONLY for each answer.')"
                                ></span>
                            </p>
                            <div class="space-y-4">
                                <div
                                    v-for="q in sentence36_40"
                                    :key="q.num"
                                    :id="`question-${q.num}`"
                                    class="rounded-2xl border-l-4 border-orange-400 bg-white p-4 shadow-lg"
                                >
                                    <p class="text-base font-semibold text-gray-800 sm:text-lg">
                                        <span
                                            class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full border bg-orange-500 p-4.5 text-white shadow-xl ring-2 ring-white"
                                            >{{ q.num }}</span
                                        >
                                        <span :data-segment-text="q.part1" v-html="getHighlightedSegment(q.part1)"></span>
                                        <input
                                            v-model="answers[q.key]"
                                            type="text"
                                            placeholder=""
                                            class="mx-1 inline-block w-48 rounded-md border border-orange-300 p-2 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        />
                                        <span v-if="q.part2" :data-segment-text="q.part2" v-html="getHighlightedSegment(q.part2)"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
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
