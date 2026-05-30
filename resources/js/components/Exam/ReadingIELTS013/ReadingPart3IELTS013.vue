<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

// Reading Part 3 component

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts011-part3-panel-width';
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
    q27: '',
    q28: '',
    q29: '',
    q30: '',
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

const passageText =
    ref(`Excavations at the site of prehistoric Akrotiri, on the coast of the Aegean Sea, have revealed much about the technical aspects of pottery manufacture, indisputably one of the basic industries of this Greek city. However, considerably less is known about the socio-economic context and the way production was organised. 

The bulk of pottery found at Akrotiri is locally made, and dates from the late fifteenth century BC. It clearly fulfilled a vast range of the settlement’s requirements: more than fifty different types of pots can be distinguished. The pottery found includes a wide variety of functional types like storage jars, smaller containers, pouring vessels, cooking pots, drinking vessels and so on, which all relate to specific activities and which would have been made and distributed with those activities in mind. Given the large number of shapes produced and the relatively high degree of standardisation, it has generally been assumed that most, if not all, of Akrotiri pottery was produced by specialised craftsmen in a nondomestic context. Unfortunately, neither the potters’ workshops nor kilns have been found within the excavated area. The reason may be that the ceramic workshops were located on the periphery of the site, which has not yet been excavated. In any event, the ubiquity of the pottery, and the consistent repetition of the same types in different sizes, suggests production on an industrial scale. 

The Akrotirian potters seem to have responded to pressures beyond their households, namely to the increasing complexity of regional distribution and exchange systems. We can imagine them as fulltime craftsmen working permanently in a high production-rate craft such as pottery manufacture, and supporting themselves entirely from the proceeds of their craft. In view of the above, one can begin to speak in terms of mass-produced pottery and the existence of organised workshops of craftsmen during the period 1550-1500 BC. Yet, how pottery production was organised at Akrotiri remains an open question, as there is no real documentary evidence. Our entire knowledge comes from the ceramic material itself, and the tentative conclusions which can be drawn from it. 

The invention of units of quantity and of a numerical system to count them was of capital importance for an exchange-geared society such as that of Akrotiri. In spite of the absence of any written records, the archaeological evidence reveals that concepts of measurements, both of weight and number, had been formulated. Standard measures may already have been in operation, such as those evidenced by a graduated series of lead weights - made in disc form - found at the site. The existence of units of capacity in Late Bronze Age times is also evidenced by the notation of units of a liquid measure for wine on excavated containers. 

It must be recognised that the function of pottery vessels plays a very important role in determining their characteristics. The intended function affects the choice of clay, the production technique, and the shape and the size of the pots. For example, large storage jars (pithoi) would be needed to store commodities, whereas smaller containers would be used for transport. In fact, the length of a man’s arm limits the size of a smaller pot to a capacity of about twenty litres; that is also the maximum a man can comfortably carry. 

The various sizes of container would thus represent standard quantities of a commodity, which is a fundamental element in the function of exchange. Akrotirian merchants handling a commodity such as wine would have been able to determine easily the amount of wine they were transporting from the number of containers they carried in their ships, since the capacity of each container was known to be 14-18 litres. (We could draw a parallel here with the current practice in Greece of selling oil in 17 kilogram tins) 

We may, therefore, assume that the shape, capacity, and, sometimes decoration of vessels are indicative of the commodity contained by them. Since individual transactions would normally involve different quantities of a given commodity, a range of ‘standardised’ types of vessel would be needed to meet traders’ requirements. 

In trying to reconstruct systems of capacity by measuring the volume of excavated pottery, a rather generous range of tolerances must be allowed. It seems possible that the potters of that time had specific sizes of vessel in mind, and tried to reproduce them using a specific type and amount of clay. However, it would be quite difficult for them to achieve the exact size required every time, without any mechanical means of regulating symmetry and wall thickness, and some potters would be more skilled than others. In addition, variations in the repetition of types and size may also occur because of unforeseen circumstances during the throwing process. For instance, instead of destroying the entire pot if the clay in the rim contained a piece of grit, a potter might produce a smaller pot by simply cutting off the rim. Even where there is no noticeable external difference between pots meant to contain the same quantity of a commodity, differences in their capacity can actually reach one or two litres. In one case the deviation from the required size appears to be as much as 10-20 percent. 

The establishment of regular trade routes within the Aegean led to increased movement of goods; consequently, a regular exchange of local, luxury and surplus goods, including metals, would have become feasible as a result of the advances in transport technology. The increased demand for standardised exchanges, inextricably linked to commercial transactions, might have been one of the main factors which led to the standardisation of pottery production. Thus, the whole network of ceramic production and exchange would have depended on specific regional economic conditions, and would reflect the socio-economic structure of prehistoric Akrotiri.`);

