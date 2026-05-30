<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Reading Part 2 component

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
const PANEL_WIDTH_KEY = 'reading-ielts012-part2-panel-width';
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
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

const passageText = `More than two hundred years ago, Russian explorers and fur hunters landed on the Aleutian Islands, a volcanic archipelago in the North Pacific, and learned of a land mass that lay farther to the north. The islands' native inhabitants called this land mass Aleyska, the 'Great Land'; today, we know it as Alaska. 

The forty-ninth state to join the United States of America (in 1959), Alaska is fully one-fifth the size of the mainland 48 states combined. It shares, with Canada, the second, longest river system in North America and has over half the coastline of the United States. The rivers feed into the Bering Sea and Gulf of Alaska - cold, nutrient-rich waters which support tens of millions of seabirds, and over 400 species of fish, shellfish, crustaceans, and mollusks. Taking advantage of this rich bounty, Alaska's commercial fisheries have developed into some of the largest in the world.

According to the Alaska Department of Fish and Game (ADF&G), Alaska's commercial fisheries landedhundreds of thousarids of tonnes of shellfish and herring, and well over a million tones of ground fish (cod,sole, perch and pollock) in 2000. The true cultural heart and soul of Alaska's fisheries, "however, is salmon.'Salmon,' notes writer Susan Ewing in The Great Alaska Nature Factbook, pump through Alaska like blood through a heart, bringing rhythmic, circulating nourishment to land, animals and people.' The 'predictableabundance of salmon allowed some native cultures to flourish,' and 'dying spankers* feed bears, eagles, otheranimals, and ultimately the soil itself' All five species of Pacific salmon - chinook, or king; chum, or dog;Coho, or silver; sockeye, or red; and pink, or humpback-spawn** in Alaskan waters, and 90% of all Pacificsalmon commercially caught in North America are produced there. Indeed, if Alaska was an independentnation, it would be the largest producer of wild salmon in the world. During 2000, commercial catches ofPacific salmon in Alaska exceeded 320,000 tonnes, with an ex-vessel value of over $US260 million.

Catches have not always been so healthy. Between 1940 and 1959, over-fishing led to crashes in salmonpopulations so severe that in 1953 Alaska was declared a federal disaster area. With the onset of statehood,however, the State of Alaska took over management of its own fisheries, guided by a state constitution whichmandates that Alaska's natural resources be managed on a sustainable basis. At that time, statewide harveststotaled around 25 million salmon. Over the next few- decades average catches steadily increased as a result ofthis policy of sustainable management, until, during the 1990s, annual harvests were well in excess of 100million, and on several occasions over 200 million fish.

The primary reason for such increases is what is known as In-Season Abundance-Based Management'. There arebiologists throughout the state constantly monitoring adult fish as they show up to spawn. The biologists sir. instreamside counting towers, study sonar, watch from aeroplanes, and talk to fishermen. The salmon season in Alaska isnot pre-set. The fishermen know die approximate time of year when they will be allowed to fish, but on any given day,one or more field biologists in a particular area can put a halt to fishing. Even sport filing can be brought to a halt It isthis management mechanism that has allowed Alaska salmon stocks - and, accordingly, Alaska salmon fisheries - toprosper, even as salmon populations in the rest of the United States arc increasingly considered threatened or evenendangered.

In 1999, the Marine Stewardship Council (MSC)*** commissioned a review of the Alaska salmon fishery.The Council, which was founded in 19%, certifies fisheries that meet high environmental standards, enablingthem to use a label that recognises their environmental responsibility. The MSC has established a set ofcriteria by which commercial fisheries can be judged. Recognising the potential benefits of being identifiedas environmentally responsible, fisheries approach the Council requesting to undergo tcertificationuonprocess. The MSC then appoints a certification committee, composed of a panel of fisheries experts, whichgathers information and opinions from fishermen, biologists, government officials, industry representatives,non-governmental organisations and others.

Some observers thought the Alaska salmon fisheries would not have any chance of certification when, in themonths leading up to MSC's final decision, salmon runs throughout western Alaska - completely collapsed.In the Yukon and Kuskokwim rivers, chinook and chum runs were probably the poorest since statehood;subsistence communities throughout the region, who normally have priority over commercial fishing, weredevastated.

The crisis was completely unexpected, but researchers believe it had nothing to do with impacts of fisheries.Rather, they contend, it was almost certainly the result of climatic shifts, prompted in part by cumulativeeffects of the elniño/ la niña phenomenon on Pacific Ocean temperatures, culminating in a harsh winter inwhich huge numbers of salmon eggs were frozen. It could have meant the end as far as the certificationprocess was concerned. However, the state reacted quickly, closing down all fisheries, even those necessaryfor subsistence purposes.

In September 2000, MSC announced that the Alaska salmon fisheries qualified fop certification. Sevencompanies producing Alaska salmon were immediately granted permission to display the MSC logo on theirproducts. Certification is for an initial period of five years, with an annual review to ensure dial the fishery iscontinuing to meet the required standards.
* spawners: fish that have released eggs
** spawn: release eggs
*** MSC: a joint venture between WWF (World Wildlife Fund) and Unilever, A Dutch-based multi-national.`;

