<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const PANEL_WIDTH_KEY = 'reading-ielts015-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight } = useHighlight();

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

const passageText = `For many environmentalists, the world seems to be getting worse. They have developed a hit-list of our main fears: that natural resources are running out; that the population is ever growing, leaving less and less to eat; that species are becoming extinct in vast numbers, and that the planet’s air and water are becoming ever more polluted.

But a quick look at the facts shows a different picture. First, energy and other natural resources have become more abundant, not less so, since the book ‘The limits to Growth’ was published in 1972 by a group of scientists. Second, more food is now produced per head of the world’s population than at any time in history. Fewer people are starving. Third, although species are indeed becoming extinct, only about 0.7% of them are expelled to disappear in the next 50 years, not 25-50%, as has so often been predicted. And finally, most forms of environmental pollution either appear to have been exaggerated, or are transient – associated with the early phases of industrialisation and therefore best cured not by restricting economic growth, but by accelerating it. One form of pollution – the release of greenhouse gases that causes global warming – does appear to be a phenomenon that is going to extend well into our future, but its total impact is unlikely to pose a devastating problem. A bigger problem may well turn out to be an inappropriate response to it.

Yet opinion polls suggest that many people nurture the belief that environmental standards are declining and four factors seem to cause this disjunction between perception and reality.

One is the lopsidedness built into scientific research. Scientific funding goes mainly to areas with many problems. That may be wise policy but it will also create an impression that many more potential problems exist than is the case.

Secondly, environmental groups need to be noticed by the mass media. They also need to keep the money rolling in. Understandably, perhaps, they sometimes overstate their arguments. In 1997, for example, the World Wide Fund for Nature issued a press release entitled: ‘Two thirds of the world’s forests lost forever’. The truth turns out to be nearer 20%.

Though these groups are run overwhelmingly by selfless folk, they nevertheless share many of the characteristics of other lobby groups. That would matter less if people applied the same degree of scepticism to environmental lobbying as they do to lobby groups in other fields. A trade organisation arguing for, say, weaker pollution control is instantly seen as self- interested. Yet a green organisation opposing such a weakening is seen as altruistic, even if an impartial view of the controls in question might suggest they are doing more harm than good.

A third source of confusion is the attitude of the media. People are dearly more curious about bad news than good. Newspapers and broadcasters are there to provide what the public wants. That, however, can lead to significant distortions of perception. An example was America’s encounter with EI Nino in 1997 and 1998. This climatic phenomenon was accused of wrecking tourism, causing allergies, melting the ski-slopes, and causing 22 deaths. However, according to an article in the Bulletin of the American Meteorological Society, the damage it did was estimated at US$4 billion but the benefits amounted to some US$19 billion. These came from higher winter temperatures (which saved an estimated 850 lives, reduced heating costs and diminished spring floods caused by meltwaters).

The fourth factor is poor individual perception. People worry that the endless rise in the amount of stuff everyone throws away will cause the world to run out of places to dispose of waste. Yet, even if America’s trash output continues to rise as it has done in the past, and even if the American population doubles by 2100, all the rubbish America produces through the entire 21st century will still take up only one-12,000th of the area of the entire United States.

So what of global warming? As we know, carbon dioxide emissions are causing the planet to warm. The best estimates are that the temperatures will rise by 2-3°C in this century, causing considerable problems, at a total cost of US$5,000 billion. Despite the intuition that something drastic needs to be done about such a costly problem, economic analyses dearly show it will be far more expensive to cut carbon dioxide emissions radically than to pay the costs of adaptation to the increased temperatures. A model by one of the main authors of the United Nations Climate Change Panel shows how an expected temperature increase of 2.1 degrees in 2100 would only be diminished to an increase of 1.9 degrees. Or to put it another way, the temperature increase that the planet would have experienced in 2094 would be postponed to 2100.

So this does not prevent global warming, but merely buys the world six years. Yet the cost of reducing carbon dioxide emissions, for the United States alone, will be higher than the cost of solving the world’s single, most pressing health problem: providing universal access to clean drinking water and sanitation. Such measures would avoid 2 million deaths every year, and prevent half a billion people from becoming seriously ill.

It is crucial that we look at the facts if we want to make the best possible decisions for the future. It may be costly to be overly optimistic – but more costly still to be too pessimistic.`;

