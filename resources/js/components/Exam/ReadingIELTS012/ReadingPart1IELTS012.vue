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
const PANEL_WIDTH_KEY = 'reading-ielts012-part1-panel-width';
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

const passageText = `No one knows exactly how the pyramids were built. Marcus Chown reckons the answer could be 'hanging in the air'.

The pyramids of Egypt were built more than three thousand years ago, and no one knows how. The conventional picture is that tens of thousands of slaves dragged stones on sledges. But there is no evidence to back this up. Now a Californian software consultant called Maureen Clemmons has suggested that kites might have been involved. While perusing a book on the monuments of Egypt, she noticed a hieroglyph that showed a row of men standing in odd postures. They were holding what looked like ropes that led, via some kind of mechanical system, to a giant bird in the sky. She wondered if perhaps the bird was actually a giant kite, and the men were using it to lift a heavy object. 

Intrigued, Clemmons contacted Morteza Gharib, aeronautics professor at the California Institute of Technology. He was fascinated by the idea. 'Coming from Iran, I have a keen interest in Middle Eastern science', he says. He too was puzzled by the picture that had sparked Clemmons's interest. The object in the sky apparently had wings far too short and wide for a bird. 'The possibility certainly existed that it was a kite', he says. And since he needed a summer project for his student Emilio Graff, investigating the possibility of using kites as heavy lifters seemed like a good idea.

Gharib and Graff set themselves the task of raising a 4.5-metre stone column from horizontal to vertical, using no source of energy except the wind. Their initial calculations and scale-model wind-tunnel experiments convinced them they wouldn't need a strong wind to lift the 33.5-tonne column. Even a modest force, if sustained over a long time, would do. The key was to use a pulley system that would magnify the applied force. So they rigged up a tent-shaped scaffold directly above the tip of the horizontal column, with pulleys suspended from the scaffold directly above the tip of the horizontal column, with pulleys suspended from the scaffold's apex. The idea was that as one end of the column rose, the base would roll across the ground on a trolley. Earlier this year, the team put Clemmons's unlikely theory to the test, using a 40-square-meter rectangular nylon sail. The kite lifted the column clean off the ground. 'We were absolutely stunned' Gharib says. The instant the sail opened into the wind, a huge force was generated and the column was raised to the vertical in a mere 40 seconds.' 

The wind was blowing at a gentle 16 to 20 kilometers an hour, little more than half what they thought would be needed. What they had failed to reckon with was what happened when the kite was opened. There was a huge initial force - five times larger than the steady state force', Gharib says. This jerk meant that kites could lift huge weights, Gharib realised. Even a 300-tonne column could have been lifted to the vertical with 40 or so men and four or five sails. So Clemmons was right: the pyramid builders could have used kites to lift massive stones into place. 'Whether they actually did is another matter,' Gharib says. There are no pictures showing the construction of the pyramids, so there is no way to tell what really happened. The evidence for using kites to move large stones is no better or worse than the evidence for the brute force method', Gharib says.

Indeed, the experiments triage left many specialists unconvinced. The evidence for kite- lifting is non- existent', says Wallace Wendrich, an associate professor of Egyptology at the University of California, Los Angeles.

Others feel there is more of a case for the theory. Harnessing the wind would not have been a problem for accomplished sailors like the Egyptians. And they are known to have used wooden pulleys, which could have been made strong enough to bear the weight of massive blocks of stone. In addition, there is some physical evidence that the ancient Egyptians were interested in flight. A wooden artifact found on the step pyramid at Saqqara looks uncannily like a modern glider. Although it dates from several hundred years after the building of the pyramids, its sophistication suggests that the Egyptians might have been developing ideas of flight for a long time. And other ancient civilisations certainly knew about kites; as early as 1250 BC, the Chinese were using them to deliver messages and dump flaming debris on their foes. The experiments might even have practical uses nowadays. There are plenty of places around the globe where people have no access to heavy machinery, but do know how to deal with, wind, sailing and basic mechanical principles. Gharib has already been contacted by a civil engineer in Nicaragua, who wants to put up buildings with adobe roofs supported by concrete arches on a site that heavy equipment can't reach. His idea is to build the arches horizontally, then lift them into place using kites. 'We've given him some design hints', says Gharib. 'We're just waiting for him to report back.' So whether they were actually used to build the pyramids or not, it seems that kites may make sensible construction tools in the 21st century AD.`;

