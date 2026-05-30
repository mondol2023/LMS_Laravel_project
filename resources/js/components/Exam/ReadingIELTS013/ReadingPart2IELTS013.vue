<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts011-part2-panel-width';
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
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

import { reactive } from 'vue';

const multipleAnswers = reactive({
    q21_22: [],
});

const passageText = `A. In terms of micro-renewable energy sources suitable for private use, a 15-kilowatt (kW) turbine is at the biggest end of the spectrum. With a nine metre diameter and a pole as high as a four-storey house, this is the most efficient form of wind microturbine, and the sort of thing you could install only if you had plenty of space and money. According to one estimate, a 15-kW micro-turbine (that’s one with the maximum output), costing £41,000 to purchase and a further £9,000 to install, is capable of delivering 25,000 kilowatt-hours (kWh)’ of electricity each year if placed on a suitably windy site.

B. I don’t know of any credible studies of the greenhouse gas emissions involved in producing and installing turbines, so my estimates here are going to be even more broad than usual. However, it is worth trying. If turbine manufacture is about as carbon intensive per pound sterling of product as other generators and electrical motors, which seems a reasonable assumption, the carbon intensity of manufacture will be around 640 kilograms (kg) per £1,000 of value. Installation is probably about as carbon intensive as typical construction, at around 380 kg per £1,000. That makes the carbon footprint (the total amount of greenhouse gases that installing a turbine creates) 30 tonnes.

C. The carbon savings from wind-powered electricity generation depend on the carbon intensity of the electricity that you’re replacing. Let’s assume that your generation replaces the coal-fuelled part of the country’s energy mix. In other words, if you live in the UK, let’s say that rather than replacing typical grid electricity, which comes from a mix of coal, gas, oil and renewable energy sources, the effect of your turbine is to reduce the use of coal-fired power stations. That’s reasonable, because coal is the least preferable source in the electricity mix. In this case, the carbon saving is roughly one kilogram per kWh, so you save 25 tonnes per year and pay back the embodied carbon in just 14 months - a great start.

D. The UK government has recently introduced a subsidy for renewable energy that pays individual producers 24p per energy unit on top of all the money they save on their own fuel bill, and on selling surplus electricity back to the grid at approximately 5p per unit. With all this taken into account, individuals would get back £7,250 per year on their investment. That pays back the costs in about six years. It makes good financial sense and, for people who care about the carbon savings for their own sake, it looks like a fantastic move. The carbon investment pays back in just over a year, and every year after that is a 25-tonne carbon saving. (It’s important to remember that  all these sums rely on a wind turbine having a favourable location) 

E. So, at face value, the turbine looks like a great idea environmentally, and a fairly good long-term investment economically for the person installing it. However, there is a crucial perspective missing from the analysis so far. Has the government spent its money wisely? It has invested 24p per unit into each micro-turbine. That works out at a massive £250 per tonne of carbon saved. My calculations tell me that had the government invested its money in offshore wind farms, instead of subsidising smaller domestic turbines, they would have broken even after eight years. In other words, the micro-turbine works out as a good investment for individuals, but only because the government spends, and arguably wastes, so much money subsidising it. Carbon savings are far lower too.

F. Nevertheless, although the micro-wind turbine subsidy doesn’t look like the very best way of spending government resources on climate change mitigation, we are talking about investing only about 0.075 percent per year of the nation’s GDP to get a one percent reduction in carbon emissions, which is a worthwhile benefit. In other words, it could be much better, but it could be worse. In addition, such investment helps to promote and sustain developing technology.

G. There is one extra favourable way of looking at the micro-wind turbine, even if it is not the single best way of investing money in cutting carbon. Input- output modelling has told us that it is actually quite difficult to spend money without having a negative carbon impact. So if the subsidy encourages people to spend their money on a carbon-reducing technology such as a wind turbine, rather than on carbon-producing goods like cars, and services such as overseas holidays, then the reductions in emissions will be greater than my simple sums above have suggested `;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 2', offset: 5543 },
    { text: 'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.', offset: 5562 },
    { text: 'An assessment of micro-wind turbines', offset: 5658 },
    { text: 'Questions 14-20', offset: 5683 },
    { text: 'Reading Passage 2 has SEVEN paragraphs, A-G.', offset: 5700 },
    { text: 'Choose the correct heading for each paragraph from the list of headings below.', offset: 5746 },
    { text: 'Write the correct number (i-ix) in boxes 14-20 on your answer sheet.', offset: 5828 },
    { text: 'List of Headings', offset: 5903 },
    { text: 'i. A better use for large sums of money.', offset: 5920 },
    { text: 'ii. The environmental costs of manufacture and installation.', offset: 5962 },
    { text: 'iii. Estimates of the number of micro-turbines in use.', offset: 6023 },
    { text: 'iv. The environmental benefits of running a micro-turbine.', offset: 6081 },
    { text: 'v. The size and output of the largest type of micro-turbine.', offset: 6142 },
    { text: 'vi. A limited case for subsidising micro-turbines.', offset: 6207 },
    { text: 'vii. Recent improvements in the design of micro-turbines.', offset: 6261 },
    { text: 'viii. An indirect method of reducing carbon emissions.', offset: 6323 },
    { text: 'ix. The financial benefits of running a micro-turbine.', offset: 6380 },
    { text: 'Paragraph A', offset: 6440 },
    { text: 'Paragraph B', offset: 6453 },
    { text: 'Paragraph C', offset: 6466 },
    { text: 'Paragraph D', offset: 6479 },
    { text: 'Paragraph E', offset: 6492 },
    { text: 'Paragraph F', offset: 6505 },
    { text: 'Paragraph G', offset: 6518 },
    { text: 'Questions 21-22', offset: 6529 },
    { text: 'Choose TWO letters, A-E.', offset: 6544 },
    { text: 'The list below contains some possible statements about micro wind-turbines.', offset: 6569 },
    { text: 'Which TWO of these statements are made by the writer of the passage?', offset: 6644 },
    { text: 'A. In certain areas, permission is required to install them.', offset: 6713 },
    { text: 'B. Their exact energy output depends on their position.', offset: 6771 },
    { text: 'C. They probably take less energy to make than other engines.', offset: 6825 },
    { text: 'D. The UK government contributes towards their purchase cost.', offset: 6887 },
    { text: 'E. They can produce more energy than a household needs.', offset: 6946 },
    { text: 'Questions 23-26', offset: 7001 },
    { text: 'Complete the sentences below.', offset: 7016 },
    { text: 'Choose NO MORE THAN THREE WORDS from the passage for each answer.', offset: 7044 },
    { text: 'would be a more effective target for government investment than micro-turbines.', offset: 7109 },
    { text: 'An indirect benefit of subsidising micro-turbines is the support it provides for', offset: 7193 },
    { text: 'Most spending has a effect on the environment.', offset: 7276 },
    { text: 'If people buy a micro-turbine, they have less money to spend on things like foreign holidays and', offset: 7320 },
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
                                :data-segment-text="'You should spend about 20 minutes on Questions 14-20 which are based on Reading Passage 2 below.'"
                                v-html="
                                    getHighlightedSegment(
                                        'You should spend about 20 minutes on Questions 14-20 which are based on Reading Passage 2 below.',
                                    )
                                "
                            ></p>
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                    <span
                                        :data-segment-text="'An assessment of micro-wind turbines'"
                                        v-html="getHighlightedSegment('An assessment of micro-wind turbines')"
                                    ></span>
                                </h2>
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
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-pink-50 shadow-lg"
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
                        <div class="flex-1 overflow-y-auto bg-pink-50 pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 14-20 -->
                                <div
                                    class="rounded-xl border border-pink-200 bg-gradient-to-r from-purple-50 to-pink-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <div class="mb-4 flex items-center space-x-2">
                                            <h3 class="bg-gradient-to-r from-purple-500 to-pink-600 bg-clip-text text-lg font-bold text-transparent">
                                                <span :data-segment-text="'Questions 14-20'" v-html="getHighlightedSegment('Questions 14-20')"></span>
                                            </h3>
                                        </div>
                                        <div class="rounded-xl border border-pink-200 bg-white p-6 shadow-sm">
                                            <p
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Reading Passage 2 has SEVEN paragraphs, A-G.'"
                                                v-html="getHighlightedSegment('Reading Passage 2 has SEVEN paragraphs, A-G.')"
                                            ></p>
                                            <p
                                                class="mt-2 text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Choose the correct heading for each paragraph from the list of headings below.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Choose the correct heading for each paragraph from the list of headings below.',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="mt-2 text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Write the correct number (i-ix) in boxes 14-20 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write the correct number (i-ix) in boxes 14-20 on your answer sheet.')"
                                            ></p>
                                        </div>
                                    </div>

                                    <div class="mb-6 rounded-xl bg-gradient-to-r from-purple-100 to-pink-50 p-4 shadow-xl">
                                        <h4
                                            class="mb-2 text-base font-semibold"
                                            :data-segment-text="'List of Headings'"
                                            v-html="getHighlightedSegment('List of Headings')"
                                        ></h4>
                                        <ul class="space-y-1 text-base text-gray-700">
                                            <li
                                                :data-segment-text="'i. A better use for large sums of money.'"
                                                v-html="getHighlightedSegment('i. A better use for large sums of money.')"
                                            ></li>
                                            <li
                                                :data-segment-text="'ii. The environmental costs of manufacture and installation.'"
                                                v-html="getHighlightedSegment('ii. The environmental costs of manufacture and installation.')"
                                            ></li>
                                            <li
                                                :data-segment-text="'iii. Estimates of the number of micro-turbines in use.'"
                                                v-html="getHighlightedSegment('iii. Estimates of the number of micro-turbines in use.')"
                                            ></li>
                                            <li
                                                :data-segment-text="'iv. The environmental benefits of running a micro-turbine.'"
                                                v-html="getHighlightedSegment('iv. The environmental benefits of running a micro-turbine.')"
                                            ></li>
                                            <li
                                                :data-segment-text="'v. The size and output of the largest type of micro-turbine.'"
                                                v-html="getHighlightedSegment('v. The size and output of the largest type of micro-turbine.')"
                                            ></li>
                                            <li
                                                :data-segment-text="'vi. A limited case for subsidising micro-turbines.'"
                                                v-html="getHighlightedSegment('vi. A limited case for subsidising micro-turbines.')"
                                            ></li>
                                            <li
                                                :data-segment-text="'vii. Recent improvements in the design of micro-turbines.'"
                                                v-html="getHighlightedSegment('vii. Recent improvements in the design of micro-turbines.')"
                                            ></li>
                                            <li
                                                :data-segment-text="'viii. An indirect method of reducing carbon emissions.'"
                                                v-html="getHighlightedSegment('viii. An indirect method of reducing carbon emissions.')"
                                            ></li>
                                            <li
                                                :data-segment-text="'ix. The financial benefits of running a micro-turbine.'"
                                                v-html="getHighlightedSegment('ix. The financial benefits of running a micro-turbine.')"
                                            ></li>
                                        </ul>
                                    </div>

                                    <div class="space-y-4">
                                        <div
                                            v-for="i in 7"
                                            :key="i"
                                            class="rounded-xl border-l-4 border-pink-500 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                        >
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-500 font-semibold text-white shadow-lg"
                                                    style="
                                                        box-shadow:
                                                            0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                    "
                                                    >{{ 13 + i }}</span
                                                >
                                                <span
                                                    class="flex-1 font-medium text-gray-700"
                                                    :data-segment-text="`Paragraph ${String.fromCharCode(64 + i)}`"
                                                    v-html="getHighlightedSegment(`Paragraph ${String.fromCharCode(64 + i)}`)"
                                                ></span>
                                                <select
                                                    v-model="answers[`q${13 + i}`]"
                                                    class="w-28 rounded-xl border border-pink-300 bg-pink-50 px-2 py-1 text-sm shadow focus:ring-2 focus:ring-pink-500"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option value="i">i</option>
                                                    <option value="ii">ii</option>
                                                    <option value="iii">iii</option>
                                                    <option value="iv">iv</option>
                                                    <option value="v">v</option>
                                                    <option value="vi">vi</option>
                                                    <option value="vii">vii</option>
                                                    <option value="viii">viii</option>
                                                    <option value="ix">ix</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Questions 21-22 -->
                                <div
                                    id="question-21-22"
                                    class="rounded-xl border-2 border-pink-300 bg-gradient-to-br from-purple-50 to-pink-100 p-4 shadow-xl sm:p-6"
                                >
                                    <div class="mb-4 flex items-center space-x-3">
                                        <div
                                            class="flex h-10 w-14 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg"
                                        >
                                            <span class="text-base font-bold text-white">21-22</span>
                                        </div>
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 21-22'"
                                            v-html="getHighlightedSegment('Questions 21-22')"
                                        ></h3>
                                    </div>
                                    <p
                                        class="mb-2 font-semibold text-gray-800"
                                        :data-segment-text="'Choose TWO letters, A-E.'"
                                        v-html="getHighlightedSegment('Choose TWO letters, A-E.')"
                                    ></p>
                                    <p
                                        class="mb-2 text-gray-700"
                                        :data-segment-text="'The list below contains some possible statements about micro wind-turbines.'"
                                        v-html="getHighlightedSegment('The list below contains some possible statements about micro wind-turbines.')"
                                    ></p>
                                    <p
                                        class="mb-4 text-gray-700"
                                        :data-segment-text="'Which TWO of these statements are made by the writer of the passage?'"
                                        v-html="getHighlightedSegment('Which TWO of these statements are made by the writer of the passage?')"
                                    ></p>
                                    <div class="space-y-3">
                                        <label
                                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                        >
                                            <input
                                                type="checkbox"
                                                value="A"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('A')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="font-medium text-gray-800"
                                                :data-segment-text="'A. In certain areas, permission is required to install them.'"
                                                v-html="getHighlightedSegment('A. In certain areas, permission is required to install them.')"
                                            ></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                        >
                                            <input
                                                type="checkbox"
                                                value="B"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('B')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="font-medium text-gray-800"
                                                :data-segment-text="'B. Their exact energy output depends on their position.'"
                                                v-html="getHighlightedSegment('B. Their exact energy output depends on their position.')"
                                            ></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                        >
                                            <input
                                                type="checkbox"
                                                value="C"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('C')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="font-medium text-gray-800"
                                                :data-segment-text="'C. They probably take less energy to make than other engines.'"
                                                v-html="getHighlightedSegment('C. They probably take less energy to make than other engines.')"
                                            ></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                        >
                                            <input
                                                type="checkbox"
                                                value="D"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('D')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="font-medium text-gray-800"
                                                :data-segment-text="'D. The UK government contributes towards their purchase cost.'"
                                                v-html="getHighlightedSegment('D. The UK government contributes towards their purchase cost.')"
                                            ></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                        >
                                            <input
                                                type="checkbox"
                                                value="E"
                                                v-model="multipleAnswers.q21_22"
                                                :disabled="multipleAnswers.q21_22.length >= 2 && !multipleAnswers.q21_22.includes('E')"
                                                class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                            />
                                            <span
                                                class="font-medium text-gray-800"
                                                :data-segment-text="'E. They can produce more energy than a household needs.'"
                                                v-html="getHighlightedSegment('E. They can produce more energy than a household needs.')"
                                            ></span>
                                        </label>
                                    </div>
                                    <div class="mt-4 rounded-lg border border-pink-200 bg-white p-3 shadow">
                                        <p class="font-medium text-pink-700">Selected: {{ multipleAnswers.q21_22.length }}/2 answers</p>
                                    </div>
                                </div>

                                <!-- Questions 23-26 -->
                                <div
                                    class="rounded-xl border border-pink-200 bg-gradient-to-r from-purple-50 to-pink-100 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <div class="mb-4 flex items-center space-x-2">
                                            <h3
                                                class="bg-gradient-to-r from-purple-500 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                                :data-segment-text="'Questions 23-26'"
                                                v-html="getHighlightedSegment('Questions 23-26')"
                                            ></h3>
                                        </div>
                                        <div class="rounded-xl border border-pink-200 bg-white p-6 shadow-sm">
                                            <p
                                                class="text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Complete the sentences below.'"
                                                v-html="getHighlightedSegment('Complete the sentences below.')"
                                            ></p>
                                            <p
                                                class="mt-2 text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Choose NO MORE THAN THREE WORDS from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose NO MORE THAN THREE WORDS from the passage for each answer.')"
                                            ></p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div
                                            class="rounded-xl border-l-4 border-pink-500 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                        >
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-500 font-semibold text-white shadow-lg"
                                                    style="
                                                        box-shadow:
                                                            0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                    "
                                                    >23</span
                                                >
                                                <div class="flex-1">
                                                    <input
                                                        v-model="answers.q23"
                                                        type="text"
                                                        class="w-full rounded-full border-2 border-pink-200 bg-pink-50 px-3 py-2 text-sm shadow transition-colors focus:border-pink-500 focus:bg-white focus:outline-none"
                                                        placeholder=""
                                                    />
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-text="'would be a more effective target for government investment than micro-turbines.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'would be a more effective target for government investment than micro-turbines.',
                                                            )
                                                        "
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="rounded-xl border-l-4 border-pink-500 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                        >
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-500 font-semibold text-white shadow-lg"
                                                    style="
                                                        box-shadow:
                                                            0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                    "
                                                    >24</span
                                                >
                                                <div class="flex-1">
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-text="'An indirect benefit of subsidising micro-turbines is the support it provides for'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'An indirect benefit of subsidising micro-turbines is the support it provides for',
                                                            )
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q24"
                                                        type="text"
                                                        class="w-full rounded-full border-2 border-pink-200 bg-pink-50 px-3 py-2 text-sm shadow transition-colors focus:border-pink-500 focus:bg-white focus:outline-none"
                                                        placeholder=""
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="rounded-xl border-l-4 border-pink-500 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                        >
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-500 font-semibold text-white shadow-lg"
                                                    style="
                                                        box-shadow:
                                                            0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                    "
                                                    >25</span
                                                >
                                                <div class="flex-1">
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-text="'Most spending has a'"
                                                        v-html="getHighlightedSegment('Most spending has a')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q25"
                                                        type="text"
                                                        class="mx-2 w-48 rounded-full border-2 border-pink-200 bg-pink-50 px-3 py-2 text-sm shadow transition-colors focus:border-pink-500 focus:bg-white focus:outline-none"
                                                        placeholder=""
                                                    />
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-text="'effect on the environment.'"
                                                        v-html="getHighlightedSegment('effect on the environment.')"
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="rounded-xl border-l-4 border-pink-500 bg-white p-3 shadow-lg transition-shadow duration-300 hover:shadow-2xl"
                                        >
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-500 font-semibold text-white shadow-lg"
                                                    style="
                                                        box-shadow:
                                                            0 4px 6px -1px rgba(236, 72, 153, 0.5),
                                                            0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                                    "
                                                    >26</span
                                                >
                                                <div class="flex-1">
                                                    <span
                                                        class="text-gray-700"
                                                        :data-segment-text="'If people buy a micro-turbine, they have less money to spend on things like foreign holidays and'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'If people buy a micro-turbine, they have less money to spend on things like foreign holidays and',
                                                            )
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q26"
                                                        type="text"
                                                        class="w-full rounded-full border-2 border-pink-200 bg-pink-50 px-3 py-2 text-sm shadow transition-colors focus:border-pink-500 focus:bg-white focus:outline-none"
                                                        placeholder=""
                                                    />
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