const passageOffset = passageText.length;
const textSegments = ref([
    { text: passageText, offset: 0 },
    // Questions 14-20: TRUE/FALSE/NOT GIVEN
    { text: 'Questions 14-20', offset: passageOffset + 1 },
    { text: 'Do the following statements agree with the information given in the Reading Passage?', offset: passageOffset + 18 },
    { text: 'In boxes 14-20 on your answer sheet, write', offset: passageOffset + 101 },
    { text: 'TRUE', offset: passageOffset + 148 },
    { text: 'if the statement agrees with the information', offset: passageOffset + 153 },
    { text: 'FALSE', offset: passageOffset + 198 },
    { text: 'if the statement contradicts the information', offset: passageOffset + 204 },
    { text: 'NOT GIVEN', offset: passageOffset + 249 },
    { text: 'if there is no information on this', offset: passageOffset + 259 },
    { text: 'The inhabitants of the Aleutian islands renamed their islands Aleyska.', offset: passageOffset + 298 },
    { text: 'Alaska’s fisheries are owned by some of the world’s largest companies.', offset: passageOffset + 375 },
    { text: 'Life in Alaska is dependent on salmon.', offset: passageOffset + 452 },
    { text: 'Ninety per cent of all Pacific salmon caught are sockeye or pink salmon.', offset: passageOffset + 490 },
    { text: 'More than 320,000 tonnes of salmon were caught in Alaska in 2000.', offset: passageOffset + 572 },
    { text: 'Between 1940 and 1959, there was a sharp decrease in Alaska’s salmon population.', offset: passageOffset + 648 },
    { text: 'During the 1990s, the average number of salmon caught each year was 100 million.', offset: passageOffset + 742 },

    // Questions 21-26: Sentence Completion
    { text: 'Questions 21-26', offset: passageOffset + 830 },
    { text: 'Complete each sentence with the correct ending, A-K, below.', offset: passageOffset + 847 },
    { text: 'Write the correct letter, A-K, in boxes 21-26 on your answer sheet.', offset: 910 },
    { text: 'In Alaska, biologists keep a check on adult fish', offset: 978 },
    { text: 'Biologists have the authority', offset: 1027 },
    { text: 'In-Season Abundance-Based Management has allowed the Alaska salmon fisheries', offset: 1062 },
    { text: 'The Marine Stewardship Council (MSC) was established', offset: 1142 },
    { text: 'As a result of the collapse of the salmon runs in 1999, the state decided', offset: 1204 },
    { text: 'In September 2000, the MSC allowed seven Alaska salmon companies', offset: 1285 },
    { text: 'to recognise fisheries that care for the environment.', offset: 1359 },
    { text: 'to be successful.', offset: 1415 },
    { text: 'to stop fish from spawning', offset: 1435 },
    { text: 'to set up environmental protection laws.', offset: 1465 },
    { text: 'to stop people fishing for sport.', offset: 1507 },
    { text: 'to label their products using the MSC logo.', offset: 1542 },
    { text: 'to ensure that fish numbers are sufficient to permit fishing.', offset: 1589 },
    { text: 'to assist the subsistence communities in the region.', offset: 1653 },
    { text: 'to freeze a huge number of salmon eggs.', offset: 1709 },
    { text: 'to deny certification to the Alaska fisheries.', offset: 1749 },
    { text: 'to close down all-fisheries.', offset: 1797 },
]);