const textSegments = ref([
    { text: passageText.value, offset: 0 },
    { text: 'READING PASSAGE 3', offset: 5693 },
    { text: 'Pottery production in ancient Akrotiri', offset: 5712 },
    { text: 'Questions 27-28', offset: 5747 },
    { text: 'Choose the correct letter, A, B, C or D and write your answers in boxes 27 & 28 on your answer sheet.', offset: 5763 },
    { text: 'What does the writer say about items of pottery excavated at Akrotiri?', offset: 5865 },
    { text: 'A. There was very little duplication.', offset: 5937 },
    { text: 'B. They would have met a big variety of needs.', offset: 5975 },
    { text: 'C. Most of them had been imported from other places.', offset: 6023 },
    { text: 'D. The intended purpose of each piece was unclear.', offset: 6078 },
    { text: 'The assumption that pottery from Akrotiri was produced by specialists is partly based on', offset: 6133 },
    { text: 'A. the discovery of kilns.', offset: 6220 },
    { text: 'B. the central location of workshops.', offset: 6248 },
    { text: 'C. the sophistication of decorative patterns.', offset: 6288 },
    { text: 'D. the wide range of shapes represented.', offset: 6338 },
    { text: 'Questions 29-32', offset: 6377 },
    { text: 'Complete each sentence with the correct ending, A-F, below.', offset: 6394 },
    { text: 'Write the correct letter, A-F in boxes 29-32 on your answer sheet.', offset: 6459 },
    { text: 'The assumption that standard units of weight were in use could be based on', offset: 6531 },
    { text: 'Evidence of the use of standard units of volume is provided by', offset: 6612 },
    { text: 'The size of certain types of containers would have been restricted by', offset: 6684 },
    { text: 'Attempts to identify the intended capacity of containers are complicated by', offset: 6761 },
    { text: 'A. the discovery of a collection of metal discs.', offset: 6842 },
    { text: 'B. the size and type of the sailing ships in use.', offset: 6891 },
    { text: 'C. variations in the exact shape and thickness of similar containers.', offset: 6942 },
    { text: 'D. the physical characteristics of workmen.', offset: 7013 },
    { text: 'E. marks found on wine containers.', offset: 7058 },
    { text: 'F. the variety of commodities for which they would have been used.', offset: 7091 },
    { text: 'Questions 33-38', offset: 7163 },
    { text: 'Do the following statements agree with the views of the writer in Reading Passage 3?', offset: 7180 },
    { text: 'In boxes on your answer sheet, write', offset: 7266 },
    { text: 'YES', offset: 7303 },
    { text: 'if the statement agrees with the claims of the writer', offset: 7306 },
    { text: 'NO', offset: 7362 },
    { text: 'if the statement contradicts the claims of the writer', offset: 7364 },
    { text: 'NOT GIVEN', offset: 7420 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 7429 },
    { text: 'There are plans to excavate new areas of the archaeological site in the near future.', offset: 7500 },
    { text: 'Some of the evidence concerning pottery production in ancient Akrotiri comes from written records.', offset: 7591 },
    { text: 'Pots for transporting liquids would have held no more than about 20 litres.', offset: 7687 },
    { text: 'It would have been hard for merchants to calculate how much wine was on their ships.', offset: 7767 },
    { text: 'The capacity of containers intended to hold the same amounts differed by up to 20 percent.', offset: 7854 },
    { text: 'Regular trading of goods around the Aegean would have led to the general standardisation of quantities.', offset: 7954 },
    { text: 'Questions 39-40', offset: 8071 },
    { text: 'Choose the correct letter, A, B, C or D and write answers in boxes 39 & 40 on your answer sheet.', offset: 8088 },
    { text: 'What does the writer say about the standardisation of container sizes?', offset: 8185 },
    { text: 'A. Containers which looked the same from the outside often varied in capacity.', offset: 8257 },
    { text: 'B. The instruments used to control container size were unreliable.', offset: 8333 },
    { text: 'C. The unsystematic use of different types of clay resulted in size variations.', offset: 8402 },
    { text: 'D. Potters usually discarded containers which were of a non-standard size.', offset: 8481 },
    { text: 'What is probably the main purpose of Reading Passage 3?', offset: 8560 },
    { text: 'A. To evaluate the quality of pottery containers found in prehistoric Akrotiri.', offset: 8624 },
    { text: 'B. To suggest how features of pottery production at Akrotiri reflected other developments in the region.', offset: 8704 },
    { text: 'C. To outline the development of pottery-making skills in ancient Greece.', offset: 8810 },
    { text: 'D. To describe methods for storing and transporting household goods in prehistoric societies.', offset: 8886 },
]);

