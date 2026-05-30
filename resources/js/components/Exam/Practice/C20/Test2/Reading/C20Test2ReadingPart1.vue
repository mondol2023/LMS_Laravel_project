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
const PANEL_WIDTH_KEY = 'reading-ielts002-part1-panel-width';
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

const passageText = `Manatees, also known as sea cows, are aquatic mammals that belong to a group of animals called Sirenia. This group also contains dugongs. Dugongs and manatees look quite alike -they are similar in size, colour and shape, and both have flexible flippers for forelimbs. However, the manatee has a broad, rounded tail, whereas the dugongs is fluked, like that of a whale. There are three species of manatees: the West Indian manatee (Trichechus manatus), the African manatee (Trichechus senegalensis) and the Amazonian manatee (Trichechus in unguis).

Unlike most mammals, manatees have only six bones in their neck – most others, including humans and giraffes, have seven. This short neck allows a manatee to move its head up and down, but not side to side. To see something on its left or its right, a manatee must tum its entire body, steering with its flippers. Manatees have pectoral flippers but no back limbs, only a tail for propulsion. They do have pelvic bones, however – a leftover from their evolution from a four-legged to a fully aquatic animal. Manatees share some visual similarities to elephants. Like elephants, manatees have thick, wrinkled skin. They also have some hairs covering their bodies which help them sense vibrations in the water around them.

Seagrasses and other marine plants make up most of a manatee’s diet. Manatees spend about eight hours each day grazing and uprooting plants. They eat up to 15% of their weight in food each day. African manatees are omnivorous – studies have shown that molluscs and fish make up a small part of their diets. West Indian and Amazonian manatees are both herbivores.

Manatees’ teeth are all molars -flat, rounded teeth for grinding food. Due to manatees’ abrasive aquatic plant diet, these teeth get worn down and they eventually fall out, so they continually grow new teeth that get pushed forward to replace the ones they lose. Instead of having incisors to grasp their food, manatees have lips which function like a pair of hands to help tear food away from the seafloor.

Manatees are fully aquatic, but as mammals, they need to come up to the surface to breathe. When awake, they typically surface every two to four minutes, but they can hold their breath for much longer. Adult manatees sleep underwater for 10-12 hours a day, but they come up for air every 15-20 minutes. Active manatees need to breathe more frequently. It’s thought that manatees use their muscular diaphragm and breathing to adjust their buoyancy. They may use diaphragm contractions to compress and store gas in folds in their large intestine to help them float.

The West Indian manatee reaches about 3.5 metros long and weighs on average around 500 kilo grammes. It moves between fresh water and salt water, taking advantage of coastal mangroves and coral reefs, rivers, lakes and inland lagoons. There are two subspecies of West Indian manatee: the Antillean manatee is found in waters from the Bahamas to Brazil, whereas the Florida manatee is found in US waters, although some individuals have been recorded in the Bahamas. In winter, the Florida manatee is typically restricted to Florida. When the ambient water temperature drops below 20°C, it takes reliige in naturally and artificially warmed water, such as at the warm-water outfalls from powerplants.

The African manatee is also about 3.5 metros long and found in the sea along the west coast of Africa, from Mauritania down to Angola. The species also makes use of rivers, with the mammals seen in landlocked countries such as Mali and Niger. The Amazonian manatee is the smallest species, though it is still a big animal. It grows to about 2.5 metros long and 350 kilo grammes. Amazonian manatees favour calm, shallow waters that are above 23°C This species is found in fresh water in the Amazon Basin in Brazil, as well as in Colombia, Ecuador and Peru.

All three manatee species are endangered or at a heightened risk of extinction. The African manatee and Amazonian manatee are both listed as Vulnerable by the International Union for Conservation of Nature (IUCN). It is estimated that 140,000 Amazonian manatees were killed between 1935 and 1954 for their meat, fat and skin, with the latter used to make leather. In more recent years, African manatee decline has been tied to incidental capture in fishing nets and hunting. Manatee hunting is now illegal in every country the African species is found in.

The two subspecies of West Indian manatee are listed as Endangered by the IUCN. Both are also expected to undergo a decline of20° over the next 40 years. A review of almost 1,800 cases of entanglement in fishing nets and of plastic consumption among marine mammals in US waters from 2009 to 2020 found that at least 700 cases involved manatees. The chief cause of death in Florida manatees is boat strikes. However, laws in certain parts of Florida now limit boat speeds during winter, allowing slow-moving manatees more time to respond.`;