const passageOffset = passageText.length;
const textSegments = ref([
    { text: passageText, offset: 0 },
    { text: 'Questions 1-7', offset: passageOffset + 1 },
    { text: 'Do the following statements with the information given in the Reading Passage?', offset: passageOffset + 15 },
    { text: 'In boxes 1-7 on your answer sheet, write', offset: passageOffset + 92 },
    { text: 'TRUE', offset: passageOffset + 138 },
    { text: ' if the statement agrees with the information', offset: passageOffset + 138 },
    { text: 'FALSE', offset: 188 + passageOffset },
    { text: ' if the statement contradicts the information', offset: 188 + passageOffset },
    { text: 'NOT GIVEN', offset: 239 + passageOffset },
    { text: ' if there is no information on this', offset: 239 + passageOffset },
    { text: '   It is generally believed that large numbers of people were needed to build the pyramids.', offset: 288 + passageOffset },
    { text: '   Clemmons found a strange hieroglyph on the wall of an Egyptian monument.', offset: 382 + passageOffset },
    { text: '   Gharib had previously done experiments on bird flight.', offset: 462 + passageOffset },
    { text: '   Gharib and Graff tested their theory before applying it.', offset: 519 + passageOffset },
    { text: '   The success of the actual experiment was due to the high speed of the wind.', offset: 580 + passageOffset },
    { text: '   They found that, as the kite flew higher, the wind force got stronger.', offset: 661 + passageOffset },
    { text: '   The team decided that it was possible to use kites to raise very heavy stones.', offset: 737 + passageOffset },
    { text: 'Questions 8-13', offset: 818 + passageOffset },
    { text: 'Complete the summary below.', offset: 835 + passageOffset },
    { text: 'Choose NO MORE THAN TWO WORDS from the passage for each answer', offset: 863 + passageOffset },
    { text: 'Write your answers in boxes 8-13 your answer sheet.', offset: 928 + passageOffset },
    { text: 'Addition evidence for theory of kite lifting', offset: 981 + passageOffset },
    { text: 'The Egyptians had ', offset: 1026 + passageOffset },
    { text: ', which could lift large pieces of ', offset: 1046 + passageOffset },
    { text: ', and they knew how to use the energy of the wind from their skill as ', offset: 1083 + passageOffset },
    { text: ' . The discovery on one pyramid of an object which resembled a ', offset: 1150 + passageOffset },
    { text: ' suggests they may have experimented with ', offset: 1215 + passageOffset },
    { text: ' . In addition, over two thousand years ago kites used in China as weapons, as well as for sending ', offset: 1261 + passageOffset },
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
                                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">READING PASSAGE 1</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h2
                                        class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-center text-xl font-bold text-transparent"
                                    >
                                        Pulling strings to build pyramids
                                    </h2>
                                </div>
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
                                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">QUESTIONS</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 1-7 -->
                                <section id="question-1-7" class="space-y-4 rounded-xl border border-blue-200 bg-blue-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-blue-800"
                                        :data-segment-text="'Questions 1-7'"
                                        v-html="getHighlightedSegment('Questions 1-7')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Do the following statements with the information given in the Reading Passage?'"
                                        v-html="
                                            getHighlightedSegment('Do the following statements with the information given in the Reading Passage?')
                                        "
                                    ></p>
                                    <p
                                        :data-segment-text="'In boxes 1-7 on your answer sheet, write'"
                                        v-html="getHighlightedSegment('In boxes 1-7 on your answer sheet, write')"
                                    ></p>
                                    <div class="space-y-8 rounded-lg border border-blue-200 bg-white p-8 shadow-xs">
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
                                            v-for="i in 7"
                                            :key="i"
                                            class="group rounded-lg border-l-4 border-blue-400 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                                        >
                                            <div class="flex items-start gap-4">
                                                <span
                                                    class="flex h-8 w-8 transform items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-lg transition-transform group-hover:scale-110"
                                                    >{{ i }}</span
                                                >
                                                <p
                                                    class="flex-1"
                                                    :data-segment-text="textSegments[i + 9]?.text"
                                                    v-html="getHighlightedSegment(textSegments[i + 9]?.text.substring(3))"
                                                ></p>
                                            </div>
                                            <div class="mt-3 flex space-x-4 pl-12">
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + i"
                                                        :value="'TRUE'"
                                                        v-model="answers['q' + i]"
                                                        class="form-radio text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <span class="text-gray-700">TRUE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + i"
                                                        :value="'FALSE'"
                                                        v-model="answers['q' + i]"
                                                        class="form-radio text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <span class="text-gray-700">FALSE</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center space-x-2">
                                                    <input
                                                        type="radio"
                                                        :name="'q' + i"
                                                        :value="'NOT GIVEN'"
                                                        v-model="answers['q' + i]"
                                                        class="form-radio text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <span class="text-gray-700">NOT GIVEN</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- Questions 8-13 -->
                                <section id="question-8-13" class="space-y-4 rounded-xl border border-blue-200 bg-blue-50/50 p-6 shadow-sm">
                                    <h3
                                        class="text-lg font-bold text-blue-800"
                                        :data-segment-text="'Questions 8-13'"
                                        v-html="getHighlightedSegment('Questions 8-13')"
                                    ></h3>
                                    <p
                                        :data-segment-text="'Complete the summary below.'"
                                        v-html="getHighlightedSegment('Complete the summary below.')"
                                    ></p>
                                    <p
                                        :data-segment-text="'Choose NO MORE THAN TWO WORDS from the passage for each answer'"
                                        v-html="getHighlightedSegment('Choose NO MORE THAN TWO WORDS from the passage for each answer')"
                                    ></p>
                                    <p
                                        :data-segment-text="'Write your answers in boxes 8-13 your answer sheet.'"
                                        v-html="getHighlightedSegment('Write your answers in boxes 8-13 your answer sheet.')"
                                    ></p>

                                    <div class="space-y-4 rounded-lg border border-blue-200 bg-white p-6 shadow-inner">
                                        <h4
                                            class="text-center font-bold text-blue-800"
                                            :data-segment-text="'Addition evidence for theory of kite lifting'"
                                            v-html="getHighlightedSegment('Addition evidence for theory of kite lifting')"
                                        ></h4>
                                        <p class="leading-loose text-gray-700">
                                            <span
                                                :data-segment-text="'The Egyptians had '"
                                                v-html="getHighlightedSegment('The Egyptians had ')"
                                            ></span>
                                            <span
                                                class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-lg"
                                                >8</span
                                            >
                                            <input
                                                type="text"
                                                v-model="answers.q8"
                                                class="mx-1 my-2 inline-block w-40 rounded-md border border-blue-500 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            />
                                            <span
                                                :data-segment-text="', which could lift large pieces of '"
                                                v-html="getHighlightedSegment(', which could lift large pieces of ')"
                                            ></span>
                                            <span
                                                class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-lg"
                                                >9</span
                                            >
                                            <input
                                                type="text"
                                                v-model="answers.q9"
                                                class="mx-1 inline-block w-40 rounded-md border border-blue-500 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            />
                                            <span
                                                :data-segment-text="', and they knew how to use the energy of the wind from their skill as '"
                                                v-html="
                                                    getHighlightedSegment(', and they knew how to use the energy of the wind from their skill as ')
                                                "
                                            ></span>
                                            <span
                                                class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-lg"
                                                >10</span
                                            >
                                            <input
                                                type="text"
                                                v-model="answers.q10"
                                                class="mx-1 inline-block w-40 rounded-md border border-blue-500 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            />
                                            <span
                                                :data-segment-text="' . The discovery on one pyramid of an object which resembled a '"
                                                v-html="getHighlightedSegment(' . The discovery on one pyramid of an object which resembled a ')"
                                            ></span>
                                            <span
                                                class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-lg"
                                                >11</span
                                            >
                                            <input
                                                type="text"
                                                v-model="answers.q11"
                                                class="mx-1 inline-block w-40 rounded-md border border-blue-500 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            />
                                            <span
                                                :data-segment-text="' suggests they may have experimented with '"
                                                v-html="getHighlightedSegment(' suggests they may have experimented with ')"
                                            ></span>
                                            <span
                                                class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-lg"
                                                >12</span
                                            >
                                            <input
                                                type="text"
                                                v-model="answers.q12"
                                                class="mx-1 my-2 inline-block w-40 rounded-md border border-blue-500 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            />
                                            <span
                                                :data-segment-text="' . In addition, over two thousand years ago kites used in China as weapons, as well as for sending '"
                                                v-html="
                                                    getHighlightedSegment(
                                                        ' . In addition, over two thousand years ago kites used in China as weapons, as well as for sending ',
                                                    )
                                                "
                                            ></span>
                                            <span
                                                class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-lg"
                                                >13</span
                                            >
                                            <input
                                                type="text"
                                                v-model="answers.q13"
                                                class="mx-1 inline-block w-40 rounded-md border border-blue-500 p-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            />
                                            .
                                        </p>
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
