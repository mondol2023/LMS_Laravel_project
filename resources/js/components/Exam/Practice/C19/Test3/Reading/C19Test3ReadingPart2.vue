<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Props for draft system
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

// Reading Part 2 component

// Draft auto-saver

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-c19t3-part2-panel-width';
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

const passageText = `A Wetlands are areas where water covers the soil, or is present either at or near the surface of the soil, for all or part of the year. These are complex ecosystems, rich in unique plant and animal life. But according to the World Wide Fund for Nature (WWFN), half of the world\u2019s wetlands have disappeared since 1990 \u2013 converted or destroyed for commercial development, drainage schemes and the extraction of minerals and peat*. Many of those that remain have been damaged by agricultural pesticides and fertilizers, industrial pollutants, and construction works.

* peat: a brown deposit formed by the partial decomposition of vegetation in wet acidic conditions, often cut out and dried for use as fuel

B Throughout history, humans have gathered around wetlands, and their fertile ecosystems have played an important part in human development. Consequently, they are of considerable religious, historical and archaeological value to many communities around the world. \u2018Wetlands directly support the livelihoods and well-being of millions of people,\u2019 says Dr Matthew McCartney, principal researcher and hydrologist at the International Water Management Institute (IWMI). \u2018In many developing countries, large numbers of people are dependent on wetland agriculture for their livelihoods.\u2019

C They also serve a crucial environmental purpose. \u2018Wetlands are one of the key tools in mitigating climate change across the planet,\u2019 says Pieter van Eijk, head of Climate Adaptation at Wetlands International (WI), pointing to their use as buffers that protect coastal areas from sea-level rise and extreme weather events such as hurricanes and flooding. Wetland coastal forests provide food and water, as well as shelter from storms, and WI and other agencies are working to restore those forests which have been lost. \u2018It can be as simple as planting a few trees per hectare to create shade and substantially change a microclimate,\u2019 he says. \u2018Implementing climate change projects isn\u2019t so much about money.\u2019

D The world\u2019s wetlands are, unfortunately, rich sources for in-demand commodities, such as palm oil and pulpwood. Peatlands \u2013 wetlands with a waterlogged organic soil layer \u2013 are particularly targeted. When peatlands are drained for cultivation, they become net carbon emitters instead of active carbon stores, and, according to Marcel Silvius, head of Climate-smart Land-use at WI, this practice causes six per cent of all global carbon emissions. The clearance of peatlands for planting also increases the risk of forest fires, which release huge amounts of CO\u2082. \u2018We\u2019re seeing huge peatland forests with extremely high biodiversity value being lost for a few decades of oil palm revenues,\u2019 says Silvius.

E The damage starts when logging companies arrive to clear the trees. They dig ditches to enter the peat swamps by boat and then float the logs out the same way. These are then used to drain water out of the peatlands to allow for the planting of corn, oil palms or pulpwood trees. Once the water has drained away, bacteria and fungi then break down the carbon in the peat and turn it into CO\u2082 and methane. Meanwhile, the remainder of the solid matter in the peat starts to move downwards, in a process known as subsidence. Peat comprises 90 per cent water, so this is one of the most alarming consequences of peatland clearances. \u2018In the tropics, peat subsides at about four centimetres a year, so within half a century, very large landscapes on Sumatra and Borneo will become flooded as the peat drops below water level,\u2019 says Silvius. \u2018It\u2019s a huge catastrophe that\u2019s in preparation. Some provinces will lose 40 per cent of their landmass.\u2019

F And while these industries affect wetlands in ways that can easily be documented, Dr Dave Tickner of the WWFN believes that more subtle impacts can be even more devastating. \u2018Sediment run-off and fertilizers can be pretty invisible,\u2019 says Tickner. \u2018Over-extraction of water is equally invisible. You do get shock stories about rivers running red, or even catching fire, but there\u2019s seldom one big impact that really hurts a wetland.\u2019 Tickner does not blame anyone for deliberate damage, however. \u2018I\u2019ve worked on wetland issues for 20 years and have never met anybody who wanted to damage a wetland,\u2019 he says. \u2018It isn\u2019t something that people generally set out to do. Quite often, the effects simply come from people trying to make a living.\u2019

G Silvius also acknowledges the importance of income generation. \u2018It\u2019s not that we just want to restore the biodiversity of wetlands \u2013 which we do \u2013 but we recognise there\u2019s a need to provide an income for local people.\u2019 This approach is supported by IWMI. \u2018The idea is that people in a developing country will only protect wetlands if they value and profit from them,\u2019 says McCartney. \u2018For sustainability, it\u2019s essential that local people are involved in wetland planning and decision making and have clear rights to use wetlands.\u2019

H The fortunes of wetlands would be improved, Silvius suggests, if more governments recognized their long-term value. \u2018Different governments have different attitudes,\u2019 he says, and goes on to explain that some countries place a high priority on restoring wetlands, while others still deny the issue. McCartney is cautiously optimistic, however. \u2018Awareness of the importance of wetlands is growing,\u2019 he says. \u2018It\u2019s true that wetland degradation still continues at a rapid pace, but my impression is that things are slowly changing.\u2019`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 2', offset: 5600 },
    { text: 'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.', offset: 5650 },
    { text: 'The global importance of wetlands', offset: 5800 },
    { text: 'Questions 14\u201317', offset: 5900 },
    { text: 'Reading Passage 2 has eight paragraphs, A\u2013H.', offset: 5950 },
    { text: 'Which paragraph contains the following information?', offset: 6050 },
    { text: 'Write the correct letter, A\u2013H, in boxes 14\u201317 on your answer sheet.', offset: 6150 },
    { text: 'reference to the need to ensure that inhabitants of wetland regions continue to benefit from them', offset: 6350 },
    { text: 'the proportion of wetlands which have already been lost', offset: 6450 },
    { text: 'reference to the idea that people are beginning to appreciate the value of wetlands', offset: 6550 },
    { text: 'mention of the cultural significance of wetlands', offset: 6650 },
    { text: 'Questions 18\u201322', offset: 6900 },
    { text: 'Complete the sentences below.', offset: 6950 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 7000 },
    { text: 'Write your answers in boxes 18\u201322 on your answer sheet.', offset: 7100 },
    { text: 'Peatlands which have been drained begin to release ', offset: 7200 },
    { text: 'Once peatland areas have been cleared, ', offset: 7400 },
    { text: 'Clearing peatland forests to make way for oil palm plantations destroys the ', offset: 7550 },
    { text: 'Water is drained out of peatlands through the ', offset: 7750 },
    { text: 'Draining peatlands leads to ', offset: 7950 },
    { text: 'Questions 23\u201326', offset: 8200 },
    { text: 'Look at the following statements (Questions 23\u201326) and the list of experts below.', offset: 8250 },
    { text: 'Match each statement with the correct expert, A\u2013D.', offset: 8350 },
    { text: 'Write the correct letter, A\u2013D, in boxes 23\u201326 on your answer sheet.', offset: 8450 },
    { text: 'List of Experts', offset: 8550 },
    { text: 'A Matthew McCartney', offset: 8600 },
    { text: 'B Pieter van Eijk', offset: 8650 },
    { text: 'C Marcel Silvius', offset: 8700 },
    { text: 'D Dave Tickner', offset: 8750 },
    { text: 'Communities living in wetland regions must be included in discussions about the future of these areas.', offset: 8850 },
    { text: 'Official policies towards wetlands vary from one nation to the next.', offset: 8950 },
    { text: 'People cause harm to wetlands without having any intention to do so.', offset: 9050 },
    { text: 'Initiatives to reverse environmental damage need not be complex.', offset: 9150 },
]);