const passageOffset = passageText.length;
const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'Questions 27-32', offset: passageOffset + 1 },
    { text: 'Do the following statements agree with the information given in Reading Passage 3?', offset: passageOffset + 18 },
    { text: 'In boxes 27-32 on your answer sheet, write', offset: passageOffset + 103 },
    { text: 'YES', offset: passageOffset + 148 },
    { text: ' if the statement agrees with the information', offset: passageOffset + 154 },
    { text: 'NO', offset: passageOffset + 201 },
    { text: ' if the statement contradicts the information', offset: passageOffset + 204 },
    { text: 'NOT GIVEN', offset: passageOffset + 252 },
    { text: 'if there is no information on this', offset: passageOffset + 262 },
    { text: 'Environmentalists take a pessimistic view of the world for a number of reasons.', offset: passageOffset + 331 },
    { text: "Data on the Earth's natural resources has only been collected since 1972.", offset: passageOffset + 412 },
    { text: 'The number of starving people in the world has increased in recent years.', offset: passageOffset + 488 },
    { text: 'Extinct species are being replaced by new species.', offset: passageOffset + 561 },
    { text: 'Some pollution problems have been correctly linked to industrialisation.', offset: passageOffset + 612 },
    { text: 'It would be best to attempt to slow down economic growth.', offset: passageOffset + 685 },
    { text: 'Questions 33-37', offset: passageOffset + 742 },
    { text: 'Choose the correct letter, A, B, C or D. Write your answers in boxes 33-37 on your answer sheet.', offset: passageOffset + 759 },
    { text: 'What aspect of scientific research does the writer express concern about in paragraph 4?', offset: passageOffset + 855 },
    { text: 'A the need to produce results', offset: passageOffset + 942 },
    { text: 'B the lack of financial support', offset: passageOffset + 972 },
    { text: 'C the selection of areas to research', offset: passageOffset + 1006 },
    { text: 'D the desire to solve every research problem', offset: passageOffset + 1044 },
    { text: 'The writer quotes from the Worldwide Fund for Nature to illustrate how', offset: passageOffset + 1094 },
    { text: 'A influential the mass media can be', offset: passageOffset + 1167 },
    { text: 'B effective environmental groups can be', offset: passageOffset + 1204 },
    { text: 'C the mass media can help groups raise funds', offset: passageOffset + 1243 },
    { text: 'D environmental groups can exaggerate their claims', offset: passageOffset + 1289 },
    { text: "What is the writer's main point about lobby groups in paragraph 6?", offset: passageOffset + 1344 },
    { text: 'A Some are more active than others', offset: passageOffset + 1414 },
    { text: 'B Some are better organised than others', offset: passageOffset + 1450 },
    { text: 'C Some receive more criticism than others', offset: passageOffset + 1490 },
    { text: 'D Some support more important issues than others', offset: passageOffset + 1535 },
    { text: 'The writer suggests that newspapers print items that are intended to', offset: passageOffset + 1592 },
    { text: 'A educate readers', offset: passageOffset + 1660 },
    { text: "B meet their readers' expectations", offset: passageOffset + 1679 },
    { text: 'C encourage feedback from readers', offset: passageOffset + 1714 },
    { text: 'D mislead readers', offset: passageOffset + 1748 },
    { text: "What does the writer say about America's waste problem?", offset: passageOffset + 1768 },
    { text: 'A It will increase in line with population growth', offset: passageOffset + 1827 },
    { text: 'B It is not as important as we have been led to believe', offset: passageOffset + 1878 },
    { text: 'C It has been reduced through public awareness of the issues', offset: passageOffset + 1937 },
    { text: 'D It is only significant in certain areas of the country', offset: passageOffset + 2004 },
    { text: 'Questions 38-40', offset: passageOffset + 2058 },
    {
        text: 'Complete the summary with the list of words A-I below. Write the correct letter A-I in boxes 38-40 on your answer sheet.',
        offset: passageOffset + 2075,
    },
    { text: 'GLOBAL WARMING', offset: passageOffset + 2195 },
    { text: 'The writer admits that global warming is a ', offset: passageOffset + 2211 },
    { text: '38', offset: passageOffset + 2253 },
    {
        text: 'challenge, but says that it will not have a catastrophic impact on our future, if we deal with it in the ',
        offset: passageOffset + 2256,
    },
    { text: '39', offset: passageOffset + 2369 },
    {
        text: 'way. If we try to reduce the levels of greenhouse gases, he believes that it would only have a minimal impact on rising temperatures. He feels it would be better to spend money on the more ',
        offset: passageOffset + 2372,
    },
    { text: '40', offset: passageOffset + 2584 },
    { text: "health problem of providing the world's population with clean drinking water.", offset: passageOffset + 2587 },
    { text: 'A unrealistic', offset: passageOffset + 2665 },
    { text: 'B agreed', offset: passageOffset + 2679 },
    { text: 'C expensive', offset: passageOffset + 2688 },
    { text: 'D right', offset: passageOffset + 2700 },
    { text: 'E long-term', offset: passageOffset + 2708 },
    { text: 'F usual', offset: passageOffset + 2720 },
    { text: 'G surprising', offset: passageOffset + 2728 },
    { text: 'H personal', offset: passageOffset + 2741 },
    { text: 'I urgent', offset: passageOffset + 2752 },
]);

