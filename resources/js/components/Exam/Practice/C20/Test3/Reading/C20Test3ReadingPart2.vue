<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

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

// Draft auto-saver

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts003-part2-panel-width';
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

const multipleAnswers = reactive({
    q20_21: [] as string[],
    q22_23: [] as string[],
});

watch(multipleAnswers, (newVal) => {
    answers.value.q20 = newVal.q20_21[0] || '';
    answers.value.q21 = newVal.q20_21[1] || '';
    answers.value.q22 = newVal.q22_23[0] || '';
    answers.value.q23 = newVal.q22_23[1] || '';
});

const passageText = `A. Conservationists have put the final touches to a giant artificial reef they have been assembling at the world-renowned Zoological Society of London (London Zoo). Samples of the planet’s most spectacular corals – vivid green branching coral, yellow scroll, blue ridge and many more species – have been added to the giant tank along with fish that thrive in their presence: blue tang, clownfish and many others. The reef is in the zoo’s new gallery, Tiny Giants, which is dedicated to the minuscule invertebrate creatures that sustain life across the planet. The coral reef tank and its seven-metre-wide window form the core of the exhibition. ‘Coral reefs are the most diverse ecosystems on Earth and we want to show people how wonderful they are,’ said Paul Pearce-Kelly, senior curator of invertebrates and fish at the Zoological Society of London. ‘However, we also want to highlight the research and conservation efforts that are now being carried out to try to save them from the threat of global warming.’ They want people to see what is being done to try to save these wonders.

B. Corals are composed of tiny animals, known as polyps, with tentacles for capturing small marine creatures in the sea water. These polyps are transparent but get their brilliant tones of pink, orange, blue, green, etc. from algae that live within them, which in turn get protection, while their photosynthesising of the sun’s rays provides nutrients for the polyps. This comfortable symbiotic relationship has led to the growth of coral reefs that cover 0.1% of the planet’s ocean bed while providing homes for more than 25% of marine species, including fish, molluscs, sponges and shellfish.

C. As a result, coral reefs are often described as the ‘rainforests of the sea’, though the comparison is dismissed by some naturalists, including David Attenborough. ‘People say you cannot beat the rainforest,’ Attenborough has stated. ‘But that is simply not true. You go there and the first thing you think is: where are the birds? Where are the animals? They are hiding in the trees, of course. No, if you want beauty and wildlife, you want a coral reef. Put on a mask and stick your head under the water. The sight is mindblowing.’

D. Unfortunately, these majestic sights are now under very serious threat, with the most immediate problem coming in the form of thermal stress. Rising ocean temperatures are triggering bleaching events that strip reefs of their colour and eventually kill them. And that is just the start. Other menaces include ocean acidification, sea level increase, pollution by humans, deoxygenation and ocean current changes, while the climate crisis is also increasing habitat destruction. As a result, vast areas – including massive chunks of Australia’s Great Barrier Reef – have already been destroyed, and scientists advise that more than 90% of reefs could be lost by 2050 unless urgent action is taken to tackle global heating and greenhouse gas emissions. Pearce-Kelly says that coral reefs have to survive really harsh conditions – wave erosion and other factors. And ‘when things start to go wrong in the oceans, then corals will be the first to react. And that is exactly what we are seeing now. Coral reefs are dying and they are telling us that all is not well with our planet.’

E. However, scientists are trying to pinpoint hardy types of coral that could survive our overheated oceans, and some of this research will be carried out at London Zoo. ‘Behind our … coral reef tank we have built laboratories where scientists will be studying coral species,’ said Pearce-Kelly. One aim will be to carry out research on species to find those that can survive best in warm, acidic waters. Another will be to try to increase coral breeding rates. ‘Coral spawn just once a year,’ he added. ‘However, aquarium-based research has enabled some corals to spawn artificially, which can assist coral reef restoration efforts. And if this can be extended for all species, we could consider the launching of coral-spawning programmes several times a year. That would be a big help in restoring blighted reefs.’

F. Research in these fields is being conducted in laboratories around the world, with the London Zoo centre linked to this global network. Studies carried out in one centre can then be tested in others. The resulting young coral can then be displayed in the tank in Tiny Giants. ‘The crucial point is that the progress we make in making coral better able to survive in a warming world can be shown to the public and encourage them to believe that we can do something to save the planet’s reefs,’ said Pearce-Kelly. ‘Saving our coral reefs is now a critically important ecological goal.’`;