const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'Questions 1-6', offset: 4825 },
    { text: 'Complete the notes below.', offset: 4840 },
    { text: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.', offset: 4868 },
    { text: 'Appearance', offset: 4940 },
    { text: 'look similar to dugongs, but with a differently shaped', offset: 4952 },
    { text: 'Movement', offset: 5010 },
    { text: 'have fewer neck bones than most mammals', offset: 5020 },
    { text: 'need to use their', offset: 5065 },
    { text: 'to help to turn their bodies around in order to look sideways', offset: 5085 },
    { text: 'sense vibrations in the water by means of', offset: 5150 },
    { text: 'on their skin', offset: 5195 },
    { text: 'Feeding', offset: 5210 },
    { text: 'eat mainly aquatic vegetation, such as', offset: 5220 },
    { text: 'grasp and pull up plants with their', offset: 5265 },
    { text: 'Breathing', offset: 5310 },
    { text: 'come to the surface for air every 2-4 minutes when awake and every 15-20 while sleeping', offset: 5322 },
    { text: 'may regulate the', offset: 5410 },
    { text: 'of their bodies by using muscles of diaphragm to store air internally', offset: 5430 },
    { text: 'Questions 7-13', offset: 5500 },
    { text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: 5518 },
    { text: 'In boxes 7-13 on your answer sheet, write', offset: 5595 },
    { text: 'TRUE', offset: 5640 },
    { text: 'if the statement agrees with the information', offset: 5645 },
    { text: 'FALSE', offset: 5690 },
    { text: 'if the statement contradicts the information', offset: 5696 },
    { text: 'NOT GIVEN', offset: 5740 },
    { text: 'if there is no information on this', offset: 5752 },
    { text: 'West Indian manatees can be found in a variety of different aquatic habitats.', offset: 5810 },
    { text: 'The Florida manatee lives in warmer waters than the Antillean manatee.', offset: 5890 },
    { text: 'The African manatee’s range is limited to coastal waters between the West African countries of Mauritania and Angola.', offset: 5970 },
    { text: 'The extent of the loss of Amazonian manatees in the mid-twentieth century was only revealed many years later.', offset: 6080 },
    { text: 'It is predicted that West Indian manatee populations will fall in the coming decades.', offset: 6180 },
    { text: 'The risk to manatees from entanglement and plastic consumption increased significantly in the period 2009-2020.', offset: 6270 },
    { text: 'There is some legislation in place which aims to reduce the likelihood of boat strikes on manatees in Florida.', offset: 6380 },
]);