const questions27to32 = [
    'Environmentalists take a pessimistic view of the world for a number of reasons.',
    "Data on the Earth's natural resources has only been collected since 1972.",
    'The number of starving people in the world has increased in recent years.',
    'Extinct species are being replaced by new species.',
    'Some pollution problems have been correctly linked to industrialisation.',
    'It would be best to attempt to slow down economic growth.',
];

const questions33to37 = [
    {
        question: 'What aspect of scientific research does the writer express concern about in paragraph 4?',
        options: {
            A: 'the need to produce results',
            B: 'the lack of financial support',
            C: 'the selection of areas to research',
            D: 'the desire to solve every research problem',
        },
    },
    {
        question: 'The writer quotes from the Worldwide Fund for Nature to illustrate how',
        options: {
            A: 'influential the mass media can be',
            B: 'effective environmental groups can be',
            C: 'the mass media can help groups raise funds',
            D: 'environmental groups can exaggerate their claims',
        },
    },
    {
        question: "What is the writer's main point about lobby groups in paragraph 6?",
        options: {
            A: 'Some are more active than others',
            B: 'Some are better organised than others',
            C: 'Some receive more criticism than others',
            D: 'Some support more important issues than others',
        },
    },
    {
        question: 'The writer suggests that newspapers print items that are intended to',
        options: {
            A: 'educate readers',
            B: "meet their readers' expectations",
            C: 'encourage feedback from readers',
            D: 'mislead readers',
        },
    },
    {
        question: "What does the writer say about America's waste problem?",
        options: {
            A: 'It will increase in line with population growth',
            B: 'It is not as important as we have been led to believe',
            C: 'It has been reduced through public awareness of the issues',
            D: 'It is only significant in certain areas of the country',
        },
    },
];

const wordList38to40 = {
    A: 'unrealistic',
    B: 'agreed',
    C: 'expensive',
    D: 'right',
    E: 'long-term',
    F: 'usual',
    G: 'surprising',
    H: 'personal',
    I: 'urgent',
};

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

const getAnswers = () => answers.value;

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-flash');
        setTimeout(() => {
            element.classList.remove('highlight-flash');
        }, 2000);
    }
};

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) return;
        const selected = selection.toString().trim();
        if (!selected) return;
        const range = selection.getRangeAt(0);
        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) {
                container = container.parentNode as Node;
            }
            const segmentEl = (container as Element).closest('[data-segment-text]');
            if (!segmentEl) return null;
            const segmentText = segmentEl.getAttribute('data-segment-text');
            const segment = textSegments.value.find((s) => s.text === segmentText);
            if (!segment) return null;
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

watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

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