const headings = [
    { id: 'i', text: 'Tried and tested solutions' },
    { id: 'ii', text: 'Cooperation beneath the waves' },
    { id: 'iii', text: 'Working to lessen the problems' },
    { id: 'iv', text: 'Disagreement about the accuracy of a certain phrase' },
    { id: 'v', text: 'Two clear educational goals' },
    { id: 'vi', text: 'Promoting hope' },
    { id: 'vii', text: 'A warning of further trouble ahead' },
];
const headingQuestions = [
    { num: 14, section: 'Section A' },
    { num: 15, section: 'Section B' },
    { num: 16, section: 'Section C' },
    { num: 17, section: 'Section D' },
    { num: 18, section: 'Section E' },
    { num: 19, section: 'Section F' },
];
const causesOfDamageOptions = [
    { label: 'A', text: 'a rising number of extreme storms' },
    { label: 'B', text: 'the removal of too many fish from the sea' },
    { label: 'C', text: 'the contamination of the sea from waste' },
    { label: 'D', text: 'increased disease among marine species' },
    { label: 'E', text: 'alterations in the usual flow of water in the seas' },
];
const researcherStatements = [
    { label: 'A', text: 'They are hoping to expand the numbers of different corals being bred in laboratories.' },
    { label: 'B', text: 'They want to identify corals that can cope well with the changed sea conditions.' },
    { label: 'C', text: 'They are looking at ways of creating artificial reefs that corals could grow on.' },
    { label: 'D', text: 'They are trying out methods that would speed up reproduction in some corals.' },
    { label: 'E', text: 'They are investigating materials that might protect reefs from higher temperatures.' },
];
const sentenceCompletionQuestions = [
    { num: 24, pre: 'Corals have a number of', post: 'which they use to collect their food.' },
    { num: 25, pre: 'Algae gain', post: 'from being inside the coral.' },
    { num: 26, pre: 'Increases in the warmth of the sea water can remove the', post: 'from coral.' },
];

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'Questions 14–19', offset: 6000 },
    { text: 'Reading Passage 2 has six sections, A–F.', offset: 6018 },
    { text: 'Choose the correct heading for each section from the list of headings below.', offset: 6060 },
    ...headings.map((h) => ({ text: h.text, offset: Math.random() + 7000 })),
    ...headingQuestions.map((h) => ({ text: h.section, offset: Math.random() + 8000 })),
    { text: 'Questions 20 and 21', offset: 9000 },
    { text: 'Choose TWO letters, A–E.', offset: 9022 },
    { text: 'Which TWO of these causes of damage to coral reefs are mentioned by the writer of the text?', offset: 9050 },
    ...causesOfDamageOptions.map((o) => ({ text: o.text, offset: Math.random() + 10000 })),
    { text: 'Questions 22 and 23', offset: 11000 },
    { text: 'Which TWO of the following statements are true of the researchers at London Zoo?', offset: 11050 },
    ...researcherStatements.map((o) => ({ text: o.text, offset: Math.random() + 12000 })),
    { text: 'Questions 24–26', offset: 13000 },
    { text: 'Complete the sentences below.', offset: 13018 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 13048 },
    ...sentenceCompletionQuestions.flatMap((q) => [
        { text: q.pre, offset: Math.random() + 14000 },
        { text: q.post, offset: Math.random() + 14000 },
    ]),
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

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
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
                    <div class="flex h-full flex-col overflow-hidden bg-white">
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

                            <div class="flex items-center justify-center gap-2">
                                <h2 class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                    <span
                                        :data-segment-text="'Can the planet’s coral reefs be saved?'"
                                        v-html="getHighlightedSegment('Can the planet’s coral reefs be saved?')"
                                    ></span>
                                </h2>
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
                    <div class="flex h-full flex-col overflow-hidden bg-white">
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
                                <div><p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">QUESTIONS</p></div>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 14-19: Headings -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-14-19" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 14–19'"
                                            v-html="getHighlightedSegment('Questions 14–19')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Reading Passage 2 has six sections, A–F.'"
                                                v-html="getHighlightedSegment('Reading Passage 2 has six sections, A–F.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Choose the correct heading for each section from the list of headings below.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Choose the correct heading for each section from the list of headings below.',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                        <div class="my-4 rounded-lg border-2 border-dashed border-pink-300 p-4">
                                            <h4 class="mb-3 text-center font-bold text-pink-800">List of Headings</h4>
                                            <ul class="list-none space-y-1 pl-2">
                                                <li v-for="h in headings" :key="h.id" class="text-gray-700">
                                                    <strong class="text-pink-600">{{ h.id }}</strong>
                                                    <span :data-segment-text="h.text" v-html="getHighlightedSegment(h.text)"></span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="space-y-3">
                                            <div
                                                v-for="q in headingQuestions"
                                                :key="q.num"
                                                :id="`question-${q.num}`"
                                                class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-pink-600 font-bold text-white shadow-md"
                                                        >{{ q.num }}</span
                                                    >
                                                    <p
                                                        class="flex-1 font-semibold text-gray-700"
                                                        :data-segment-text="q.section"
                                                        v-html="getHighlightedSegment(q.section)"
                                                    ></p>
                                                </div>
                                                <select
                                                    v-model="answers['q' + q.num]"
                                                    class="w-28 rounded-xl border border-pink-300 bg-pink-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="h in headings" :key="h.id" :value="h.id">{{ h.id }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 20-21: Checkbox -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-20-21" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 20 and 21'"
                                            v-html="getHighlightedSegment('Questions 20 and 21')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Choose TWO letters, A–E.'"
                                                v-html="getHighlightedSegment('Choose TWO letters, A–E.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Which TWO of these causes of damage to coral reefs are mentioned by the writer of the text?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Which TWO of these causes of damage to coral reefs are mentioned by the writer of the text?',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                        <div class="space-y-3">
                                            <label
                                                v-for="opt in causesOfDamageOptions"
                                                :key="opt.label"
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="opt.label"
                                                    v-model="multipleAnswers.q20_21"
                                                    :disabled="multipleAnswers.q20_21.length >= 2 && !multipleAnswers.q20_21.includes(opt.label)"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span class="font-medium text-gray-800"
                                                    ><strong class="mr-1">{{ opt.label }}</strong>
                                                    <span :data-segment-text="opt.text" v-html="getHighlightedSegment(opt.text)"></span
                                                ></span>
                                            </label>
                                        </div>
                                        <div class="mt-4 rounded-lg border border-pink-200 bg-white p-3 shadow">
                                            <p class="font-medium text-pink-700">Selected: {{ multipleAnswers.q20_21.length }}/2 answers</p>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 22-23: Checkbox -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-22-23" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 22 and 23'"
                                            v-html="getHighlightedSegment('Questions 22 and 23')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Choose TWO letters, A–E.'"
                                                v-html="getHighlightedSegment('Choose TWO letters, A–E.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Which TWO of the following statements are true of the researchers at London Zoo?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Which TWO of the following statements are true of the researchers at London Zoo?',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                        <div class="space-y-3">
                                            <label
                                                v-for="opt in researcherStatements"
                                                :key="opt.label"
                                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-pink-200 bg-white p-3 shadow hover:bg-pink-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="opt.label"
                                                    v-model="multipleAnswers.q22_23"
                                                    :disabled="multipleAnswers.q22_23.length >= 2 && !multipleAnswers.q22_23.includes(opt.label)"
                                                    class="h-5 w-5 rounded border-pink-400 text-pink-600 focus:ring-pink-500"
                                                />
                                                <span class="font-medium text-gray-800"
                                                    ><strong class="mr-1">{{ opt.label }}</strong>
                                                    <span :data-segment-text="opt.text" v-html="getHighlightedSegment(opt.text)"></span
                                                ></span>
                                            </label>
                                        </div>
                                        <div class="mt-4 rounded-lg border border-pink-200 bg-white p-3 shadow">
                                            <p class="font-medium text-pink-700">Selected: {{ multipleAnswers.q22_23.length }}/2 answers</p>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 24-26: Sentence Completion -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-24-26" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 24–26'"
                                            v-html="getHighlightedSegment('Questions 24–26')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the sentences below.'"
                                                v-html="getHighlightedSegment('Complete the sentences below.')"
                                            ></p>
                                            <p
                                                class="text-sm font-semibold text-red-500"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                        </div>
                                        <div class="space-y-4 pt-4">
                                            <div
                                                v-for="q in sentenceCompletionQuestions"
                                                :key="q.num"
                                                :id="`question-${q.num}`"
                                                class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-md"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >{{ q.num }}</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span :data-segment-text="q.pre" v-html="getHighlightedSegment(q.pre)"></span>
                                                    <input
                                                        v-model="answers['q' + q.num]"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                    />
                                                    <span :data-segment-text="q.post" v-html="getHighlightedSegment(q.post)"></span>
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
        <div
            v-if="showNoteInput"
            class="absolute z-[9999] w-80 rounded-lg border-2 border-indigo-300 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
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
        background-color: rgba(219, 39, 119, 0.1);
        transform: scale(1);
    }
    50% {
        background-color: rgba(219, 39, 119, 0.3);
        transform: scale(1.02);
    }
    100% {
        background-color: rgba(219, 39, 119, 0.1);
        transform: scale(1);
    }
}
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: #fdf2f8;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #a855f7, #ec4899);
    border-radius: 3px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #9333ea, #db2777);
}
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
</style>