const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + segmentText.length;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    if (overlappingHighlights.length === 0) {
        return segmentText;
    }

    const sorted = [...overlappingHighlights].sort((a, b) => b.start_offset - a.start_offset);

    let result = segmentText;
    for (const highlight of sorted) {
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-purple-500 to-pink-600">
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
                                        :data-segment-text="'READING PASSAGE 2'"
                                        v-html="getHighlightedSegment('READING PASSAGE 2')"
                                    ></p>
                                </div>
                            </div>
                            <p
                                class="text-sm tracking-wide text-gray-700"
                                :data-segment-text="'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.'"
                                v-html="
                                    getHighlightedSegment(
                                        'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.',
                                    )
                                "
                            ></p>
                            <div class="flex flex-col items-center justify-between gap-2">
                                <h2 class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                    <span
                                        :data-segment-text="'The global importance of wetlands'"
                                        v-html="getHighlightedSegment('The global importance of wetlands')"
                                    ></span>
                                </h2>
                                <button
                                    class="self-end rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200 sm:text-sm"
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
                                <div class="rounded-xl border border-purple-100 bg-gradient-to-br from-purple-50 to-pink-50 p-6 shadow-md">
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
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-pink-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-pink-500"></div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col gap-1">
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-pink-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-pink-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-pink-600"></div>
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
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-purple-500 to-pink-600">
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
                                    <p class="text-xs text-gray-500">Answer all questions based on Reading Passage 2</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 14-17 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-14-17" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 14\u201317'"
                                            v-html="getHighlightedSegment('Questions 14\u201317')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Reading Passage 2 has eight paragraphs, A\u2013H.'"
                                                v-html="getHighlightedSegment('Reading Passage 2 has eight paragraphs, A\u2013H.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Which paragraph contains the following information?'"
                                                v-html="getHighlightedSegment('Which paragraph contains the following information?')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A\u2013H, in boxes 14\u201317 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Write the correct letter, A\u2013H, in boxes 14\u201317 on your answer sheet.',
                                                    )
                                                "
                                            ></p>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                v-for="n in 4"
                                                :key="n"
                                                :id="'question-' + (13 + n)"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                    >{{ 13 + n }}</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="textSegments.find((s) => s.offset === [6350, 6450, 6550, 6650][n - 1])?.text"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            textSegments.find((s) => s.offset === [6350, 6450, 6550, 6650][n - 1])?.text || '',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers['q' + (13 + n)]"
                                                    class="w-24 rounded-xl border border-pink-300 bg-pink-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 18-22 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-18-22" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 18\u201322'"
                                            v-html="getHighlightedSegment('Questions 18\u201322')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the sentences below.'"
                                                v-html="getHighlightedSegment('Complete the sentences below.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm font-semibold text-red-500"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write your answers in boxes 18\u201322 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write your answers in boxes 18\u201322 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Question 18 -->
                                            <div
                                                id="question-18"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >18</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'Peatlands which have been drained begin to release '"
                                                            v-html="getHighlightedSegment('Peatlands which have been drained begin to release ')"
                                                        ></span>
                                                        <input
                                                            v-model="answers.q18"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                        <span> instead of storing it.</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Question 19 -->
                                            <div
                                                id="question-19"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >19</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'Once peatland areas have been cleared, '"
                                                            v-html="getHighlightedSegment('Once peatland areas have been cleared, ')"
                                                        ></span>
                                                        <input
                                                            v-model="answers.q19"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                        <span> are more likely to occur.</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Question 20 -->
                                            <div
                                                id="question-20"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >20</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'Clearing peatland forests to make way for oil palm plantations destroys the '"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'Clearing peatland forests to make way for oil palm plantations destroys the ',
                                                                )
                                                            "
                                                        ></span>
                                                        <input
                                                            v-model="answers.q20"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                        <span> of the local environment.</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Question 21 -->
                                            <div
                                                id="question-21"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >21</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'Water is drained out of peatlands through the '"
                                                            v-html="getHighlightedSegment('Water is drained out of peatlands through the ')"
                                                        ></span>
                                                        <input
                                                            v-model="answers.q21"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                        <span> which are created by logging companies.</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Question 22 -->
                                            <div
                                                id="question-22"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >22</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'Draining peatlands leads to '"
                                                            v-html="getHighlightedSegment('Draining peatlands leads to ')"
                                                        ></span>
                                                        <input
                                                            v-model="answers.q22"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                        <span>
                                                            : a serious problem which can eventually result in coastal flooding and land loss.</span
                                                        >
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 23-26 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-23-26" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 23\u201326'"
                                            v-html="getHighlightedSegment('Questions 23\u201326')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Look at the following statements (Questions 23\u201326) and the list of experts below.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Look at the following statements (Questions 23\u201326) and the list of experts below.',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Match each statement with the correct expert, A\u2013D.'"
                                                v-html="getHighlightedSegment('Match each statement with the correct expert, A\u2013D.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A\u2013D, in boxes 23\u201326 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Write the correct letter, A\u2013D, in boxes 23\u201326 on your answer sheet.',
                                                    )
                                                "
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'List of Experts'"
                                                v-html="getHighlightedSegment('List of Experts')"
                                            ></p>
                                            <ul class="space-y-1 text-gray-700">
                                                <li
                                                    :data-segment-text="'A Matthew McCartney'"
                                                    v-html="getHighlightedSegment('A Matthew McCartney')"
                                                ></li>
                                                <li :data-segment-text="'B Pieter van Eijk'" v-html="getHighlightedSegment('B Pieter van Eijk')"></li>
                                                <li :data-segment-text="'C Marcel Silvius'" v-html="getHighlightedSegment('C Marcel Silvius')"></li>
                                                <li :data-segment-text="'D Dave Tickner'" v-html="getHighlightedSegment('D Dave Tickner')"></li>
                                            </ul>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                id="question-23"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >23</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="'Communities living in wetland regions must be included in discussions about the future of these areas.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'Communities living in wetland regions must be included in discussions about the future of these areas.',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers.q23"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                            <div
                                                id="question-24"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >24</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="'Official policies towards wetlands vary from one nation to the next.'"
                                                    v-html="
                                                        getHighlightedSegment('Official policies towards wetlands vary from one nation to the next.')
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers.q24"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                            <div
                                                id="question-25"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >25</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="'People cause harm to wetlands without having any intention to do so.'"
                                                    v-html="
                                                        getHighlightedSegment('People cause harm to wetlands without having any intention to do so.')
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers.q25"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                            <div
                                                id="question-26"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >26</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="'Initiatives to reverse environmental damage need not be complex.'"
                                                    v-html="getHighlightedSegment('Initiatives to reverse environmental damage need not be complex.')"
                                                ></p>
                                                <select
                                                    v-model="answers.q26"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D']" :key="i" :value="i">{{ i }}</option>
                                                </select>
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
