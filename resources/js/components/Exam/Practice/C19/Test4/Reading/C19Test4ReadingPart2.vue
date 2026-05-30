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
const PANEL_WIDTH_KEY = 'reading-c19t4-part2-panel-width';
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

const passageText = `A When Professor Mat Upton found that a microbe from a deep-sea sponge was killing pathogenic bugs in his laboratory, he realised it could be a breakthrough in the fight against antibiotic-resistant superbugs, which are responsible for thousands of deaths a year in the UK alone. Further tests confirmed that an antibiotic from the sponge bacteria, found living more than 700 metres under the sea at the Rockall trough in the north-east Atlantic, was previously unknown to science, boosting its potential as a life-saving medicine. But Upton, and other scientists who view the deep ocean and its wealth of unique and undocumented species as a prospecting ground for new medicines, fear such potential will be lost in the rush to exploit the deep sea\u2019s equally rich metal and mineral resources.

B \u2018We\u2019re looking at the bioactive potential of marine resources, to see if there are any more medicines or drugs down there before we destroy it for ever,\u2019 says Upton, a medical microbiologist at the University of Plymouth. He is among many scientists urging a halt to deep-sea mining, asking for time to weigh up the pros and cons. \u2018In sustainability terms, this could be a better way of exploiting the economic potential of the deep sea,\u2019 he argues. Oceanographers using remotely operated vehicles have spotted many new species. Among them have been sea cucumbers with tails allowing them to sail along the ocean floor, and a rare \u2018Dumbo\u2019 octopus, found 3,000 metres under the Pacific Ocean, off the coast of California. Any one of these could offer lifesaving potential. Upton estimates it could take up to a decade for a newly discovered antibiotic to become a medicine \u2013 but the race towards commercial mining in the ocean abyss has already begun.

C The deep sea contains more nickel, cobalt and rare earth metals than all land reserves combined, according to the US Geological Survey. Mining corporations argue that deep-sea exploration could help diversify the supply of metals and point to the fact that demand for resources such as copper, aluminum, cobalt for electric car batteries and other metals to power technology and smartphones, is soaring. They say that deep-sea mining could yield far superior ore to land mining with little, if any, waste. Different methods of extraction exist, but most involve employing some form of converted machinery previously used in terrestrial mining to excavate materials from the sea floor, at depths of up to 6,000 meters, then drawing a seawater slurry, containing rock and other solid particles, from the sea floor to ships on the surface. The slurry is then \u2018de-watered\u2019 and transferred to another vessel for shipping. Extracted seawater is pumped back down and discharged close to the sea floor.

D But environmental and legal groups have urged caution, arguing there are potentially massive and unknown ramifications for the environment and for nearby communities, and that the global regulatory framework is not yet drafted. \u2018Despite arising in the last half century, the \u201cnew global gold rush\u201d of deep-sea mining shares many features with past resource scrambles \u2013 including a general disregard for environmental and social impacts, and the marginalisation of indigenous peoples and their rights,\u2019 a paper, written by Julie Hunter and Julian Aguon, from Blue Ocean Law, and Pradeep Singh, from the Center for Marine Environmental Sciences, Bremen, argues. The authors say that knowledge of the deep seabed remains extremely limited. \u2018The surface of the Moon, Mars and even Venus have all been mapped and studied in much greater detail, leading marine scientists to commonly remark that, with respect to the deep sea, \u201cWe don\u2019t yet know what we need to know\u201d.\u2019

E Scientific research \u2013 including a recent paper in Marine Policy journal has suggested the deep seabed, and hydrothermal vents, which are created when seawater meets volcanic magma, have crucial impacts upon biodiversity and the global climate. The mineral-rich vents and their surrounds are also home to many well-known animals including crustaceans, tubeworms, clams, slugs, anemones and fish. \u2018It is becoming increasingly clear that deep-sea mining poses a grave threat to these vital seabed functions,\u2019 the paper says. \u2018Extraction methods would produce large sediment plumes and involve the discharge of waste back into the ocean, significantly disturbing seafloor environments,\u2019 the paper continues. \u2018On deep sea vents, scientists are clear,\u2019 says Dr Jon Copley of the National Oceanography Centre, Southampton: \u2018We don\u2019t want mining on them.\u2019

F The oceans occupy around 70% of the planet and are relatively unexplored, says Mike Johnston, chief executive of Nautilus, a Canadian underwater exploration company: \u2018It makes sense to explore this untapped potential in an environmentally sustainable way, instead of continually looking at the fast depleting land resources of the planet to meet society\u2019s rising needs.\u2019 Those leading the global rush to place giant mining machines thousands of metres below the sea surface say the environmental impacts will be far lower than on land. But critics say exotic and little-known ecosystems in the deep oceans could be destroyed and must be protected. \u2018Mining will be the greatest assault on deep-sea ecosystems ever inflicted by humans,\u2019 according to hydrothermal vent expert Verena Tunnicliffe, at the University of Victoria in Canada. She argues that active vents must be off-limits for mining to protect the new knowledge and biotechnology spin-offs they can deliver, and that strict controls must be in place elsewhere.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 2', offset: 5600 },
    { text: 'You should spend about 20 minutes on Questions 14-26 which are based on Reading Passage 2 below.', offset: 5650 },
    { text: 'Deep-sea mining', offset: 5800 },
    {
        text: 'Bacteria from the ocean floor can beat superbugs and cancer. But habitats are at risk from the hunger for marine minerals',
        offset: 5850,
    },
    { text: 'Questions 14\u201317', offset: 5950 },
    { text: 'Reading Passage 2 has six paragraphs, A\u2013F.', offset: 6000 },
    { text: 'Which paragraph contains the following information?', offset: 6100 },
    { text: 'Write the correct letter, A\u2013F, in boxes 14\u201317 on your answer sheet.', offset: 6200 },
    { text: 'reference to the rapidly increasing need for one raw material in the transport industry', offset: 6400 },
    { text: 'a rough estimate of the area of the Earth covered by the oceans', offset: 6500 },
    { text: 'how a particular underwater habitat, where minerals and organisms co-exist, is formed', offset: 6600 },
    { text: 'reference to the fact that the countries of the world have yet to agree on rules for the exploration of the seabed', offset: 6700 },
    { text: 'Questions 18\u201323', offset: 6900 },
    { text: 'Look at the following statements (Questions 18\u201323) and the list of people below.', offset: 6950 },
    { text: 'Match each statement with the correct person, A\u2013E.', offset: 7050 },
    { text: 'Write the correct letter, A\u2013E, in boxes 18\u201323 on your answer sheet.', offset: 7150 },
    { text: 'NB You may use any letter more than once.', offset: 7250 },
    { text: 'List of People', offset: 7350 },
    { text: 'A Professor Mat Upton', offset: 7400 },
    { text: 'B Julie Hunter, Julian Aguon and Pradeep Singh', offset: 7450 },
    { text: 'C Dr Jon Copley', offset: 7500 },
    { text: 'D Mike Johnston', offset: 7550 },
    { text: 'E Verena Tunnicliffe', offset: 7600 },
    { text: 'A move away from the exploration of heavily mined reserves on land is a good idea.', offset: 7700 },
    { text: 'The negative effects of undersea exploration on local areas and their inhabitants are being ignored.', offset: 7800 },
    { text: 'There are more worthwhile things to extract from the sea than minerals.', offset: 7900 },
    { text: 'No other form of human exploration will have such a destructive impact on marine life as deep-sea mining.', offset: 8000 },
    { text: 'More is known about outer space than about what lies beneath the oceans.', offset: 8100 },
    { text: 'There is one marine life habitat where experts agree mining should not take place.', offset: 8200 },
    { text: 'Questions 24\u201326', offset: 8400 },
    { text: 'Complete the summary below.', offset: 8450 },
    { text: 'Choose ONE WORD ONLY from the passage for each answer.', offset: 8500 },
    { text: 'Write your answers in boxes 24\u201326 on your answer sheet.', offset: 8600 },
    { text: 'Mining the sea floor', offset: 8700 },
    {
        text: 'Mining corporations believe that the mineral resources lying under the sea may be superior to those found in the earth. They also say that these can be removed without producing much ',
        offset: 8800,
    },
    { text: 'The extraction is often done by adapting the ', offset: 9000 },
    {
        text: 'The method of excavation involves removing the seawater from the slurry that is brought up to ships and returning it to the seabed. However, concerned groups strongly believe that ',
        offset: 9200,
    },
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
                                    <span :data-segment-text="'Deep-sea mining'" v-html="getHighlightedSegment('Deep-sea mining')"></span>
                                </h2>
                                <p
                                    class="text-center text-sm text-gray-600 italic"
                                    :data-segment-text="'Bacteria from the ocean floor can beat superbugs and cancer. But habitats are at risk from the hunger for marine minerals'"
                                    v-html="
                                        getHighlightedSegment(
                                            'Bacteria from the ocean floor can beat superbugs and cancer. But habitats are at risk from the hunger for marine minerals',
                                        )
                                    "
                                ></p>
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
                                                :data-segment-text="'Reading Passage 2 has six paragraphs, A\u2013F.'"
                                                v-html="getHighlightedSegment('Reading Passage 2 has six paragraphs, A\u2013F.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Which paragraph contains the following information?'"
                                                v-html="getHighlightedSegment('Which paragraph contains the following information?')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A\u2013F, in boxes 14\u201317 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Write the correct letter, A\u2013F, in boxes 14\u201317 on your answer sheet.',
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
                                                    :data-segment-text="textSegments.find((s) => s.offset === [6400, 6500, 6600, 6700][n - 1])?.text"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            textSegments.find((s) => s.offset === [6400, 6500, 6600, 6700][n - 1])?.text || '',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers['q' + (13 + n)]"
                                                    class="w-24 rounded-xl border border-pink-300 bg-pink-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-pink-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E', 'F']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 18-23 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-18-23" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 18\u201323'"
                                            v-html="getHighlightedSegment('Questions 18\u201323')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Look at the following statements (Questions 18\u201323) and the list of people below.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Look at the following statements (Questions 18\u201323) and the list of people below.',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Match each statement with the correct person, A\u2013E.'"
                                                v-html="getHighlightedSegment('Match each statement with the correct person, A\u2013E.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm text-gray-600 italic"
                                                :data-segment-text="'Write the correct letter, A\u2013E, in boxes 18\u201323 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Write the correct letter, A\u2013E, in boxes 18\u201323 on your answer sheet.',
                                                    )
                                                "
                                            ></p>
                                            <p
                                                class="mb-2 text-sm font-semibold text-red-500"
                                                :data-segment-text="'NB You may use any letter more than once.'"
                                                v-html="getHighlightedSegment('NB You may use any letter more than once.')"
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 font-semibold text-gray-800"
                                                :data-segment-text="'List of People'"
                                                v-html="getHighlightedSegment('List of People')"
                                            ></p>
                                            <ul class="space-y-1 text-gray-700">
                                                <li
                                                    :data-segment-text="'A Professor Mat Upton'"
                                                    v-html="getHighlightedSegment('A Professor Mat Upton')"
                                                ></li>
                                                <li
                                                    :data-segment-text="'B Julie Hunter, Julian Aguon and Pradeep Singh'"
                                                    v-html="getHighlightedSegment('B Julie Hunter, Julian Aguon and Pradeep Singh')"
                                                ></li>
                                                <li :data-segment-text="'C Dr Jon Copley'" v-html="getHighlightedSegment('C Dr Jon Copley')"></li>
                                                <li :data-segment-text="'D Mike Johnston'" v-html="getHighlightedSegment('D Mike Johnston')"></li>
                                                <li
                                                    :data-segment-text="'E Verena Tunnicliffe'"
                                                    v-html="getHighlightedSegment('E Verena Tunnicliffe')"
                                                ></li>
                                            </ul>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                id="question-18"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >18</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="'A move away from the exploration of heavily mined reserves on land is a good idea.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'A move away from the exploration of heavily mined reserves on land is a good idea.',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers.q18"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                            <div
                                                id="question-19"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >19</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="'The negative effects of undersea exploration on local areas and their inhabitants are being ignored.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'The negative effects of undersea exploration on local areas and their inhabitants are being ignored.',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers.q19"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                            <div
                                                id="question-20"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >20</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="'There are more worthwhile things to extract from the sea than minerals.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'There are more worthwhile things to extract from the sea than minerals.',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers.q20"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                            <div
                                                id="question-21"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >21</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="'No other form of human exploration will have such a destructive impact on marine life as deep-sea mining.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'No other form of human exploration will have such a destructive impact on marine life as deep-sea mining.',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers.q21"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                            <div
                                                id="question-22"
                                                class="flex items-center gap-3 rounded-xl border-l-4 border-purple-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-purple-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-white shadow-md"
                                                    >22</span
                                                >
                                                <p
                                                    class="flex-1 text-gray-700"
                                                    :data-segment-text="'More is known about outer space than about what lies beneath the oceans.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'More is known about outer space than about what lies beneath the oceans.',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers.q22"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
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
                                                    :data-segment-text="'There is one marine life habitat where experts agree mining should not take place.'"
                                                    v-html="
                                                        getHighlightedSegment(
                                                            'There is one marine life habitat where experts agree mining should not take place.',
                                                        )
                                                    "
                                                ></p>
                                                <select
                                                    v-model="answers.q23"
                                                    class="w-24 rounded-xl border border-purple-300 bg-purple-50 px-2 py-1 text-sm shadow-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option v-for="i in ['A', 'B', 'C', 'D', 'E']" :key="i" :value="i">{{ i }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 24-26 -->
                                <div
                                    class="rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-24-26" class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 24\u201326'"
                                            v-html="getHighlightedSegment('Questions 24\u201326')"
                                        ></h3>
                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-gray-800"
                                                :data-segment-text="'Complete the summary below.'"
                                                v-html="getHighlightedSegment('Complete the summary below.')"
                                            ></p>
                                            <p
                                                class="mb-2 text-sm font-semibold text-red-500"
                                                :data-segment-text="'Choose ONE WORD ONLY from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD ONLY from the passage for each answer.')"
                                            ></p>
                                            <p
                                                class="text-sm text-gray-600 italic"
                                                :data-segment-text="'Write your answers in boxes 24\u201326 on your answer sheet.'"
                                                v-html="getHighlightedSegment('Write your answers in boxes 24\u201326 on your answer sheet.')"
                                            ></p>
                                        </div>

                                        <div class="rounded-xl border border-purple-300 bg-white/50 p-4 shadow-inner">
                                            <p
                                                class="mb-2 text-center font-semibold text-gray-800"
                                                :data-segment-text="'Mining the sea floor'"
                                                v-html="getHighlightedSegment('Mining the sea floor')"
                                            ></p>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Question 24 -->
                                            <div
                                                id="question-24"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >24</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'Mining corporations believe that the mineral resources lying under the sea may be superior to those found in the earth. They also say that these can be removed without producing much '"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'Mining corporations believe that the mineral resources lying under the sea may be superior to those found in the earth. They also say that these can be removed without producing much ',
                                                                )
                                                            "
                                                        ></span>
                                                        <input
                                                            v-model="answers.q24"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                        <span>.</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Question 25 -->
                                            <div
                                                id="question-25"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >25</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'The extraction is often done by adapting the '"
                                                            v-html="getHighlightedSegment('The extraction is often done by adapting the ')"
                                                        ></span>
                                                        <input
                                                            v-model="answers.q25"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                        <span> that has already been used to work on land.</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Question 26 -->
                                            <div
                                                id="question-26"
                                                class="rounded-xl border-l-4 border-pink-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-px hover:shadow-2xl hover:shadow-pink-200/50"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-pink-600 font-bold text-white shadow-md"
                                                        >26</span
                                                    >
                                                    <p class="flex-1 text-gray-700">
                                                        <span
                                                            :data-segment-text="'The method of excavation involves removing the seawater from the slurry that is brought up to ships and returning it to the seabed. However, concerned groups strongly believe that '"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'The method of excavation involves removing the seawater from the slurry that is brought up to ships and returning it to the seabed. However, concerned groups strongly believe that ',
                                                                )
                                                            "
                                                        ></span>
                                                        <input
                                                            v-model="answers.q26"
                                                            type="text"
                                                            class="inline-block w-32 rounded-md border border-purple-300 bg-purple-50 px-2 py-1 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                        />
                                                        <span> is necessary due to the possible number of unidentified consequences.</span>
                                                    </p>
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
