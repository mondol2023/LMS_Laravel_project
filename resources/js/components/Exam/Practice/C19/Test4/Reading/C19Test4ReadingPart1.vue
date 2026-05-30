<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Reading Part 1 component

interface Props {}

const props = withDefaults(defineProps<Props>(), {});

// Define emits
const emit = defineEmits<{
    notesChange: [
        notes: Array<{
            id: number;
            text: string;
            selectedText: string;
            note: string;
            start: number;
            end: number;
        }>,
    ];
}>();

// Draft auto-saver

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-c19t4-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<
    Array<{
        id: number;
        text: string;
        selectedText: string;
        note: string;
        start: number;
        end: number;
    }>
>([]);

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
    q11: '',
    q12: '',
    q13: '',
});

const passageText = `According to conservationists, populations of around two thirds of butterfly species have declined in Britain over the past 40 years. If this trend continues, it might have unpredictable knock-on effects for other species in the ecosystem. Butterfly eggs develop into caterpillars and these insects, which are the second stage in a new butterfly\u2019s lifecycle, consume vast quantities of plant material, and in turn act as prey for birds as well as bats and other small mammals. Only by arming themselves with an understanding of why butterfly numbers are down can conservationists hope to halt or reverse the decline.

Butterflies prefer outdoor conditions to be \u2018just right\u2019, which means neither too hot nor too cold. Under the conditions of climate change, the temperature at any given time in summer is generally getting warmer, leaving butterflies with the challenge of how to deal with this. One of the main ways in which species are ensuring conditions suit them is by changing the time of year at which they are active and reproduce. Scientists refer to the timing of such lifecycle events as \u2018phenology\u2019, so when an animal or plant starts to do something earlier in the year than it usually does, it is said to be \u2018advancing its phenology\u2019.

These advances have been observed already in a wide range of butterflies \u2013 indeed, most species are advancing their phenology to some extent. In Britain, as the average spring temperature has increased by roughly 0.5\u00B0C over the past 20 years, species have advanced by between three days and a week on average, to keep in line with cooler temperatures. Is this a sign that butterflies are well equipped to cope with climate change, and readily adjust to new temperatures? Or are these populations under stress, being dragged along unwillingly by unnaturally fast changes? The answer is still unknown, but a new study is seeking to answer these questions.

First, the researchers pulled together data from millions of records that had been submitted by butterfly enthusiasts \u2013 people who spend their free time observing the activities of different species. This provided information on 130 species of butterflies in Great Britain every year for a 20-year period. They then estimated the abundance and distribution of each species across this time, along with how far north in the country they had moved. The data also, crucially, allowed researchers to estimate subtle changes in what time of the year each species was changing into an adult butterfly.

Analyzing the trends in each variable, the researchers discovered that species with more flexible lifecycles were more likely to be able to benefit from an earlier emergence driven by climate change. Some species are able to go from caterpillar to butterfly twice or more per year, so that the individual butterflies you see flying in the spring are the grandchildren or great-grandchildren of the individuals seen a year previously.

Among these species, researchers observed that those which have been advancing their phenology the most over the 20-year study period also had the most positive trends in abundance, distribution and northwards extent. For these species, such as Britain\u2019s tiniest butterfly, the dainty Small Blue, whose colonies are up to a hundred strong, some develop into butterflies early in spring, allowing their summer generations to complete another reproductive cycle by autumn so that more population growth occurs.

Other species, however, are less flexible and restricted to a single reproductive cycle per year. For these species, there was no evidence of any benefit to emerging earlier. Indeed, worryingly, it was found that the species in this group that specialize in very specific habitat types, often related to the caterpillar\u2019s preferred diet, actually tended to be most at harm from advancing phenology. The beautiful High Brown Fritillary, often described as Britain\u2019s most endangered butterfly, is in this group. It is found only in coppiced woodland and limestone pavement habitats. It is also a single-generation butterfly that has advanced its phenology. This suggests that climate change, while undoubtedly not the sole cause, might have played a part in the downfall of this species.

All is not lost, however. Many of Britain\u2019s single-generation species show the capacity, in continental Europe, to add a second generation in years that are sufficiently warm. Therefore, as the climate continues to warm, species like the Silver-studded Blue might be able to switch to multiple generations in the UK as well, and so begin to extract benefits from the additional warmth, potentially leading to population increases.

More immediately, conservationists can arm themselves with all this knowledge to spot the warning signs of species that may be at risk. The White Admiral of southern England, a much sought-after butterfly, experienced a significant increase in numbers from the 1920s but has shown a considerable decline in the past 20 years. This may be because the caterpillar exists solely on a diet of a plant called honeysuckle. But it is also likely to be due to climate change.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 1', offset: 5000 },
    { text: 'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.', offset: 5018 },
    { text: 'The impact of climate change on butterflies in Britain', offset: 5114 },
    { text: 'Questions 1-6', offset: 5169 },
    { text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: 5183 },
    { text: 'In boxes 1-6 on your answer sheet, write', offset: 5265 },
    { text: 'TRUE', offset: 5307 },
    { text: 'if the statement agrees with the information', offset: 5312 },
    { text: 'FALSE', offset: 5357 },
    { text: 'if the statement contradicts the information', offset: 5363 },
    { text: 'NOT GIVEN', offset: 5408 },
    { text: 'if there is no information on this', offset: 5418 },
    { text: 'Forty years ago, there were fewer butterflies in Britain than at present.', offset: 5452 },
    { text: 'Caterpillars are eaten by a number of different predators.', offset: 5524 },
    { text: '\u2018Phenology\u2019 is a term used to describe a creature\u2019s ability to alter the location of a lifecycle event.', offset: 5582 },
    { text: 'Some species of butterfly have a reduced lifespan due to spring temperature increases.', offset: 5686 },
    { text: 'There is a clear reason for the adaptations that butterflies are making to climate change.', offset: 5772 },
    { text: 'The data used in the study was taken from the work of amateur butterfly watchers.', offset: 5863 },
    { text: 'Questions 7-13', offset: 5944 },
    { text: 'Complete the notes below.', offset: 5959 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 5985 },
    { text: 'Write your answers in boxes 7-13 on your answer sheet.', offset: 6040 },
    { text: 'Butterflies in the UK', offset: 6095 },
    { text: 'The Small Blue', offset: 6117 },
    { text: 'lives in large ', offset: 6132 },
    { text: 'first appears at the start of ', offset: 6148 },
    { text: 'completes more than one reproductive cycle per year', offset: 6178 },
    { text: 'The High Brown Fritillary', offset: 6229 },
    { text: 'has one reproductive cycle', offset: 6254 },
    { text: 'is considered to be more ', offset: 6280 },
    { text: ' than other species', offset: 6305 },
    { text: 'its caterpillars occupy a limited range of ', offset: 6325 },
    { text: 'The Silver-studded Blue', offset: 6368 },
    { text: 'is already able to reproduce twice a year in warm areas of ', offset: 6391 },
    { text: 'The White Admiral', offset: 6451 },
    { text: 'is found in ', offset: 6469 },
    { text: ' areas of England', offset: 6482 },
    { text: 'both climate change and the ', offset: 6500 },
    { text: ' of the caterpillar are possible reasons for decline', offset: 6528 },
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

const handleContentTextSelect = (event: MouseEvent) => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        if (!range) return;

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

    // Position note input below the context menu
    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };

    showNoteInput.value = true;
    showContextMenu.value = false;

    // Focus the input after a small delay
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

    // Clear and close
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

const closeContextMenu = () => {
    showContextMenu.value = false;
};

const handleDeleteHighlight = (id: number) => {
    deleteHighlight(id);
};

const handleClickOutside = (event: MouseEvent) => {
    if (showContextMenu.value) {
        showContextMenu.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) {
        showContextMenu.value = false;
    }
};

// Resize handlers
const startResize = (event: MouseEvent) => {
    isResizing.value = true;
    event.preventDefault();
};

const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;

    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;

    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
    }
};

const stopResize = () => {
    isResizing.value = false;
};

// Watch notes and emit changes
watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

// Load saved answers when component mounts
onMounted(async () => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="min-h-screen overflow-y-auto">
        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div
                    class="passage-panel mb-4 max-h-screen overflow-y-auto rounded-r-lg bg-white shadow-lg lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">
                        <div class="flex-shrink-0 border-b border-gray-200 bg-white p-6">
                            <div class="mb-4 flex items-center space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600">
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-semibold tracking-wide text-gray-700 uppercase"
                                        :data-segment-text="'READING PASSAGE 1'"
                                        v-html="getHighlightedSegment('READING PASSAGE 1')"
                                    ></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p
                                        class="mb-1 text-base sm:text-lg"
                                        :data-segment-text="'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.'"
                                        v-html="
                                            getHighlightedSegment(
                                                'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.',
                                            )
                                        "
                                    ></p>
                                    <h2
                                        class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                    >
                                        <span
                                            :data-segment-text="'The impact of climate change on butterflies in Britain'"
                                            v-html="getHighlightedSegment('The impact of climate change on butterflies in Britain')"
                                        ></span>
                                    </h2>
                                </div>
                                <button
                                    class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200 sm:text-sm"
                                    title="Select text to highlight"
                                >
                                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 space-y-6 overflow-y-auto p-6">
                            <div ref="passageTextRef" class="space-y-6 text-base leading-relaxed select-text sm:text-lg">
                                <div class="rounded-lg border border-blue-100 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 shadow-md">
                                    <div
                                        class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700"
                                        :data-segment-text="passageText"
                                        v-html="getHighlightedSegment(passageText)"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-blue-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-blue-500"></div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col gap-1">
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-white shadow-lg"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">
                        <!-- Questions Header (Fixed) -->
                        <div class="flex-shrink-0 border-b border-gray-200 bg-white p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600">
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">QUESTIONS</p>
                                    <p class="text-xs text-gray-500">Answer all questions based on Reading Passage 1</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 1-6: TRUE/FALSE/NOT GIVEN -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-1-6" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 1-6'"
                                            v-html="getHighlightedSegment('Questions 1-6')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-blue-50 p-4 shadow-inner">
                                            <p
                                                class="mb-3 text-sm text-gray-800"
                                                :data-segment-text="'Do the following statements agree with the information given in Reading Passage 1?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the information given in Reading Passage 1?',
                                                    )
                                                "
                                            ></p>
                                            <div class="rounded-xl border border-purple-200 bg-white p-6 shadow-sm">
                                                <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                                    <span
                                                        :data-segment-text="'In boxes 1-6 on your answer sheet, write'"
                                                        v-html="getHighlightedSegment('In boxes 1-6 on your answer sheet, write')"
                                                    ></span>
                                                </p>
                                                <div class="grid grid-cols-1 gap-2 pt-4 text-base sm:text-lg">
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-24 rounded bg-purple-100 px-2 py-1 font-bold text-purple-700"
                                                            :data-segment-text="'TRUE'"
                                                            v-html="getHighlightedSegment('TRUE')"
                                                        ></span>
                                                        <span
                                                            :data-segment-text="'if the statement agrees with the information'"
                                                            class="text-gray-700"
                                                            v-html="getHighlightedSegment('if the statement agrees with the information')"
                                                        ></span>
                                                    </div>
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-24 rounded bg-red-100 px-2 py-1 font-bold text-red-700"
                                                            :data-segment-text="'FALSE'"
                                                            v-html="getHighlightedSegment('FALSE')"
                                                        ></span>
                                                        <span
                                                            :data-segment-text="'if the statement contradicts the information'"
                                                            class="text-gray-700"
                                                            v-html="getHighlightedSegment('if the statement contradicts the information')"
                                                        ></span>
                                                    </div>
                                                    <div class="flex items-center gap-4">
                                                        <span
                                                            class="w-28 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                            :data-segment-text="'NOT GIVEN'"
                                                            v-html="getHighlightedSegment('NOT GIVEN')"
                                                        ></span>
                                                        <span
                                                            :data-segment-text="'if there is no information on this'"
                                                            class="text-gray-700"
                                                            v-html="getHighlightedSegment('if there is no information on this')"
                                                        ></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Q1 -->
                                            <div
                                                id="question-1"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >1</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Forty years ago, there were fewer butterflies in Britain than at present.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Forty years ago, there were fewer butterflies in Britain than at present.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q1"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q2 -->
                                            <div
                                                id="question-2"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >2</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Caterpillars are eaten by a number of different predators.'"
                                                        v-html="getHighlightedSegment('Caterpillars are eaten by a number of different predators.')"
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q2"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q3 -->
                                            <div
                                                id="question-3"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >3</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'\u2018Phenology\u2019 is a term used to describe a creature\u2019s ability to alter the location of a lifecycle event.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                '\u2018Phenology\u2019 is a term used to describe a creature\u2019s ability to alter the location of a lifecycle event.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q3"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q4 -->
                                            <div
                                                id="question-4"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >4</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Some species of butterfly have a reduced lifespan due to spring temperature increases.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Some species of butterfly have a reduced lifespan due to spring temperature increases.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q4"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q5 -->
                                            <div
                                                id="question-5"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >5</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'There is a clear reason for the adaptations that butterflies are making to climate change.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'There is a clear reason for the adaptations that butterflies are making to climate change.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q5"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Q6 -->
                                            <div
                                                id="question-6"
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >6</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'The data used in the study was taken from the work of amateur butterfly watchers.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'The data used in the study was taken from the work of amateur butterfly watchers.',
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap justify-start gap-4 sm:gap-6">
                                                        <label
                                                            v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                            :key="option"
                                                            class="flex cursor-pointer items-center space-x-2"
                                                        >
                                                            <input
                                                                v-model="answers.q6"
                                                                type="radio"
                                                                :value="option"
                                                                class="form-radio h-5 w-5 text-blue-600 transition-colors duration-300 focus:ring-blue-500"
                                                            />
                                                            <span class="font-medium text-gray-700">{{ option }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 7-13: Note Completion -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-7-13" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 7-13'"
                                            v-html="getHighlightedSegment('Questions 7-13')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-blue-50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-sm text-gray-800"
                                                :data-segment-text="'Complete the notes below.'"
                                                v-html="getHighlightedSegment('Complete the notes below.')"
                                            ></p>
                                            <p
                                                class="text-sm font-semibold text-red-500"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-3 rounded-lg bg-white/50 p-4">
                                            <h4
                                                class="text-md font-bold text-blue-800"
                                                :data-segment-text="'Butterflies in the UK'"
                                                v-html="getHighlightedSegment('Butterflies in the UK')"
                                            ></h4>

                                            <!-- The Small Blue -->
                                            <div class="mt-4 rounded-lg p-3">
                                                <p class="font-semibold text-gray-700">
                                                    <span
                                                        :data-segment-text="'The Small Blue'"
                                                        v-html="getHighlightedSegment('The Small Blue')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Q7 -->
                                            <div id="question-7" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >7</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span class="mr-2">-</span>
                                                    <span
                                                        :data-segment-text="'lives in large '"
                                                        v-html="getHighlightedSegment('lives in large ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q7"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                </p>
                                            </div>

                                            <!-- Q8 -->
                                            <div id="question-8" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >8</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span class="mr-2">-</span>
                                                    <span
                                                        :data-segment-text="'first appears at the start of '"
                                                        v-html="getHighlightedSegment('first appears at the start of ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q8"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                </p>
                                            </div>

                                            <div class="ml-11 rounded-lg p-3">
                                                <p class="text-gray-700">
                                                    <span class="mr-2">-</span>
                                                    <span
                                                        :data-segment-text="'completes more than one reproductive cycle per year'"
                                                        v-html="getHighlightedSegment('completes more than one reproductive cycle per year')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- The High Brown Fritillary -->
                                            <div class="mt-4 rounded-lg p-3">
                                                <p class="font-semibold text-gray-700">
                                                    <span
                                                        :data-segment-text="'The High Brown Fritillary'"
                                                        v-html="getHighlightedSegment('The High Brown Fritillary')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <div class="ml-11 rounded-lg p-3">
                                                <p class="text-gray-700">
                                                    <span class="mr-2">-</span>
                                                    <span
                                                        :data-segment-text="'has one reproductive cycle'"
                                                        v-html="getHighlightedSegment('has one reproductive cycle')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Q9 -->
                                            <div id="question-9" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >9</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span class="mr-2">-</span>
                                                    <span
                                                        :data-segment-text="'is considered to be more '"
                                                        v-html="getHighlightedSegment('is considered to be more ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q9"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' than other species'"
                                                        v-html="getHighlightedSegment(' than other species')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Q10 -->
                                            <div id="question-10" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >10</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span class="mr-2">-</span>
                                                    <span
                                                        :data-segment-text="'its caterpillars occupy a limited range of '"
                                                        v-html="getHighlightedSegment('its caterpillars occupy a limited range of ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q10"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                </p>
                                            </div>

                                            <!-- The Silver-studded Blue -->
                                            <div class="mt-4 rounded-lg p-3">
                                                <p class="font-semibold text-gray-700">
                                                    <span
                                                        :data-segment-text="'The Silver-studded Blue'"
                                                        v-html="getHighlightedSegment('The Silver-studded Blue')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Q11 -->
                                            <div id="question-11" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >11</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span class="mr-2">-</span>
                                                    <span
                                                        :data-segment-text="'is already able to reproduce twice a year in warm areas of '"
                                                        v-html="getHighlightedSegment('is already able to reproduce twice a year in warm areas of ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q11"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                </p>
                                            </div>

                                            <!-- The White Admiral -->
                                            <div class="mt-4 rounded-lg p-3">
                                                <p class="font-semibold text-gray-700">
                                                    <span
                                                        :data-segment-text="'The White Admiral'"
                                                        v-html="getHighlightedSegment('The White Admiral')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Q12 -->
                                            <div id="question-12" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >12</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span class="mr-2">-</span>
                                                    <span :data-segment-text="'is found in '" v-html="getHighlightedSegment('is found in ')"></span>
                                                    <input
                                                        v-model="answers.q12"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' areas of England'"
                                                        v-html="getHighlightedSegment(' areas of England')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <!-- Q13 -->
                                            <div id="question-13" class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >13</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span class="mr-2">-</span>
                                                    <span
                                                        :data-segment-text="'both climate change and the '"
                                                        v-html="getHighlightedSegment('both climate change and the ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q13"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' of the caterpillar are possible reasons for decline'"
                                                        v-html="getHighlightedSegment(' of the caterpillar are possible reasons for decline')"
                                                    ></span>
                                                </p>
                                            </div>
                                        </div>
                                    </section>
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
                        class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-indigo-50 transition-all hover:scale-110 hover:bg-indigo-100"
                        title="Add Note"
                    >
                        <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                    </button>
                    <button
                        @click="closeContextMenu"
                        class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 hover:bg-gray-100"
                        title="Cancel"
                    >
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div
            v-if="showNoteInput"
            class="absolute z-[9999] w-80 rounded-lg border-2 border-indigo-300 bg-white p-4 shadow-2xl"
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
                    class="note-input-field w-full rounded-lg border-2 border-indigo-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
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
                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-indigo-700"
                >
                    Save Note
                </button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.select-none {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: col-resize;
}

.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

/* Responsive panel widths */
.passage-panel {
    width: 100%;
}

.answer-panel {
    width: 100%;
}

@media (min-width: 1024px) {
    .passage-panel {
        width: calc(var(--panel-width) - 0.5rem);
    }

    .answer-panel {
        width: calc(100% - var(--panel-width) - 0.5rem);
    }
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
}

.highlight-yellow {
    background-color: rgba(254, 240, 138, 0.5);
}

.highlight-indigo {
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
        background-color: rgba(59, 130, 246, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(59, 130, 246, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(59, 130, 246, 0.1);
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
