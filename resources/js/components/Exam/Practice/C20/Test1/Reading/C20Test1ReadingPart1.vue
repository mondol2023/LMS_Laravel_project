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
});

const passageText = `The kākāpō is a nocturnal, flightless parrot that is critically endangered and one of New Zealand’s unique treasures

The kākāpō, also known as the owl parrot, is a large, forest-dwelling bird, with a pale owl-like face. Up to 64 cm in length, it has predominantly yellow-green feathers, forward-facing eyes, a large grey beak, large blue feet, and relatively short wings and tail. It is the world’s only flightless parrot, and is also possibly one of the world’s longest-living birds, with a reported lifespan of up to 100 years.

Kākāpō are solitary birds and tend to occupy the same home range for many years. They forage on the ground and climb high into trees. They often leap from trees and flap their wings, but at best manage a controlled descent to the ground. They are entirely vegetarian, with their diet including the leaves, roots and bark of trees as well as bulbs, and fern fronds.

Kākāpō breed in summer and autumn, but only in years when food is plentiful. Males play no part in incubation or chick-rearing – females alone incubate eggs and feed the chicks. The 1-4 eggs are laid in soil, which is repeatedly turned over before and during incubation. The female kākāpō has to spend long periods away from the nest searching for food, which leaves the unattended eggs and chicks particularly vulnerable to predators.

Before humans arrived, kākāpō were common throughout New Zealand’s forests. However, this all changed with the arrival of the first Polynesian settlers about 700 years ago. For the early settlers, the flightless kākāpō was easy prey. They ate its meat and used its feathers to make soft cloaks. With them came the Polynesian dog and rat, which also preyed on kākāpō. By the time European colonisers arrived in the early 1800s, kākāpō had become confined to the central North Island and forested parts of the South Island. The fall in kākāpō numbers was accelerated by European colonisation. A great deal of habitat was lost through forest clearance, and introduced species such as deer depleted the remaining forests of food. Other predators such as cats, stoats and two more species of rat were also introduced. The kākāpō were in serious trouble.

In 1894, the New Zealand government launched its first attempt to save the kākāpō. Conservationist Richard Henry led an effort to relocate several hundred of the birds to predator-free Resolution Island in Fiordland. Unfortunately, the island didn’t remain predator free – stoats arrived within six years, eventually destroying the kākāpō population. By the mid-1900s, the kākāpō was practically a lost species. Only a few clung to life in the most isolated parts of New Zealand.

From 1949 to 1973, the newly formed New Zealand Wildlife Service made over 60 expeditions to find kākāpō, focusing mainly on Fiordland. Six were caught, but there were no females amongst them and all but one died within a few months of captivity. In 1974, a new initiative was launched, and by 1977, 18 more kākāpō were found in Fiordland. However, there were still no females. In 1977, a large population of males was spotted in Rakiura – a large island free from stoats, ferrets and weasels. There were about 200 individuals, and in 1980 it was confirmed females were also present. These birds have been the foundation of all subsequent work in managing the species.

Unfortunately, predation by feral cats on Rakiura Island led to a rapid decline in kākāpō numbers. As a result, during 1980-97, the surviving population was evacuated to three island sanctuaries: Codfish Island, Maud Island and Little Barrier Island. However, breeding success was hard to achieve. Rats were found to be a major predator of kākāpō chicks and an insufficient number of chicks survived to offset adult mortality. By 1995, although at least 12 chicks had been produced on the islands, only three had survived. The kākāpō population had dropped to 51 birds. The critical situation prompted an urgent review of kākāpō management in New Zealand.

In 1996, a new Recovery Plan was launched, together with a specialist advisory group called the Kākāpō Scientific and Technical Advisory Committee and a higher amount of funding. Renewed steps were taken to control predators on the three islands. Cats were eradicated from Little Barrier Island in 1980, and possums were eradicated from Codfish Island by 1986. However, the population did not start to increase until rats were removed from all three islands, and the birds were more intensively managed. This involved moving the birds between islands, supplementary feeding of adults and rescuing and hand-raising any failing chicks.

After the first five years of the Recovery Plan, the population was on target. By 2000, five new females had been produced, and the total population had grown to 62 birds. For the first time, there was cautious optimism for the future of kākāpō and by June 2020, a total of 210 birds was recorded.

Today, kākāpō management continues to be guided by the kākāpō Recovery Plan. Its key goals are: minimise the loss of genetic diversity in the kākāpō population, restore or maintain sufficient habitat to accommodate the expected increase in the kākāpō population, and ensure stakeholders continue to be fully engaged in the preservation of the species.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'READING PASSAGE 1', offset: 4825 },
    { text: 'You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.', offset: 4844 },
    { text: 'The kākāpō', offset: 4945 },
    { text: 'Questions 1-6 ', offset: 4957 },
    { text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: 4971 },
    { text: 'In boxes 1-6 on your answer sheet, write', offset: 5700 },
    { text: 'TRUE', offset: 5745 },
    { text: 'if the statement agrees with the writer', offset: 5748 },
    { text: 'FALSE', offset: 5789 },
    { text: 'if the statement contradicts the writer', offset: 5791 },
    { text: 'NOT GIVEN', offset: 5831 },
    { text: 'if it is impossible to say what the writer thinks about this', offset: 5840 },
    { text: "There are other parrots that share the kakapo's inability to fly.", offset: 5253 },
    { text: 'Adult kakapo produce chicks every year.', offset: 5320 },
    { text: 'Adult male kakapo bring food back to nesting females.', offset: 5364 },
    { text: 'The Polynesian rat was a greater threat to the kakapo than Polynesian settlers.', offset: 5426 },
    { text: 'Kakapo were transferred from Rakiura Island to other locations because they were at risk from feral cats.', offset: 5516 },
    { text: 'One Recovery Plan initiative that helped increase the kakapo population size was caring for struggling young birds.', offset: 5629 },
    { text: 'Questions 7-13', offset: 5757 },
    { text: 'Complete the notes below.', offset: 5772 },
    { text: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.', offset: 5799 },
    { text: 'Write your answers in boxes 7-13 on your answer sheet.', offset: 5874 },
    { text: "New Zealand's kākāpō", offset: 5934 },
    { text: 'A type of parrot:', offset: 5956 },
    { text: '• diet consists of fern fronds, various parts of a tree and ', offset: 5974 },
    { text: '.', offset: 6033 },
    { text: '• nests are created in ', offset: 6034 },
    { text: ' where eggs are laid.', offset: 6058 },
    { text: '• the ', offset: 6080 },
    { text: ' of the kākāpō were used to make clothes.', offset: 6085 },
    { text: '• ', offset: 6137 },
    { text: " were an animal which they introduced that ate the kākāpō's food sources.", offset: 6139 },
    { text: 'Protecting kākāpō', offset: 6227 },
    { text: '• Richard Henry, a conservationist, tried to protect the kākāpō.', offset: 6245 },
    { text: '• a definite sighting of female kākāpō on Rakiura Island was reported in the year ', offset: 6314 },
    { text: '.', offset: 6401 },
    { text: '• the Recovery Plan included an increase in ', offset: 6402 },
    { text: '.', offset: 6445 },
    { text: '• a current goal of the Recovery Plan is to maintain the involvement of ', offset: 6446 },
    { text: ' in kākāpō protection.', offset: 6520 },
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
                                        <span :data-segment-text="'The kākāpō'" v-html="getHighlightedSegment('The kākāpō')"></span>
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
                                <!-- Questions 1-6  -->
                                <div
                                    class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <section id="question-1-6 " class="space-y-4">
                                        <h3
                                            class="bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-lg font-bold text-transparent"
                                            :data-segment-text="'Questions 1-6 '"
                                            v-html="getHighlightedSegment('Questions 1-6 ')"
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
                                                            v-html="
                                                                getHighlightedSegment('if it is impossible to say what the writer thinks about this')
                                                            "
                                                        ></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Q1 -->
                                            <div
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >1</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'There are other parrots that share the kakapo\'s inability to fly.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'There are other parrots that share the kakapo\'s inability to fly.',
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
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >2</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Adult kakapo produce chicks every year.'"
                                                        v-html="getHighlightedSegment('Adult kakapo produce chicks every year.')"
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
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >3</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Adult male kakapo bring food back to nesting females.'"
                                                        v-html="getHighlightedSegment('Adult male kakapo bring food back to nesting females.')"
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
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >4</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'The Polynesian rat was a greater threat to the kakapo than Polynesian settlers.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'The Polynesian rat was a greater threat to the kakapo than Polynesian settlers.',
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
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >5</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'Kakapo were transferred from Rakiura Island to other locations because they were at risk from feral cats.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'Kakapo were transferred from Rakiura Island to other locations because they were at risk from feral cats.',
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
                                                class="flex items-start gap-3 rounded-xl border-t-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >6</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="'One Recovery Plan initiative that helped increase the kakapo population size was caring for struggling young birds.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                'One Recovery Plan initiative that helped increase the kakapo population size was caring for struggling young birds.',
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

                                <!-- Questions 7-13 -->
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
                                                :data-segment-text="'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.'"
                                                v-html="getHighlightedSegment('Choose ONE WORD AND/OR A NUMBER from the passage for each answer.')"
                                            ></p>
                                        </div>

                                        <div class="space-y-3 rounded-lg bg-white/50 p-4">
                                            <h4
                                                class="text-md font-bold text-blue-800"
                                                :data-segment-text="'New Zealand\'s kākāpō'"
                                                v-html="getHighlightedSegment('New Zealand\'s kākāpō')"
                                            ></h4>

                                            <h5
                                                class="font-semibold text-gray-700"
                                                :data-segment-text="'A type of parrot:'"
                                                v-html="getHighlightedSegment('A type of parrot:')"
                                            ></h5>

                                            <!-- Q7 -->
                                            <div class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >7</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'• diet consists of fern fronds, various parts of a tree and '"
                                                        v-html="getHighlightedSegment('• diet consists of fern fronds, various parts of a tree and ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q7"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                                                </p>
                                            </div>

                                            <!-- Q8 -->
                                            <div class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >8</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'• nests are created in '"
                                                        v-html="getHighlightedSegment('• nests are created in ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q8"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' where eggs are laid.'"
                                                        v-html="getHighlightedSegment(' where eggs are laid.')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <h5
                                                class="pt-2 font-semibold text-gray-700"
                                                :data-segment-text="'Arrival of Polynesian settlers'"
                                                v-html="getHighlightedSegment('Arrival of Polynesian settlers')"
                                            ></h5>

                                            <!-- Q9 -->
                                            <div class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >9</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span :data-segment-text="'• the '" v-html="getHighlightedSegment('• the ')"></span>
                                                    <input
                                                        v-model="answers.q9"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' of the kākāpō were used to make clothes.'"
                                                        v-html="getHighlightedSegment(' of the kākāpō were used to make clothes.')"
                                                    ></span>
                                                </p>
                                            </div>

                                            <h5
                                                class="pt-2 font-semibold text-gray-700"
                                                :data-segment-text="'Arrival of European colonisers'"
                                                v-html="getHighlightedSegment('Arrival of European colonisers')"
                                            ></h5>

                                            <!-- Q10 -->
                                            <div class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >10</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span :data-segment-text="'• '" v-html="getHighlightedSegment('• ')"></span>
                                                    <input
                                                        v-model="answers.q10"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' were an animal which they introduced that ate the kākāpō\'s food sources.'"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                ' were an animal which they introduced that ate the kākāpō\'s food sources.',
                                                            )
                                                        "
                                                    ></span>
                                                </p>
                                            </div>

                                            <h5
                                                class="pt-2 font-semibold text-gray-700"
                                                :data-segment-text="'Protecting kākāpō'"
                                                v-html="getHighlightedSegment('Protecting kākāpō')"
                                            ></h5>

                                            <p
                                                class="text-gray-500 italic"
                                                :data-segment-text="'• Richard Henry, a conservationist, tried to protect the kākāpō.'"
                                                v-html="getHighlightedSegment('• Richard Henry, a conservationist, tried to protect the kākāpō.')"
                                            ></p>

                                            <!-- Q11 -->
                                            <div class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >11</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'• a definite sighting of female kākāpō on Rakiura Island was reported in the year '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                '• a definite sighting of female kākāpō on Rakiura Island was reported in the year ',
                                                            )
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q11"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                                                </p>
                                            </div>

                                            <!-- Q12 -->
                                            <div class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >12</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'• the Recovery Plan included an increase in '"
                                                        v-html="getHighlightedSegment('• the Recovery Plan included an increase in ')"
                                                    ></span>
                                                    <input
                                                        v-model="answers.q12"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span :data-segment-text="'.'" v-html="getHighlightedSegment('.')"></span>
                                                </p>
                                            </div>

                                            <!-- Q13 -->
                                            <div class="flex items-center gap-3 rounded-lg p-3">
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >13</span
                                                >
                                                <p class="flex-1 text-gray-700">
                                                    <span
                                                        :data-segment-text="'• a current goal of the Recovery Plan is to maintain the involvement of '"
                                                        v-html="
                                                            getHighlightedSegment(
                                                                '• a current goal of the Recovery Plan is to maintain the involvement of ',
                                                            )
                                                        "
                                                    ></span>
                                                    <input
                                                        v-model="answers.q13"
                                                        type="text"
                                                        class="mx-1 inline-block w-32 rounded-lg border border-blue-300 bg-white p-1 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span
                                                        :data-segment-text="' in kākāpō protection.'"
                                                        v-html="getHighlightedSegment(' in kākāpō protection.')"
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