const questions14to20 = [
    'The inhabitants of the Aleutian islands renamed their islands Aleyska.',
    'Alaska’s fisheries are owned by some of the world’s largest companies.',
    'Life in Alaska is dependent on salmon.',
    'Ninety per cent of all Pacific salmon caught are sockeye or pink salmon.',
    'More than 320,000 tonnes of salmon were caught in Alaska in 2000.',
    'Between 1940 and 1959, there was a sharp decrease in Alaska’s salmon population.',
    'During the 1990s, the average number of salmon caught each year was 100 million.',
];

const questions21to26 = [
    'In Alaska, biologists keep a check on adult fish',
    'Biologists have the authority',
    'In-Season Abundance-Based Management has allowed the Alaska salmon fisheries',
    'The Marine Stewardship Council (MSC) was established',
    'As a result of the collapse of the salmon runs in 1999, the state decided',
    'In September 2000, the MSC allowed seven Alaska salmon companies',
];

const optionsAtoK = {
    A: 'to recognise fisheries that care for the environment.',
    B: 'to be successful.',
    C: 'to stop fish from spawning',
    D: 'to set up environmental protection laws.',
    E: 'to stop people fishing for sport.',
    F: 'to label their products using the MSC logo.',
    G: 'to ensure that fish numbers are sufficient to permit fishing.',
    H: 'to assist the subsistence communities in the region.',
    I: 'to freeze a huge number of salmon eggs.',
    J: 'to deny certification to the Alaska fisheries.',
    K: 'to close down all-fisheries.',
};

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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-pink-500 to-purple-600">
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
                                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">READING PASSAGE 2</p>
                                </div>
                            </div>
                            <p class="text-sm tracking-wide text-gray-700">
                                You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.
                            </p>
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                    Endless Harvest
                                </h2>
                            </div>
                        </div>

                        <div class="flex-1 space-y-6 overflow-y-auto p-6">
                            <div ref="passageTextRef" class="space-y-6 text-base leading-relaxed select-text sm:text-lg">
                                <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-pink-50 to-purple-50 p-6 shadow-md">
                                    <div
                                        class="text-justify leading-relaxed font-medium whitespace-pre-wrap text-gray-700"
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-pink-500 to-purple-600">
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
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 14-20 -->
                                <section id="question-14-20" class="space-y-4 rounded-xl border border-blue-200 bg-blue-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-pink-600"
                                        :data-segment-text="'Questions 14-20'"
                                        v-html="getHighlightedSegment('Questions 14-20')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Do the following statements agree with the information given in the Reading Passage?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Do the following statements agree with the information given in the Reading Passage?',
                                            )
                                        "
                                    ></p>
                                    <p
                                        :data-segment-text="'In boxes 14-20 on your answer sheet, write'"
                                        v-html="getHighlightedSegment('In boxes 14-20 on your answer sheet, write')"
                                    ></p>
                                    <div class="space-y-8 rounded-lg border border-pink-200 bg-white p-8 shadow">
                                        <p>
                                            <strong
                                                class="m-2 rounded bg-green-100 p-2.5 text-green-700 shadow"
                                                :data-segment-text="'TRUE'"
                                                v-html="getHighlightedSegment('TRUE')"
                                            ></strong
                                            ><span
                                                :data-segment-text="' if the statement agrees with the information'"
                                                v-html="getHighlightedSegment(' if the statement agrees with the information')"
                                            ></span>
                                        </p>
                                        <p>
                                            <strong
                                                class="m-2 rounded bg-red-100 p-2.5 text-red-700 shadow"
                                                :data-segment-text="'FALSE'"
                                                v-html="getHighlightedSegment('FALSE')"
                                            ></strong
                                            ><span
                                                :data-segment-text="' if the statement contradicts the information'"
                                                v-html="getHighlightedSegment(' if the statement contradicts the information')"
                                            ></span>
                                        </p>
                                        <p>
                                            <strong
                                                class="m-2 rounded bg-gray-100 p-2.5 text-gray-700 shadow"
                                                :data-segment-text="'NOT GIVEN'"
                                                v-html="getHighlightedSegment('NOT GIVEN')"
                                            ></strong
                                            ><span
                                                :data-segment-text="' if there is no information on this'"
                                                v-html="getHighlightedSegment(' if there is no information on this')"
                                            ></span>
                                        </p>
                                    </div>
                                    <div class="space-y-4 pt-4">
                                        <div
                                            v-for="(question, index) in questions14to20"
                                            :key="index"
                                            class="group rounded-lg border-l-4 border-pink-400 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                                        >
                                            <div class="flex items-start gap-4">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 transform items-center justify-center rounded-full bg-pink-700 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                                    >{{ 14 + index }}</span
                                                >
                                                <p class="flex-1" :data-segment-text="question" v-html="getHighlightedSegment(question)"></p>
                                            </div>
                                            <div class="mt-3 flex space-x-4 pl-12">
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (14 + index)"
                                                        value="TRUE"
                                                        v-model="answers['q' + (14 + index)]"
                                                        class="form-radio text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <span class="text-gray-700">TRUE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (14 + index)"
                                                        value="FALSE"
                                                        v-model="answers['q' + (14 + index)]"
                                                        class="form-radio text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <span class="text-gray-700">FALSE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (14 + index)"
                                                        value="NOT GIVEN"
                                                        v-model="answers['q' + (14 + index)]"
                                                        class="form-radio text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <span class="text-gray-700">NOT GIVEN</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- Questions 21-26 -->
                                <section id="question-21-26" class="space-y-4 rounded-xl border border-purple-200 bg-purple-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-purple-800"
                                        :data-segment-text="'Questions 21-26'"
                                        v-html="getHighlightedSegment('Questions 21-26')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Complete each sentence with the correct ending, A-K, below.'"
                                        v-html="getHighlightedSegment('Complete each sentence with the correct ending, A-K, below.')"
                                    ></p>
                                    <p
                                        :data-segment-text="'Write the correct letter, A-K, in boxes 21-26 on your answer sheet.'"
                                        v-html="getHighlightedSegment('Write the correct letter, A-K, in boxes 21-26 on your answer sheet.')"
                                    ></p>
                                    <div class="rounded-xl bg-gradient-to-r from-purple-100 to-pink-50 p-4 shadow-xl">
                                        <ul class="space-y-2 text-sm">
                                            <li v-for="option in 'ABCDEFGHIJK'.split('')" :key="option">
                                                <strong class="font-semibold">{{ option }}. </strong>
                                                <span
                                                    :data-segment-text="optionsAtoK[option]"
                                                    v-html="getHighlightedSegment(optionsAtoK[option])"
                                                ></span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="space-y-4 pt-4">
                                        <div
                                            v-for="(question, index) in questions21to26"
                                            :key="index"
                                            class="group rounded-lg border-l-4 border-purple-400 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                                        >
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 transform items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                                    >{{ 21 + index }}</span
                                                >
                                                <p class="flex-1" :data-segment-text="question" v-html="getHighlightedSegment(question)"></p>
                                                <select
                                                    v-model="answers['q' + (21 + index)]"
                                                    class="w-28 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow focus:ring-2 focus:ring-pink-500"
                                                >
                                                    <option disabled value="">Select A–K</option>
                                                    <option v-for="char in 'ABCDEFGHIJK'.split('')" :key="char" :value="char">{{ char }}</option>
                                                </select>
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
            class="absolute z-[9999] w-80 rounded-lg border-2 border-blue-300 bg-white p-4 shadow-2xl"
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
</template>

<style scoped>
.select-none {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: col-resize;
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

/* Highlight functionality styles */
.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
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
</style>