const highlightedPassageText = computed(() => {
    return applyHighlightsToText(passageText.value);
});

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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-600">
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
                                        class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-lg"
                                        :data-segment-text="'READING PASSAGE 3'"
                                        v-html="getHighlightedSegment('READING PASSAGE 3')"
                                    ></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h2
                                        class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                    >
                                        <span
                                            :data-segment-text="'Pottery production in ancient Akrotiri'"
                                            v-html="getHighlightedSegment('Pottery production in ancient Akrotiri')"
                                        ></span>
                                    </h2>
                                </div>
                                <button
                                    class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200 sm:text-base"
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
                            <div class="space-y-6 text-base leading-relaxed select-text sm:text-lg">
                                <div class="rounded-lg border border-green-100 bg-gradient-to-r from-green-50 to-emerald-50 p-4 shadow-md">
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
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-green-50 shadow-lg"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">
                        <!-- Questions Header (Fixed) -->
                        <div class="flex-shrink-0 border-b border-gray-200 bg-white p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-600">
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
                                    <p class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-lg">QUESTIONS</p>
                                    <p class="text-xs text-gray-500">Answer all questions based on Reading Passage 3</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto bg-green-50 pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 27-28 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-purple-50 to-green-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 27-28'"
                                            v-html="getHighlightedSegment('Questions 27-28')"
                                        ></h3>
                                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4">
                                            <div class="rounded-xl border border-green-300 bg-white p-6 shadow-lg">
                                                <p
                                                    class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                    :data-segment-text="'Choose the correct letter, A, B, C or D and write your answers in boxes 27 & 28 on your answer sheet.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Choose the correct letter, A, B, C or D and write your answers in boxes 27 & 28 on your answer sheet.',
                                                        )
                                                    "
                                                ></p>
                                            </div>
                                        </div>
                                        <div class="mt-6 space-y-6">
                                            <div
                                                id="question-27"
                                                class="rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-4">
                                                    <span
                                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-semibold text-white shadow-lg"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >27</span
                                                    >
                                                    <div class="flex-1">
                                                        <p
                                                            class="mb-4 font-medium text-gray-800"
                                                            :data-segment-text="'What does the writer say about items of pottery excavated at Akrotiri?'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'What does the writer say about items of pottery excavated at Akrotiri?',
                                                                )
                                                            "
                                                        ></p>
                                                        <div class="space-y-3">
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q27"
                                                                    value="A"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'A. There was very little duplication.'"
                                                                    v-html="getHighlightedSegment('A. There was very little duplication.')"
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q27"
                                                                    value="B"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'B. They would have met a big variety of needs.'"
                                                                    v-html="getHighlightedSegment('B. They would have met a big variety of needs.')"
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q27"
                                                                    value="C"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'C. Most of them had been imported from other places.'"
                                                                    v-html="
                                                                        getHighlightedSegment('C. Most of them had been imported from other places.')
                                                                    "
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q27"
                                                                    value="D"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'D. The intended purpose of each piece was unclear.'"
                                                                    v-html="
                                                                        getHighlightedSegment('D. The intended purpose of each piece was unclear.')
                                                                    "
                                                                ></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                id="question-28"
                                                class="rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-4">
                                                    <span
                                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-semibold text-white shadow-lg"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >28</span
                                                    >
                                                    <div class="flex-1">
                                                        <p
                                                            class="mb-4 font-medium text-gray-800"
                                                            :data-segment-text="'The assumption that pottery from Akrotiri was produced by specialists is partly based on'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'The assumption that pottery from Akrotiri was produced by specialists is partly based on',
                                                                )
                                                            "
                                                        ></p>
                                                        <div class="space-y-3">
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q28"
                                                                    value="A"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'A. the discovery of kilns.'"
                                                                    v-html="getHighlightedSegment('A. the discovery of kilns.')"
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q28"
                                                                    value="B"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'B. the central location of workshops.'"
                                                                    v-html="getHighlightedSegment('B. the central location of workshops.')"
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q28"
                                                                    value="C"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'C. the sophistication of decorative patterns.'"
                                                                    v-html="getHighlightedSegment('C. the sophistication of decorative patterns.')"
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q28"
                                                                    value="D"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'D. the wide range of shapes represented.'"
                                                                    v-html="getHighlightedSegment('D. the wide range of shapes represented.')"
                                                                ></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Questions 29-32 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 29-32'"
                                            v-html="getHighlightedSegment('Questions 29-32')"
                                        ></h3>
                                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4">
                                            <p
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Complete each sentence with the correct ending, A-F, below.'"
                                                v-html="getHighlightedSegment('Complete each sentence with the correct ending, A-F, below.')"
                                            ></p>
                                            <p
                                                class="mt-2 text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Write the correct letter, A-F in boxes 29-32 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write the correct letter, A-F in boxes 29-32 on your answer sheet.')"
                                            ></p>
                                            <div
                                                class="mt-4 rounded-xl border border-green-200 bg-gradient-to-r from-green-100 to-emerald-50 p-4 shadow-xl"
                                            >
                                                <ul class="space-y-1 text-base text-gray-700">
                                                    <li
                                                        :data-segment-text="'A. the discovery of a collection of metal discs.'"
                                                        v-html="getHighlightedSegment('A. the discovery of a collection of metal discs.')"
                                                    ></li>
                                                    <li
                                                        :data-segment-text="'B. the size and type of the sailing ships in use.'"
                                                        v-html="getHighlightedSegment('B. the size and type of the sailing ships in use.')"
                                                    ></li>
                                                    <li
                                                        :data-segment-text="'C. variations in the exact shape and thickness of similar containers.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'C. variations in the exact shape and thickness of similar containers.',
                                                            )
                                                        "
                                                    ></li>
                                                    <li
                                                        :data-segment-text="'D. the physical characteristics of workmen.'"
                                                        v-html="getHighlightedSegment('D. the physical characteristics of workmen.')"
                                                    ></li>
                                                    <li
                                                        :data-segment-text="'E. marks found on wine containers.'"
                                                        v-html="getHighlightedSegment('E. marks found on wine containers.')"
                                                    ></li>
                                                    <li
                                                        :data-segment-text="'F. the variety of commodities for which they would have been used.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'F. the variety of commodities for which they would have been used.',
                                                            )
                                                        "
                                                    ></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="mt-6 space-y-4">
                                            <div
                                                v-for="i in 4"
                                                :key="i"
                                                class="rounded-xl border-l-4 border-green-500 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-semibold text-white shadow-lg"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >{{ 28 + i }}</span
                                                    >
                                                    <span
                                                        class="flex-1 text-gray-700"
                                                        :data-segment-text="
                                                            [
                                                                'The assumption that standard units of weight were in use could be based on',
                                                                'Evidence of the use of standard units of volume is provided by',
                                                                'The size of certain types of containers would have been restricted by',
                                                                'Attempts to identify the intended capacity of containers are complicated by',
                                                            ][i - 1]
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                [
                                                                    'The assumption that standard units of weight were in use could be based on',
                                                                    'Evidence of the use of standard units of volume is provided by',
                                                                    'The size of certain types of containers would have been restricted by',
                                                                    'Attempts to identify the intended capacity of containers are complicated by',
                                                                ][i - 1],
                                                            )
                                                        "
                                                    ></span>
                                                    <select
                                                        v-model="answers[`q${28 + i}`]"
                                                        class="w-24 rounded-xl border border-green-300 bg-green-50 px-2 py-1 text-sm shadow focus:ring-2 focus:ring-green-500"
                                                    >
                                                        <option disabled value="">Select</option>
                                                        <option v-for="char in ['A', 'B', 'C', 'D', 'E', 'F']" :key="char" :value="char">
                                                            {{ char }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Questions 33-38 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 33-38'"
                                            v-html="getHighlightedSegment('Questions 33-38')"
                                        ></h3>
                                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4">
                                            <p
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Do the following statements agree with the views of the writer in Reading Passage 3?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the views of the writer in Reading Passage 3?',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="mt-2 text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'In boxes on your answer sheet, write'"
                                                v-html="getHighlightedSegment('In boxes on your answer sheet, write')"
                                            ></p>
                                            <div class="grid grid-cols-1 gap-2 pt-4 text-sm sm:text-lg">
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="w-24 rounded bg-green-100 px-2 py-1 font-bold text-green-700"
                                                        :data-segment-text="'YES'"
                                                        v-html="getHighlightedSegment('YES')"
                                                    ></span
                                                    ><span
                                                        class="text-gray-700"
                                                        :data-segment-text="'if the statement agrees with the claims of the writer'"
                                                        v-html="getHighlightedSegment('if the statement agrees with the claims of the writer')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="w-24 rounded bg-red-100 px-2 py-1 font-bold text-red-700"
                                                        :data-segment-text="'NO'"
                                                        v-html="getHighlightedSegment('NO')"
                                                    ></span
                                                    ><span
                                                        class="text-gray-700"
                                                        :data-segment-text="'if the statement contradicts the claims of the writer'"
                                                        v-html="getHighlightedSegment('if the statement contradicts the claims of the writer')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <span
                                                        class="w-28 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                        :data-segment-text="'NOT GIVEN'"
                                                        v-html="getHighlightedSegment('NOT GIVEN')"
                                                    ></span
                                                    ><span
                                                        class="text-gray-700"
                                                        :data-segment-text="'if it is impossible to say what the writer thinks about this'"
                                                        v-html="getHighlightedSegment('if it is impossible to say what the writer thinks about this')"
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div
                                            v-for="i in 6"
                                            :key="i"
                                            class="rounded-xl border-l-4 border-green-600 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                        >
                                            <div class="flex items-center gap-3">
                                                <span
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 font-semibold text-white shadow-lg"
                                                    >{{ 32 + i }}</span
                                                >
                                                <span
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="
                                                        [
                                                            'There are plans to excavate new areas of the archaeological site in the near future.',
                                                            'Some of the evidence concerning pottery production in ancient Akrotiri comes from written records.',
                                                            'Pots for transporting liquids would have held no more than about 20 litres.',
                                                            'It would have been hard for merchants to calculate how much wine was on their ships.',
                                                            'The capacity of containers intended to hold the same amounts differed by up to 20 percent.',
                                                            'Regular trading of goods around the Aegean would have led to the general standardisation of quantities.',
                                                        ][i - 1]
                                                    "
                                                    v-html="
                                                        getHighlightedSegment(
                                                            [
                                                                'There are plans to excavate new areas of the archaeological site in the near future.',
                                                                'Some of the evidence concerning pottery production in ancient Akrotiri comes from written records.',
                                                                'Pots for transporting liquids would have held no more than about 20 litres.',
                                                                'It would have been hard for merchants to calculate how much wine was on their ships.',
                                                                'The capacity of containers intended to hold the same amounts differed by up to 20 percent.',
                                                                'Regular trading of goods around the Aegean would have led to the general standardisation of quantities.',
                                                            ][i - 1],
                                                        )
                                                    "
                                                ></span>
                                            </div>
                                            <div class="mt-2 flex gap-4">
                                                <label class="flex items-center gap-1"
                                                    ><input
                                                        type="radio"
                                                        :name="`q${32 + i}`"
                                                        v-model="answers[`q${32 + i}`]"
                                                        value="YES"
                                                        class="h-4 w-4 accent-emerald-500"
                                                    /><span class="font-medium text-gray-700">YES</span></label
                                                >
                                                <label class="flex items-center gap-1"
                                                    ><input
                                                        type="radio"
                                                        :name="`q${32 + i}`"
                                                        v-model="answers[`q${32 + i}`]"
                                                        value="NO"
                                                        class="h-4 w-4 accent-emerald-500"
                                                    /><span class="font-medium text-gray-700">NO</span></label
                                                >
                                                <label class="flex items-center gap-1"
                                                    ><input
                                                        type="radio"
                                                        :name="`q${32 + i}`"
                                                        v-model="answers[`q${32 + i}`]"
                                                        value="NOT GIVEN"
                                                        class="h-4 w-4 accent-emerald-500"
                                                    /><span class="font-medium text-gray-700">NOT GIVEN</span></label
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Questions 39-40 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <h3
                                            class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-lg font-bold text-transparent md:text-xl"
                                            :data-segment-text="'Questions 39-40'"
                                            v-html="getHighlightedSegment('Questions 39-40')"
                                        ></h3>
                                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4">
                                            <p
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Choose the correct letter, A, B, C or D and write answers in boxes 39 & 40 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Choose the correct letter, A, B, C or D and write answers in boxes 39 & 40 on your answer sheet.',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                        <div class="mt-6 space-y-6">
                                            <div
                                                id="question-39"
                                                class="rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-4">
                                                    <span
                                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-semibold text-white shadow-lg"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >39</span
                                                    >
                                                    <div class="flex-1">
                                                        <p
                                                            class="mb-4 font-medium text-gray-800"
                                                            :data-segment-text="'What does the writer say about the standardisation of container sizes?'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'What does the writer say about the standardisation of container sizes?',
                                                                )
                                                            "
                                                        ></p>
                                                        <div class="space-y-3">
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q39"
                                                                    value="A"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'A. Containers which looked the same from the outside often varied in capacity.'"
                                                                    v-html="
                                                                        getHighlightedSegment(
                                                                            'A. Containers which looked the same from the outside often varied in capacity.',
                                                                        )
                                                                    "
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q39"
                                                                    value="B"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'B. The instruments used to control container size were unreliable.'"
                                                                    v-html="
                                                                        getHighlightedSegment(
                                                                            'B. The instruments used to control container size were unreliable.',
                                                                        )
                                                                    "
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q39"
                                                                    value="C"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'C. The unsystematic use of different types of clay resulted in size variations.'"
                                                                    v-html="
                                                                        getHighlightedSegment(
                                                                            'C. The unsystematic use of different types of clay resulted in size variations.',
                                                                        )
                                                                    "
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q39"
                                                                    value="D"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'D. Potters usually discarded containers which were of a non-standard size.'"
                                                                    v-html="
                                                                        getHighlightedSegment(
                                                                            'D. Potters usually discarded containers which were of a non-standard size.',
                                                                        )
                                                                    "
                                                                ></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                id="question-40"
                                                class="rounded-xl border-l-4 border-green-500 bg-white p-4 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                            >
                                                <div class="flex items-start gap-4">
                                                    <span
                                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-500 font-semibold text-white shadow-lg"
                                                        style="
                                                            box-shadow:
                                                                0 4px 6px -1px rgba(16, 185, 129, 0.5),
                                                                0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                        "
                                                        >40</span
                                                    >
                                                    <div class="flex-1">
                                                        <p
                                                            class="mb-4 font-medium text-gray-800"
                                                            :data-segment-text="'What is probably the main purpose of Reading Passage 3?'"
                                                            v-html="getHighlightedSegment('What is probably the main purpose of Reading Passage 3?')"
                                                        ></p>
                                                        <div class="space-y-3">
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q40"
                                                                    value="A"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'A. To evaluate the quality of pottery containers found in prehistoric Akrotiri.'"
                                                                    v-html="
                                                                        getHighlightedSegment(
                                                                            'A. To evaluate the quality of pottery containers found in prehistoric Akrotiri.',
                                                                        )
                                                                    "
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q40"
                                                                    value="B"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'B. To suggest how features of pottery production at Akrotiri reflected other developments in the region.'"
                                                                    v-html="
                                                                        getHighlightedSegment(
                                                                            'B. To suggest how features of pottery production at Akrotiri reflected other developments in the region.',
                                                                        )
                                                                    "
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q40"
                                                                    value="C"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'C. To outline the development of pottery-making skills in ancient Greece.'"
                                                                    v-html="
                                                                        getHighlightedSegment(
                                                                            'C. To outline the development of pottery-making skills in ancient Greece.',
                                                                        )
                                                                    "
                                                                ></span>
                                                            </label>
                                                            <label
                                                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-green-200 p-3 transition-colors hover:bg-green-100"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    v-model="answers.q40"
                                                                    value="D"
                                                                    class="h-5 w-5 accent-green-600"
                                                                />
                                                                <span
                                                                    class="text-gray-700"
                                                                    :data-segment-text="'D. To describe methods for storing and transporting household goods in prehistoric societies.'"
                                                                    v-html="
                                                                        getHighlightedSegment(
                                                                            'D. To describe methods for storing and transporting household goods in prehistoric societies.',
                                                                        )
                                                                    "
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
                    class="note-input-field w-full rounded-lg border-2 border-indigo-200 px-3 py-2 text-base focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none sm:text-lg"
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
        background-color: rgba(34, 197, 94, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(34, 197, 94, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(34, 197, 94, 0.1);
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
    background: linear-gradient(to bottom, #10b981, #059669);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #059669, #047857);
}
</style>
