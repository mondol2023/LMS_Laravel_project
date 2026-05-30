<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Reading Part 1 component

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

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts011-part1-panel-width';
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
    q14: '',
});

const passageText = `A lot of people around the world are dependent, or partly dependent, on coral reefs for their livelihoods. They often live adjacent to the reef, and their livelihood revolves around the direct extraction, processing and sale of reef resources such as shell fish and seaweeds. In addition, their homes are sheltered by the reef from wave action. 

Reef flats and shallow reef lagoons are accessible on foot, without the need for a boat, and so allow women, children and the elderly to engage directly in manual harvesting, or ‘reef-gleaning’. This is a significant factor distinguishing reef-based fisheries from near-shore sea fisheries. Near-shore fisheries are typically the domain of adult males, in particular where they involve the use of boats, with women and children restricted mainly to shore-based activities. However, in a coral-reef fishery the physical accessibility of the reef opens up opportunities for direct participation by women, and consequently increases their independence and the importance of their role in the community. It also provides a place for children to play, and to acquire important skills and knowledge for later in life. For example, in the South West Island of Tobi, in the Pacific Ocean, young boys use simple hand lines with a loop and bait at the end to develop the art of fishing on the reef. Similarly, in the Surin Islands of Thailand, young Moken boys spend much of their time playing, swimming and diving in shallow reef lagoons, and in doing so build crucial skills for their future daily subsistence. 

Secondary occupations, such as fish processing and marketing activities, are often dominated by women, and offer an important survival strategy for households with access to few other physical assets (such as boats and gear), for elderly women, widows, or the wives of infirm men. On Ulithi Atoll in the western Pacific, women have a distinct role and rights in the distribution of fish catches. This is because the canoes, made from mahogany logs from nearby Yap Island, are obtained through the exchange of cloth made by the women of Ulithi. Small-scale reef fisheries support the involvement of local women traders and their involvement can give them greater control over the household income, and in negotiating for loans or credit. Thus their role is not only important in providing income for their families, it also underpins the economy of the local village. 

Poor people with little access to land, labour and financial resources are particularly reliant on exploiting natural resources, and consequently they are vulnerable to seasonal changes in availability of those resources. The diversity of coral reef fisheries, combined with their physical accessibility and the protection they provide against bad weather, make them relatively stable compared with other fisheries, or land-based agricultural production. 

In many places, the reef may even act as a resource bank, used as a means of saving food for future times of need. In Manus, Papua New Guinea, giant clams are collected and held in walled enclosures on the reef, until they are needed during periods of rough weather. In Palau, sea cucumbers are seldom eaten during good weather in an effort to conserve their populations for months during which rough weather prohibits good fishing. 

Coral reef resources also act as a buffer against seasonal lows in other sectors, particularly agriculture. For example, in coastal communities in northern Mozambique, reef harvests provide key sources of food and cash when agricultural production is low, with the peak in fisheries production coinciding with the period of lowest agricultural stocks. In Papua New Guinea, while agriculture is the primary means of food production, a large proportion of the coastal population engage in sporadic subsistence fishing. 

In many coral-reef areas, tourism is one of the main industries bringing employment, and in many cases is promoted to provide alternatives to fisheries-based livelihoods, and to ensure that local reef resources are conserved. In the Caribbean alone, tours based on scuba-diving have attracted 20 million people in one year. The upgrading of roads and communications associated with the expansion of tourism may also bring benefits to local communities. However, plans for development must be considered carefully. The ability of the poorer members of the community to access the benefits of tourism is far from guaranteed, and requires development guided by social, cultural and environmental principles. There is growing recognition that sustainability is a key requirement, as encompassed in small-scale eco-tourism activities, for instance. 

Where tourism development has not been carefully planned, and the needs and priorities of the local community have not been properly recognised, conflict has sometimes arisen between tourism and local, small-scale fishers.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 1', offset: 4825 },
    { text: 'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.', offset: 4844 },
    { text: 'The economic importance of coral reefs', offset: 4944 },
    { text: 'QUESTIONS', offset: 4967 },
    { text: 'Answer all questions based on Reading Passage 1', offset: 4976 },
    { text: 'Questions 1-7', offset: 5023 },
    { text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: 5039 },
    { text: 'TRUE', offset: 5745 },
    { text: 'if the statement agrees with the writer', offset: 5748 },
    { text: 'FALSE', offset: 5789 },
    { text: 'if the statement contradicts the writer', offset: 5791 },
    { text: 'NOT GIVEN', offset: 5831 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 5840 },
    { text: 'In most places, coral-reef gleaning is normally carried out by men.', offset: 5278 },
    { text: 'Involvement in coral-reef-based occupations raises the status of women.', offset: 5353 },
    { text: 'Coral reefs provide valuable learning opportunities for young children.', offset: 5433 },
    { text: 'The women of Ulithi Atoll have some control over how fish catches are shared out.', offset: 5509 },
    { text: 'Boats for use by the inhabitants of Ulithi are constructed on Yap Island.', offset: 5595 },
    { text: 'In coral reef fisheries, only male traders can apply for finance.', offset: 5678 },
    { text: 'Coral reefs provide a less constant source of income than near-shore seas.', offset: 5749 },
    { text: 'Questions 8-13', offset: 6000 },
    { text: 'Complete the notes below.', offset: 6015 },
    { text: 'Choose NO MORE THAN TWO WORDS from the passage for each answer.', offset: 6042 },
    { text: 'How coral-reef-based resources protect people during difficult times', offset: 6116 },
    { text: 'Coral reefs can provide', offset: 6190 },
    { text: '• a resource bank, e.g. for keeping clams and', offset: 6215 },
    { text: '• a seasonal back-up, when', offset: 6265 },
    { text: 'products are insufficient', offset: 6295 },
    { text: '• e.g. in northern Mozambique, a tourist attraction, e.g.', offset: 6325 },
    { text: 'tours in the Caribbean.', offset: 6385 },
    { text: 'Benefits for local people include:', offset: 6415 },
    { text: '• The creation of jobs.', offset: 6450 },
    { text: '• Improvements to roads and', offset: 6475 },
    { text: 'Important considerations:', offset: 6510 },
    { text: '• Development must be based on appropriate principles.', offset: 6540 },
    { text: '• Need for', offset: 6600 },
    { text: 'Poorly-planned development can create', offset: 6650 },
    { text: 'with local fishers.', offset: 6700 },
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <div class="container mx-auto p-3 sm:p-4 lg:p-6">
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
                                            :data-segment-text="'The economic importance of coral reefs'"
                                            v-html="getHighlightedSegment('The economic importance of coral reefs')"
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
                                    <p
                                        class="text-sm font-semibold tracking-wide text-gray-700 uppercase"
                                        :data-segment-text="'QUESTIONS'"
                                        v-html="getHighlightedSegment('QUESTIONS')"
                                    ></p>
                                    <p
                                        class="text-xs text-gray-500"
                                        :data-segment-text="'Answer all questions based on Reading Passage 1'"
                                        v-html="getHighlightedSegment('Answer all questions based on Reading Passage 1')"
                                    ></p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 1-7 -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-1-7" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 1-7'"
                                            v-html="getHighlightedSegment('Questions 1-7')"
                                        ></h3>

                                        <div class="rounded-xl border border-purple-200 bg-white p-6 shadow-sm">
                                            <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg">
                                                <span
                                                    :data-segment-text="'Do the following statements agree with the information given in Reading Passage 1?'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Do the following statements agree with the information given in Reading Passage 1?',
                                                        )
                                                    "
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
                                                        :data-segment-text="'if the statement agrees with the writer'"
                                                        class="text-gray-700"
                                                        v-html="getHighlightedSegment('if the statement agrees with the writer')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="w-24 rounded bg-red-100 px-2 py-1 font-bold text-red-700"
                                                        :data-segment-text="'FALSE'"
                                                        v-html="getHighlightedSegment('FALSE')"
                                                    ></span>
                                                    <span
                                                        :data-segment-text="'if the statement contradicts the writer'"
                                                        class="text-gray-700"
                                                        v-html="getHighlightedSegment('if the statement contradicts the writer')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="w-28 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                        :data-segment-text="'NOT GIVEN'"
                                                        v-html="getHighlightedSegment('NOT GIVEN')"
                                                    ></span>
                                                    <span
                                                        :data-segment-text="'if it is impossible to say what the writer thinks about this'"
                                                        class="text-gray-700"
                                                        v-html="getHighlightedSegment('if it is impossible to say what the writer thinks about this')"
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div
                                                v-for="i in 7"
                                                :key="i"
                                                class="flex items-start gap-3 rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow-lg"
                                            >
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 font-bold text-white shadow-md"
                                                >
                                                    {{ i }}
                                                </div>
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="
                                                            [
                                                                'In most places, coral-reef gleaning is normally carried out by men.',
                                                                'Involvement in coral-reef-based occupations raises the status of women.',
                                                                'Coral reefs provide valuable learning opportunities for young children.',
                                                                'The women of Ulithi Atoll have some control over how fish catches are shared out.',
                                                                'Boats for use by the inhabitants of Ulithi are constructed on Yap Island.',
                                                                'In coral reef fisheries, only male traders can apply for finance.',
                                                                'Coral reefs provide a less constant source of income than near-shore seas.',
                                                            ][i - 1]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                [
                                                                    'In most places, coral-reef gleaning is normally carried out by men.',
                                                                    'Involvement in coral-reef-based occupations raises the status of women.',
                                                                    'Coral reefs provide valuable learning opportunities for young children.',
                                                                    'The women of Ulithi Atoll have some control over how fish catches are shared out.',
                                                                    'Boats for use by the inhabitants of Ulithi are constructed on Yap Island.',
                                                                    'In coral reef fisheries, only male traders can apply for finance.',
                                                                    'Coral reefs provide a less constant source of income than near-shore seas.',
                                                                ][i - 1],
                                                            )
                                                        "
                                                    ></p>
                                                    <div class="flex flex-wrap gap-4 pt-2">
                                                        <label class="flex cursor-pointer items-center gap-2">
                                                            <input
                                                                type="radio"
                                                                :name="`q${i}`"
                                                                value="TRUE"
                                                                v-model="answers[`q${i}`]"
                                                                class="h-5 w-5 border-gray-300 text-blue-600 focus:ring-blue-500"
                                                            />
                                                            <span class="font-semibold text-gray-700">TRUE</span>
                                                        </label>
                                                        <label class="flex cursor-pointer items-center gap-2">
                                                            <input
                                                                type="radio"
                                                                :name="`q${i}`"
                                                                value="FALSE"
                                                                v-model="answers[`q${i}`]"
                                                                class="h-5 w-5 border-gray-300 text-blue-600 focus:ring-blue-500"
                                                            />
                                                            <span class="font-semibold text-gray-700">FALSE</span>
                                                        </label>
                                                        <label class="flex cursor-pointer items-center gap-2">
                                                            <input
                                                                type="radio"
                                                                :name="`q${i}`"
                                                                value="NOT GIVEN"
                                                                v-model="answers[`q${i}`]"
                                                                class="h-5 w-5 border-gray-300 text-blue-600 focus:ring-blue-500"
                                                            />
                                                            <span class="font-semibold text-gray-700">NOT GIVEN</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 8-13 -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-8-13" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 8-13'"
                                            v-html="getHighlightedSegment('Questions 8-13')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-blue-50 p-3 shadow-inner sm:p-4">
                                            <p
                                                class="mb-1 text-sm font-medium text-gray-800 sm:text-base"
                                                :data-segment-text="'Complete the notes below.'"
                                                v-html="getHighlightedSegment('Complete the notes below.')"
                                            ></p>
                                            <p
                                                class="text-xs font-semibold text-red-600 sm:text-sm"
                                                :data-segment-text="'Choose NO MORE THAN TWO WORDS from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose NO MORE THAN TWO WORDS from the passage for each answer.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-4 rounded-lg border border-blue-100 bg-white/60 p-4 shadow-lg">
                                            <h4
                                                class="text-center font-bold text-blue-800"
                                                :data-segment-text="'How coral-reef-based resources protect people during difficult times'"
                                                v-html="getHighlightedSegment('How coral-reef-based resources protect people during difficult times')"
                                            ></h4>

                                            <div class="space-y-3 pl-4 text-gray-700">
                                                <p
                                                    class="font-semibold"
                                                    :data-segment-text="'Coral reefs can provide'"
                                                    v-html="getHighlightedSegment('Coral reefs can provide')"
                                                ></p>
                                                <ul class="list-disc space-y-4 pl-5">
                                                    <li>
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                :data-segment-text="'• a resource bank, e.g. for keeping clams and'"
                                                                v-html="getHighlightedSegment('• a resource bank, e.g. for keeping clams and')"
                                                            ></span>
                                                            <span
                                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 text-base font-bold text-white shadow-md"
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
                                                                class="w-32 rounded-lg border border-blue-300 bg-blue-50 px-2 py-1 text-sm shadow-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                            />
                                                        </div>
                                                    </li>
                                                    <li>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span
                                                                :data-segment-text="'• a seasonal back-up, when'"
                                                                v-html="getHighlightedSegment('• a seasonal back-up, when')"
                                                            ></span>
                                                            <span
                                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 text-base font-bold text-white shadow-md"
                                                                style="
                                                                    box-shadow:
                                                                        0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                                "
                                                                >9</span
                                                            >
                                                            <input
                                                                v-model="answers.q9"
                                                                type="text"
                                                                class="w-32 rounded-lg border border-blue-300 bg-blue-50 px-2 py-1 text-sm shadow-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                            />
                                                            <span
                                                                :data-segment-text="'products are insufficient'"
                                                                v-html="getHighlightedSegment('products are insufficient')"
                                                            ></span>
                                                        </div>
                                                    </li>
                                                    <li>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span
                                                                :data-segment-text="'• e.g. in northern Mozambique, a tourist attraction, e.g.'"
                                                                v-html="
                                                                    getHighlightedSegment('• e.g. in northern Mozambique, a tourist attraction, e.g.')
                                                                "
                                                            ></span>
                                                            <span
                                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 text-base font-bold text-white shadow-md"
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
                                                                class="w-32 rounded-lg border border-blue-300 bg-blue-50 px-2 py-1 text-sm shadow-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                            />
                                                            <span
                                                                :data-segment-text="'tours in the Caribbean.'"
                                                                v-html="getHighlightedSegment('tours in the Caribbean.')"
                                                            ></span>
                                                        </div>
                                                    </li>
                                                </ul>

                                                <p
                                                    class="pt-2 font-semibold"
                                                    :data-segment-text="'Benefits for local people include:'"
                                                    v-html="getHighlightedSegment('Benefits for local people include:')"
                                                ></p>
                                                <ul class="list-disc space-y-4 pl-5">
                                                    <li
                                                        :data-segment-text="'• The creation of jobs.'"
                                                        v-html="getHighlightedSegment('• The creation of jobs.')"
                                                    ></li>
                                                    <li>
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                :data-segment-text="'• Improvements to roads and'"
                                                                v-html="getHighlightedSegment('• Improvements to roads and')"
                                                            ></span>
                                                            <span
                                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 text-base font-bold text-white shadow-md"
                                                                style="
                                                                    box-shadow:
                                                                        0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                                "
                                                                >11</span
                                                            >
                                                            <input
                                                                v-model="answers.q11"
                                                                type="text"
                                                                class="w-32 rounded-lg border border-blue-300 bg-blue-50 px-2 py-1 text-sm shadow-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                            />
                                                        </div>
                                                    </li>
                                                </ul>

                                                <p
                                                    class="pt-2 font-semibold"
                                                    :data-segment-text="'Important considerations:'"
                                                    v-html="getHighlightedSegment('Important considerations:')"
                                                ></p>
                                                <ul class="list-disc space-y-4 pl-5">
                                                    <li
                                                        :data-segment-text="'• Development must be based on appropriate principles.'"
                                                        v-html="getHighlightedSegment('• Development must be based on appropriate principles.')"
                                                    ></li>
                                                    <li>
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                :data-segment-text="'• Need for'"
                                                                v-html="getHighlightedSegment('• Need for')"
                                                            ></span>
                                                            <span
                                                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 text-base font-bold text-white shadow-md"
                                                                style="
                                                                    box-shadow:
                                                                        0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                                "
                                                                >12</span
                                                            >
                                                            <input
                                                                v-model="answers.q12"
                                                                type="text"
                                                                class="w-32 rounded-lg border border-blue-300 bg-blue-50 px-2 py-1 text-sm shadow-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                            />
                                                        </div>
                                                    </li>
                                                </ul>

                                                <div class="flex flex-wrap items-center gap-2 pt-2">
                                                    <span
                                                        class="font-semibold"
                                                        :data-segment-text="'Poorly-planned development can create'"
                                                        v-html="getHighlightedSegment('Poorly-planned development can create')"
                                                    ></span>
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 text-base font-bold text-white shadow-md"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(59, 130, 246, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >13</span
                                                    >
                                                    <input
                                                        v-model="answers.q13"
                                                        type="text"
                                                        class="w-32 rounded-lg border border-blue-300 bg-blue-50 px-2 py-1 text-sm shadow-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                    />
                                                    <span
                                                        class="font-semibold"
                                                        :data-segment-text="'with local fishers.'"
                                                        v-html="getHighlightedSegment('with local fishers.')"
                                                    ></span>
                                                </div>
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