// Helper to get a highlighted version of a specific text segment
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
                            <h2 class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-center text-xl font-bold text-transparent">
                                <span :data-segment-text="'Manatees'" v-html="getHighlightedSegment('Manatees')"></span>
                            </h2>
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
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 1-6 -->
                                <div class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm">
                                    <section id="question-1-6" class="space-y-6">
                                        <h3
                                            class="text-lg font-bold text-blue-800"
                                            :data-segment-text="'Questions 1-6'"
                                            v-html="getHighlightedSegment('Questions 1-6')"
                                        ></h3>
                                        <p
                                            class="text-sm text-gray-700"
                                            :data-segment-text="'Complete the notes below.'"
                                            v-html="getHighlightedSegment('Complete the notes below.')"
                                        ></p>
                                        <p
                                            class="text-sm font-semibold text-blue-700"
                                            :data-segment-text="'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.'"
                                            v-html="getHighlightedSegment('Choose ONE WORD AND/OR A NUMBER from the passage for each answer.')"
                                        ></p>
                                        <div class="space-y-6 pt-4">
                                            <div>
                                                <h4
                                                    class="font-bold text-blue-900"
                                                    :data-segment-text="'Appearance'"
                                                    v-html="getHighlightedSegment('Appearance')"
                                                ></h4>
                                                <div class="mt-2 flex items-center gap-2">
                                                    <span
                                                        :data-segment-text="'look similar to dugongs, but with a differently shaped'"
                                                        v-html="getHighlightedSegment('look similar to dugongs, but with a differently shaped')"
                                                    ></span>
                                                    <div id="question-1" class="inline-flex items-center gap-2">
                                                        <span
                                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.4)]"
                                                            >1</span
                                                        >
                                                        <input
                                                            v-model="answers.q1"
                                                            type="text"
                                                            class="w-40 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/50 sm:text-sm"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <h4
                                                    class="font-bold text-blue-900"
                                                    :data-segment-text="'Movement'"
                                                    v-html="getHighlightedSegment('Movement')"
                                                ></h4>
                                                <ul class="mt-2 list-none space-y-3 pl-4">
                                                    <li>
                                                        <span
                                                            :data-segment-text="'have fewer neck bones than most mammals'"
                                                            v-html="getHighlightedSegment('have fewer neck bones than most mammals')"
                                                        ></span>
                                                    </li>
                                                    <li class="flex items-center gap-2">
                                                        <span
                                                            :data-segment-text="'need to use their'"
                                                            v-html="getHighlightedSegment('need to use their')"
                                                        ></span>
                                                        <div id="question-2" class="inline-flex items-center gap-2">
                                                            <span
                                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.4)]"
                                                                >2</span
                                                            >
                                                            <input
                                                                v-model="answers.q2"
                                                                type="text"
                                                                class="w-40 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/50 sm:text-sm"
                                                            />
                                                        </div>
                                                        <span
                                                            :data-segment-text="'to help to turn their bodies around in order to look sideways'"
                                                            v-html="
                                                                getHighlightedSegment('to help to turn their bodies around in order to look sideways')
                                                            "
                                                        ></span>
                                                    </li>
                                                    <li class="flex items-center gap-2">
                                                        <span
                                                            :data-segment-text="'sense vibrations in the water by means of'"
                                                            v-html="getHighlightedSegment('sense vibrations in the water by means of')"
                                                        ></span>
                                                        <div id="question-3" class="inline-flex items-center gap-2">
                                                            <span
                                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.4)]"
                                                                >3</span
                                                            >
                                                            <input
                                                                v-model="answers.q3"
                                                                type="text"
                                                                class="w-40 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/50 sm:text-sm"
                                                            />
                                                        </div>
                                                        <span
                                                            :data-segment-text="'on their skin'"
                                                            v-html="getHighlightedSegment('on their skin')"
                                                        ></span>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div>
                                                <h4
                                                    class="font-bold text-blue-900"
                                                    :data-segment-text="'Feeding'"
                                                    v-html="getHighlightedSegment('Feeding')"
                                                ></h4>
                                                <ul class="mt-2 list-none space-y-3 pl-4">
                                                    <li class="flex items-center gap-2">
                                                        <span
                                                            :data-segment-text="'eat mainly aquatic vegetation, such as'"
                                                            v-html="getHighlightedSegment('eat mainly aquatic vegetation, such as')"
                                                        ></span>
                                                        <div id="question-4" class="inline-flex items-center gap-2">
                                                            <span
                                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.4)]"
                                                                >4</span
                                                            >
                                                            <input
                                                                v-model="answers.q4"
                                                                type="text"
                                                                class="w-40 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/50 sm:text-sm"
                                                            />
                                                        </div>
                                                    </li>
                                                    <li class="flex items-center gap-2">
                                                        <span
                                                            :data-segment-text="'grasp and pull up plants with their'"
                                                            v-html="getHighlightedSegment('grasp and pull up plants with their')"
                                                        ></span>
                                                        <div id="question-5" class="inline-flex items-center gap-2">
                                                            <span
                                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.4)]"
                                                                >5</span
                                                            >
                                                            <input
                                                                v-model="answers.q5"
                                                                type="text"
                                                                class="w-40 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/50 sm:text-sm"
                                                            />
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div>
                                                <h4
                                                    class="font-bold text-blue-900"
                                                    :data-segment-text="'Breathing'"
                                                    v-html="getHighlightedSegment('Breathing')"
                                                ></h4>
                                                <ul class="mt-2 list-none space-y-3 pl-4">
                                                    <li>
                                                        <span
                                                            :data-segment-text="'come to the surface for air every 2-4 minutes when awake and every 15-20 while sleeping'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'come to the surface for air every 2-4 minutes when awake and every 15-20 while sleeping',
                                                                )
                                                            "
                                                        ></span>
                                                    </li>
                                                    <li class="flex items-center gap-2">
                                                        <span
                                                            :data-segment-text="'may regulate the'"
                                                            v-html="getHighlightedSegment('may regulate the')"
                                                        ></span>
                                                        <div id="question-6" class="inline-flex items-center gap-2">
                                                            <span
                                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white shadow-[0_4px_12px_rgba(59,130,246,0.4)]"
                                                                >6</span
                                                            >
                                                            <input
                                                                v-model="answers.q6"
                                                                type="text"
                                                                class="w-40 rounded-md border border-blue-300 p-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/50 sm:text-sm"
                                                            />
                                                        </div>
                                                        <span
                                                            :data-segment-text="'of their bodies by using muscles of diaphragm to store air internally'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'of their bodies by using muscles of diaphragm to store air internally',
                                                                )
                                                            "
                                                        ></span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Questions 7-13 -->
                                <div class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm">
                                    <section id="question-7-13" class="space-y-4">
                                        <h3
                                            class="text-lg font-bold text-blue-800"
                                            :data-segment-text="'Questions 7-13'"
                                            v-html="getHighlightedSegment('Questions 7-13')"
                                        ></h3>
                                        <div class="rounded-xl border border-blue-300 bg-blue-100/50 p-4 shadow-inner">
                                            <p
                                                class="mb-3 text-sm text-gray-800"
                                                :data-segment-text="'Do the following statements agree with the information given in Reading Passage 1?'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Do the following statements agree with the information given in Reading Passage 1?',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                        <div class="space-y-6 pt-2">
                                            <div
                                                v-for="i in 7"
                                                :key="i"
                                                :id="`question-${6 + i}`"
                                                class="flex items-start gap-4 rounded-xl border-l-4 border-blue-500 bg-white p-4 shadow-lg transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-200/50"
                                            >
                                                <span
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md"
                                                    >{{ 6 + i }}</span
                                                >
                                                <div class="flex-1">
                                                    <p
                                                        class="mb-3 text-gray-800"
                                                        :data-segment-text="
                                                            textSegments.find((s) => s.offset === [5810, 5890, 5970, 6080, 6180, 6270, 6380][i - 1])
                                                                ?.text
                                                        "
                                                        v-html="
                                                            getHighlightedSegment(
                                                                textSegments.find(
                                                                    (s) => s.offset === [5810, 5890, 5970, 6080, 6180, 6270, 6380][i - 1],
                                                                )?.text || '',
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
                                                                v-model="answers['q' + (6 + i)]"
                                                                type="radio"
                                                                :value="option"
                                                                :name="`q${6 + i}`"
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