defineExpose({ getAnswers, scrollToQuestion, scrollToHighlight, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
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
                                    <p class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-lg">READING PASSAGE 3</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <h2
                                    class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                >
                                    Effects of noise
                                </h2>
                            </div>
                        </div>

                        <div class="flex-1 space-y-6 overflow-y-auto p-6">
                            <div class="space-y-6 text-base leading-relaxed select-text sm:text-lg">
                                <div class="rounded-lg border border-green-100 bg-gradient-to-r from-green-50 to-green-100 p-4 shadow-md">
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
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-emerald-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-emerald-500"></div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-white shadow-lg"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex h-[600px] flex-col overflow-hidden bg-white lg:h-full">
                        <div class="flex-shrink-0 border-b border-gray-200 bg-white p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-green-600">
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div><p class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-lg">QUESTIONS</p></div>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <section id="question-27-32" class="space-y-4 rounded-xl border border-green-200 bg-green-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-green-800"
                                        :data-segment-text="'Questions 27-32'"
                                        v-html="getHighlightedSegment('Questions 27-32')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Do the following statements agree with the information given in Reading Passage 3?'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Do the following statements agree with the information given in Reading Passage 3?',
                                            )
                                        "
                                    ></p>
                                    <p
                                        :data-segment-text="'In boxes 27-32 on your answer sheet, write'"
                                        v-html="getHighlightedSegment('In boxes 27-32 on your answer sheet, write')"
                                    ></p>
                                    <div class="space-y-8 rounded-lg border border-green-200 bg-white p-8 shadow-lg">
                                        <p>
                                            <strong
                                                class="m-2 rounded bg-green-100 p-2.5 text-green-700 shadow"
                                                :data-segment-text="'YES'"
                                                v-html="getHighlightedSegment('YES')"
                                            ></strong
                                            ><span
                                                :data-segment-text="' if the statement agrees with the information'"
                                                v-html="getHighlightedSegment(' if the statement agrees with the information')"
                                            ></span>
                                        </p>
                                        <p>
                                            <strong
                                                class="m-2 rounded bg-red-100 p-2.5 text-red-700 shadow"
                                                :data-segment-text="'NO'"
                                                v-html="getHighlightedSegment('NO')"
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
                                                :data-segment-text="'if there is no information on this'"
                                                v-html="getHighlightedSegment('if there is no information on this')"
                                            ></span>
                                        </p>
                                    </div>
                                    <div class="space-y-4 pt-4">
                                        <div
                                            v-for="(question, index) in questions27to32"
                                            :key="index"
                                            class="group rounded-lg border-l-4 border-green-400 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                                        >
                                            <div class="flex items-start gap-4">
                                                <span
                                                    class="flex h-8 w-8 transform items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-[0_4px_15px_rgba(16,185,129,0.4)] transition-transform group-hover:scale-110"
                                                    >{{ 27 + index }}</span
                                                >
                                                <p class="flex-1" :data-segment-text="question" v-html="getHighlightedSegment(question)"></p>
                                            </div>
                                            <div class="mt-3 flex space-x-4 pl-12">
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (27 + index)"
                                                        value="YES"
                                                        v-model="answers['q' + (27 + index)]"
                                                        class="form-radio text-green-600 focus:ring-green-500"
                                                    />
                                                    <span class="text-gray-700">YES</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (27 + index)"
                                                        value="NO"
                                                        v-model="answers['q' + (27 + index)]"
                                                        class="form-radio text-green-600 focus:ring-green-500"
                                                    />
                                                    <span class="text-gray-700">NO</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (27 + index)"
                                                        value="NOT GIVEN"
                                                        v-model="answers['q' + (27 + index)]"
                                                        class="form-radio text-green-600 focus:ring-green-500"
                                                    />
                                                    <span class="text-gray-700">NOT GIVEN</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section id="question-33-37" class="space-y-4 rounded-xl border border-emerald-200 bg-emerald-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-emerald-800"
                                        :data-segment-text="'Questions 33-37'"
                                        v-html="getHighlightedSegment('Questions 33-37')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Choose the correct letter, A, B, C or D. Write your answers in boxes 33-37 on your answer sheet.'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Choose the correct letter, A, B, C or D. Write your answers in boxes 33-37 on your answer sheet.',
                                            )
                                        "
                                    ></p>
                                    <div class="space-y-6">
                                        <div
                                            v-for="(q, index) in questions33to37"
                                            :key="index"
                                            class="group rounded-lg border-l-4 border-emerald-400 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                                        >
                                            <div class="flex items-start gap-4">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 transform items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-[0_4px_15px_rgba(16,185,129,0.4)] transition-transform group-hover:scale-110"
                                                    >{{ 33 + index }}</span
                                                >
                                                <p
                                                    class="flex-1 font-semibold"
                                                    :data-segment-text="q.question"
                                                    v-html="getHighlightedSegment(q.question)"
                                                ></p>
                                            </div>
                                            <div class="mt-4 space-y-3 pl-12">
                                                <label
                                                    v-for="(optionText, optionLabel) in q.options"
                                                    :key="optionLabel"
                                                    class="flex cursor-pointer items-center gap-3"
                                                >
                                                    <input
                                                        type="radio"
                                                        :name="'q' + (33 + index)"
                                                        :value="optionLabel"
                                                        v-model="answers['q' + (33 + index)]"
                                                        class="h-5 w-5 border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                                    />
                                                    <span
                                                        class="font-medium"
                                                        :data-segment-text="`${optionLabel} ${optionText}`"
                                                        v-html="getHighlightedSegment(`${optionLabel} ${optionText}`)"
                                                    ></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section id="question-38-40" class="space-y-4 rounded-xl border border-green-200 bg-green-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-green-800"
                                        :data-segment-text="'Questions 38-40'"
                                        v-html="getHighlightedSegment('Questions 38-40')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Complete the summary with the list of words A-I below. Write the correct letter A-I in boxes 38-40 on your answer sheet.'"
                                        v-html="
                                            getHighlightedSegment(
                                                'Complete the summary with the list of words A-I below. Write the correct letter A-I in boxes 38-40 on your answer sheet.',
                                            )
                                        "
                                    ></p>

                                    <div class="rounded-lg border border-green-200 bg-white p-4">
                                        <h4
                                            class="text-center font-bold text-green-800"
                                            :data-segment-text="'GLOBAL WARMING'"
                                            v-html="getHighlightedSegment('GLOBAL WARMING')"
                                        ></h4>
                                        <p class="mt-4 leading-loose text-gray-700">
                                            <span
                                                :data-segment-text="'The writer admits that global warming is a '"
                                                v-html="getHighlightedSegment('The writer admits that global warming is a ')"
                                            ></span>
                                            <span
                                                class="flex inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-[0_4px_15px_rgba(16,185,129,0.4)]"
                                                >38</span
                                            >
                                            <select
                                                v-model="answers.q38"
                                                class="mx-1 my-2 inline-block w-24 rounded-lg border-2 border-green-300 px-2 py-1 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                                            >
                                                <option value="" disabled>Select</option>
                                                <option v-for="letter in Object.keys(wordList38to40)" :key="letter" :value="letter">
                                                    {{ letter }}
                                                </option>
                                            </select>
                                            <span
                                                :data-segment-text="'challenge, but says that it will not have a catastrophic impact on our future, if we deal with it in the '"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'challenge, but says that it will not have a catastrophic impact on our future, if we deal with it in the ',
                                                    )
                                                "
                                            ></span>
                                            <span
                                                class="flex inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-[0_4px_15px_rgba(16,185,129,0.4)]"
                                                >39</span
                                            >
                                            <select
                                                v-model="answers.q39"
                                                class="mx-1 my-2 inline-block w-24 rounded-lg border-2 border-green-300 px-2 py-1 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                                            >
                                                <option value="" disabled>Select</option>
                                                <option v-for="letter in Object.keys(wordList38to40)" :key="letter" :value="letter">
                                                    {{ letter }}
                                                </option>
                                            </select>
                                            <span
                                                :data-segment-text="'way. If we try to reduce the levels of greenhouse gases, he believes that it would only have a minimal impact on rising temperatures. He feels it would be better to spend money on the more '"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'way. If we try to reduce the levels of greenhouse gases, he believes that it would only have a minimal impact on rising temperatures. He feels it would be better to spend money on the more ',
                                                    )
                                                "
                                            ></span>
                                            <span
                                                class="flex inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-500 font-bold text-white shadow-[0_4px_15px_rgba(16,185,129,0.4)]"
                                                >40</span
                                            >
                                            <select
                                                v-model="answers.q40"
                                                class="mx-1 my-2 inline-block w-24 rounded-lg border-2 border-green-300 px-2 py-1 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:outline-none"
                                            >
                                                <option value="" disabled>Select</option>
                                                <option v-for="letter in Object.keys(wordList38to40)" :key="letter" :value="letter">
                                                    {{ letter }}
                                                </option>
                                            </select>
                                            <span
                                                :data-segment-text="'health problem of providing the world\'s population with clean drinking water.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'health problem of providing the world\'s population with clean drinking water.',
                                                    )
                                                "
                                            ></span>
                                        </p>
                                    </div>

                                    <div class="rounded-lg border border-green-200 bg-white p-4">
                                        <div class="grid grid-cols-3 gap-2 text-center">
                                            <div v-for="(word, letter) in wordList38to40" :key="letter" class="rounded-md bg-green-50 p-2">
                                                <span
                                                    :data-segment-text="`${letter} ${word}`"
                                                    v-html="getHighlightedSegment(`${letter} ${word}`)"
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

    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="pointer-events-auto fixed z-[9999] rounded-lg border border-gray-200 bg-white p-2 shadow-xl"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
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
                    <button
                        @click="openNoteInput"
                        class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-emerald-50 transition-all hover:scale-110 hover:bg-emerald-100"
                        title="Add Note"
                    >
                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div
            v-if="showNoteInput"
            class="absolute z-[9999] w-80 rounded-lg border-2 border-emerald-300 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop
        >
            <div class="mb-3">
                <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                <input
                    v-model="noteInputText"
                    type="text"
                    placeholder="Write your note here..."
                    class="note-input-field w-full rounded-lg border-2 border-emerald-200 px-3 py-2 text-base focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:outline-none sm:text-lg"
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
                    class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-emerald-700"
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
.passage-panel,
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
