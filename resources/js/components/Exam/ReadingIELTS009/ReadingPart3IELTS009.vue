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

// Reading Part 3 component

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ielts009-part3-panel-width';
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

const passageText = `A.
Just as railway bridges were the great structural symbols of the 19th century, highway bridges became the engineering emblems of the 20th century. The invention of the automobile created an irresistible demand for paved roads and vehicular bridges throughout the developed world. The type of bridge needed for cars and trucks, however, is fundamentally different from that needed for locomotives. Most highway bridges carry lighter loads than railway bridges do, and their roadways can be sharply curved or steeply sloping. To meet these needs, many turn-of-the-century bridge designers began working with a new building material: reinforced concrete, which has steel bars embedded in it. And the master of this new material was Swiss structural engineer, Robert Maillart.

B.
Early in his career, Maillart developed a unique method for designing bridges, buildings and other concrete structures. He rejected the complex mathematical analysis of loads and stresses that was being enthusiastically adopted by most of his contemporaries. At the same time, he also eschewed the decorative approach taken by many bridge builders of his time. He resisted imitating architectural styles and adding design elements solely for ornamentation. Maillart’s method was a form of creative intuition. He had a knack for conceiving new shapes to solve classic engineering problems] And because he worked in a highly competitive field, one of his goals was economy - he won design and construction contracts because his structures were reasonably priced, often less costly than all his rivals’ proposals.


C.
Maillart’s first important bridge was built in the small Swiss town of Zuoz. The local officials had initially wanted a steel bridge to span the 30-metre wide Inn River, but Maillart argued that he could build a more elegant bridge made of reinforced concrete for about the same cost. His crucial innovation was incorporating the bridge’s arch and roadway into a form called the hollow-box arch, which would substantially reduce the bridge’s expense by minimising the amount of concrete needed. In a conventional arch bridge, the weight of the roadway is transferred by columns to the arch, which must be relatively thick. In Maillart’s design, though, the roadway and arch were connected by three vertical walls, forming two hollow boxes running under the roadway (see diagram). The big advantage of this design was that because the arch would not have to bear the load alone, it could be much thinner - as little as one-third as thick as the arch in the conventional bridge.

D.
His first masterpiece, however, was the 1905 Tavanasa Bridge over the Rhine river in the Swiss Alps. In this design, Maillart removed the parts of the vertical walls which were not essential because they carried no load. This produced a slender, lighter-looking form, which perfectly met the bridge’s structural requirements. But the Tavanasa Bridge gained little favourable publicity in Switzerland; on the contrary, it aroused strong aesthetic objections from public officials who were more comfortable with old-fashioned stone-faced bridges. Maillart, who had founded his own construction firm in 1902, was unable to win any more bridge projects, so he shifted his focus to designing buildings, water tanks and other structures made of reinforced concrete and did not resume his work on concrete bridges until the early 1920s.

E.
His most important breakthrough during this period was the development of the deck-stiffened arch, the first example of which was the Flienglibach Bridge, built in 1923. An arch bridge is somewhat like an inverted cable. A cable curves downward when a weight is hung from it, an arch bridge curves upward to support the roadway and the compression in the arch balances the dead load of the traffic. For aesthetic reasons, Maillart wanted a thinner arch and his solution was to connect the arch to the roadway with transverse walls. In this way, Maillart justified making the arch as thin as he could reasonably build it. His analysis accurately predicted the behaviour of the bridge but the leading authorities of Swiss engineering would argue against his methods for the next quarter of a century.

F.
Over the next 10 years, Maillart concentrated on refining the visual appearance of the deck-stiffened arch. His best-known structure is the Salginatobel Bridge, completed in 1930. He won the competition for the contract because his design was the least expensive of the 19 submitted - the bridge and road were built for only 700,000 Swiss francs, equivalent to some $3.5 million today. Salginatobel was also Maillart’s longest span, at 90 metres and it had the most dramatic setting of all his structures, vaulting 80 metres above the ravine of the Salgina brook. In 1991 it became the first concrete bridge to be designated an international historic landmark.

G.
Before his death in 1940, Maillart completed other remarkable bridges and continued to refine his designs. However, architects often recognised the high quality of Maillart’s structures before his fellow engineers did and in 1947 the architectural section of the Museum of Modern Art in New York City devoted a major exhibition entirely to his works. In contrast, very few American structural engineers at that time had even heard of Maillart. In the following years, however, engineers realised that Maillart’s bridges were more than just aesthetically pleasing - they were technically unsurpassed. Maillart’s hollow-box arch became the dominant design form for medium and long- span concrete bridges in the US. In Switzerland, professors finally began to teach Maillart’s ideas, which then influenced a new generation of designers.
`;

const textSegments = (() => {
    const segments: Array<{ text: string; offset: number }> = [];
    let offset = 0;

    function addSegment(text: string) {
        segments.push({ text, offset });
        offset += text.length;
    }

    addSegment(passageText);
    addSegment('READING PASSAGE 3');
    addSegment('The Revolutionary Bridges of Robert Maillart');
    addSegment(
        'Swiss engineer Robert Maillart built some of the greatest bridges of the 20th century. His designs elegantly solved a basic engineering problem: how to support enormous weights using a slender arch.',
    );
    addSegment('QUESTIONS');
    addSegment('Answer all questions based on Reading Passage 3');
    addSegment('Questions 27-33');
    addSegment('Reading Passage 3 has nine paragraphs A-G.');
    addSegment('From the list of headings below choose the most suitable heading for each paragraph.');
    addSegment('Write the appropriate numbers (i-x) in boxes 27-33 on your answer sheet.');
    addSegment('List of headings:');
    addSegment('The long-term impact');
    addSegment('A celebrated achievement');
    addSegment('Early brilliance passes unrecognised');
    addSegment('Outdated methods retain popularity');
    addSegment('The basis of a new design is born');
    addSegment('Frustration at never getting the design right');
    addSegment('iFurther refinements meet persistent objections');
    addSegment('Different in all respects');
    addSegment('Bridge-makers look elsewhere');
    addSegment('Transport developments spark a major change');
    addSegment('Paragraph A');
    addSegment('Paragraph B');
    addSegment('Paragraph C');
    addSegment('Paragraph D');
    addSegment('Paragraph E');
    addSegment('Paragraph F');
    addSegment('Paragraph G');
    addSegment('Questions 34-36');
    addSegment(
        'Complete the labels on the diagrams below using ONE or TWO WORDS from the reading passage. Write your answers in boxes 34-36 on your answer sheet.',
    );
    addSegment('Questions 37-40');
    addSegment('Complete each of the following statements with the best ending (A-G) from the box below');
    addSegment('Maillart designed the hollow-box arch order to .......');
    addSegment('Following the construction of the Tavanasa Bridge Maillart failed to ......');
    addSegment('The transverse walls of the Flienglibach Bridge allowed Maillart to .....');
    addSegment('Of all his bridges, the Salginatobel enabled Maillart to .....');

    return ref(segments);
})();

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
                                            :data-segment-text="'The Revolutionary Bridges of Robert Maillart'"
                                            v-html="getHighlightedSegment('The Revolutionary Bridges of Robert Maillart')"
                                        ></span>
                                        <br />
                                        <span
                                            class="text-[16px] font-normal text-black italic"
                                            :data-segment-text="'Swiss engineer Robert Maillart built some of the greatest bridges of the 20th century. His designs elegantly solved a basic engineering problem: how to support enormous weights using a slender arch.'"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Swiss engineer Robert Maillart built some of the greatest bridges of the 20th century. His designs elegantly solved a basic engineering problem: how to support enormous weights using a slender arch.',
                                                )
                                            "
                                        ></span>
                                    </h2>
                                </div>
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
                    class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-green-50 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-green-500"></div>
                    </div>
                    <div class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col gap-1">
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-green-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-green-600"></div>
                        <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-green-600"></div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-white shadow-lg"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }"
                >
                    <div class="flex flex-1 flex-col bg-white">
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
                                    <p
                                        class="text-base font-semibold tracking-wide text-gray-700 uppercase sm:text-lg"
                                        :data-segment-text="'QUESTIONS'"
                                        v-html="getHighlightedSegment('QUESTIONS')"
                                    ></p>
                                    <p
                                        class="text-xs text-gray-500"
                                        :data-segment-text="'Answer all questions based on Reading Passage 3'"
                                        v-html="getHighlightedSegment('Answer all questions based on Reading Passage 3')"
                                    ></p>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Questions Content -->
                        <div class="flex-1 overflow-y-auto pb-32">
                            <div class="space-y-8 p-4">
                                <!-- Questions 28-33 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <div class="mb-4 flex items-center space-x-2">
                                            <h3
                                                class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent"
                                                :data-segment-text="'Questions 27-33'"
                                                v-html="getHighlightedSegment('Questions 27-33')"
                                            ></h3>
                                        </div>
                                        <p class="mb-4 text-base leading-relaxed text-gray-700 sm:text-lg">
                                            <span
                                                :data-segment-text="'Reading Passage 3 has nine paragraphs A-G.'"
                                                v-html="getHighlightedSegment('Reading Passage 3 has nine paragraphs A-G.')"
                                            ></span
                                            ><br />
                                            <span
                                                :data-segment-text="'From the list of headings below choose the most suitable heading for each paragraph.'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'From the list of headings below choose the most suitable heading for each paragraph.',
                                                    )
                                                "
                                            ></span
                                            ><br />
                                            <span
                                                :data-segment-text="'Write the appropriate numbers (i-x) in boxes 27-33 on your answer sheet.'"
                                                v-html="
                                                    getHighlightedSegment('Write the appropriate numbers (i-x) in boxes 27-33 on your answer sheet.')
                                                "
                                            ></span>
                                        </p>
                                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4">
                                            <h4
                                                class="mb-3 text-base font-bold text-green-800 sm:text-lg"
                                                :data-segment-text="'List of headings:'"
                                                v-html="getHighlightedSegment('List of headings:')"
                                            ></h4>
                                            <div class="text-base sm:text-lg">
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">i</span>
                                                    <span
                                                        :data-segment-text="'The long-term impact'"
                                                        v-html="getHighlightedSegment('The long-term impact')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">ii</span>
                                                    <span
                                                        :data-segment-text="'A celebrated achievement'"
                                                        v-html="getHighlightedSegment('A celebrated achievement')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">iii</span>
                                                    <span
                                                        :data-segment-text="'Early brilliance passes unrecognised'"
                                                        v-html="getHighlightedSegment('Early brilliance passes unrecognised')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">iv</span>
                                                    <span
                                                        :data-segment-text="'Outdated methods retain popularity'"
                                                        v-html="getHighlightedSegment('Outdated methods retain popularity')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">v</span>
                                                    <span
                                                        :data-segment-text="'The basis of a new design is born'"
                                                        v-html="getHighlightedSegment('The basis of a new design is born')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">vi</span>
                                                    <span
                                                        :data-segment-text="'Frustration at never getting the design right'"
                                                        v-html="getHighlightedSegment('Frustration at never getting the design right')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">vii</span>
                                                    <span
                                                        :data-segment-text="'Further refinements meet persistent objections'"
                                                        v-html="getHighlightedSegment('Further refinements meet persistent objections')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">viii</span>
                                                    <span
                                                        :data-segment-text="'Different in all respects'"
                                                        v-html="getHighlightedSegment('Different in all respects')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">ix</span>
                                                    <span
                                                        :data-segment-text="'Bridge-makers look elsewhere'"
                                                        v-html="getHighlightedSegment('Bridge-makers look elsewhere')"
                                                    ></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-green-600">x</span>
                                                    <span
                                                        :data-segment-text="'Transport developments spark a major change'"
                                                        v-html="getHighlightedSegment('Transport developments spark a major change')"
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div id="question-27" class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow-sm">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600"
                                                >
                                                    <span class="text-base font-bold text-white sm:text-lg">27</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-2">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q27"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="i">i</option>
                                                            <option value="ii">ii</option>
                                                            <option value="iii">iii</option>
                                                            <option value="iv">iv</option>
                                                            <option value="v">v</option>
                                                            <option value="vi">vi</option>
                                                            <option value="vii">vii</option>
                                                            <option value="viii">viii</option>
                                                            <option value="ix">ix</option>
                                                            <option value="x">x</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="mb-2 text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Paragraph A'"
                                                            v-html="getHighlightedSegment('Paragraph A')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="question-28" class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow-sm">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600"
                                                >
                                                    <span class="text-base font-bold text-white sm:text-lg">28</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-2">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q28"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="i">i</option>
                                                            <option value="ii">ii</option>
                                                            <option value="iii">iii</option>
                                                            <option value="iv">iv</option>
                                                            <option value="v">v</option>
                                                            <option value="vi">vi</option>
                                                            <option value="vii">vii</option>
                                                            <option value="viii">viii</option>
                                                            <option value="ix">ix</option>
                                                            <option value="x">x</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="mb-2 text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Paragraph B'"
                                                            v-html="getHighlightedSegment('Paragraph B')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="question-29" class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow-sm">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600"
                                                >
                                                    <span class="text-base font-bold text-white sm:text-lg">29</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-2">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q29"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="i">i</option>
                                                            <option value="ii">ii</option>
                                                            <option value="iii">iii</option>
                                                            <option value="iv">iv</option>
                                                            <option value="v">v</option>
                                                            <option value="vi">vi</option>
                                                            <option value="vii">vii</option>
                                                            <option value="viii">viii</option>
                                                            <option value="ix">ix</option>
                                                            <option value="x">x</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="mb-2 text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Paragraph C'"
                                                            v-html="getHighlightedSegment('Paragraph C')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="question-30" class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow-sm">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600"
                                                >
                                                    <span class="text-base font-bold text-white sm:text-lg">30</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-2">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q30"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="i">i</option>
                                                            <option value="ii">ii</option>
                                                            <option value="iii">iii</option>
                                                            <option value="iv">iv</option>
                                                            <option value="v">v</option>
                                                            <option value="vi">vi</option>
                                                            <option value="vii">vii</option>
                                                            <option value="viii">viii</option>
                                                            <option value="ix">ix</option>
                                                            <option value="x">x</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="mb-2 text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Paragraph D'"
                                                            v-html="getHighlightedSegment('Paragraph D')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="question-31" class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow-sm">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600"
                                                >
                                                    <span class="text-base font-bold text-white sm:text-lg">31</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-2">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q31"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="i">i</option>
                                                            <option value="ii">ii</option>
                                                            <option value="iii">iii</option>
                                                            <option value="iv">iv</option>
                                                            <option value="v">v</option>
                                                            <option value="vi">vi</option>
                                                            <option value="vii">vii</option>
                                                            <option value="viii">viii</option>
                                                            <option value="ix">ix</option>
                                                            <option value="x">x</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="mb-2 text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Paragraph E'"
                                                            v-html="getHighlightedSegment('Paragraph E')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="question-32" class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow-sm">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600"
                                                >
                                                    <span class="text-base font-bold text-white sm:text-lg">32</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-2">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q32"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="i">i</option>
                                                            <option value="ii">ii</option>
                                                            <option value="iii">iii</option>
                                                            <option value="iv">iv</option>
                                                            <option value="v">v</option>
                                                            <option value="vi">vi</option>
                                                            <option value="vii">vii</option>
                                                            <option value="viii">viii</option>
                                                            <option value="ix">ix</option>
                                                            <option value="x">x</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="mb-2 text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Paragraph F'"
                                                            v-html="getHighlightedSegment('Paragraph F')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="question-33" class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow-sm">
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-600"
                                                >
                                                    <span class="text-base font-bold text-white sm:text-lg">33</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-2">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q33"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="i">i</option>
                                                            <option value="ii">ii</option>
                                                            <option value="iii">iii</option>
                                                            <option value="iv">iv</option>
                                                            <option value="v">v</option>
                                                            <option value="vi">vi</option>
                                                            <option value="vii">vii</option>
                                                            <option value="viii">viii</option>
                                                            <option value="ix">ix</option>
                                                            <option value="x">x</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="mb-2 text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Paragraph G'"
                                                            v-html="getHighlightedSegment('Paragraph G')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Questions 34-37 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <div class="mb-4 flex items-center space-x-2">
                                            <h3
                                                class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent"
                                                :data-segment-text="'Questions 34-36'"
                                                v-html="getHighlightedSegment('Questions 34-36')"
                                            ></h3>
                                        </div>
                                        <p
                                            class="mb-4 text-base leading-relaxed text-gray-700 sm:text-lg"
                                            :data-segment-text="'Complete the labels on the diagrams below using ONE or TWO WORDS from the reading passage. Write your answers in boxes 34-36 on your answer sheet.'"
                                            v-html="
                                                getHighlightedSegment(
                                                    'Complete the labels on the diagrams below using ONE or TWO WORDS from the reading passage. Write your answers in boxes 34-36 on your answer sheet.',
                                                )
                                            "
                                        ></p>
                                        <img src="/images/reading/IELTS009.png" />
                                    </div>
                                    <div class="rounded-xl border border-green-200 bg-white p-6 shadow-sm">
                                        <div class="space-y-4 text-base leading-relaxed text-gray-800 sm:text-lg">
                                            <p>
                                                <span class="mx-2 items-center"
                                                    ><span
                                                        class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 text-base font-bold text-white"
                                                        >34</span
                                                    ><input
                                                        id="question-34"
                                                        v-model="answers.q34"
                                                        type="text"
                                                        class="w-40 rounded-full border border-green-300 bg-green-50 px-3 py-1 text-center text-base font-medium transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        placeholder="____"
                                                /></span>
                                                <span class="mx-2 items-center"
                                                    ><span
                                                        class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 text-base font-bold text-white"
                                                        >35</span
                                                    ><input
                                                        id="question-35"
                                                        v-model="answers.q35"
                                                        type="text"
                                                        class="w-40 rounded-full border border-green-300 bg-green-50 px-3 py-1 text-center text-base font-medium transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        placeholder="____"
                                                /></span>
                                                <br /><br />
                                                <span class="m-2 items-center"
                                                    ><span
                                                        class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 text-base font-bold text-white"
                                                        >36</span
                                                    ><input
                                                        id="question-36"
                                                        v-model="answers.q36"
                                                        type="text"
                                                        class="w-40 rounded-full border border-green-300 bg-green-50 px-3 py-1 text-center text-base font-medium transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        placeholder="____"
                                                /></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Questions 35-40 -->
                                <div
                                    class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="mb-6">
                                        <div class="mb-4 flex items-center space-x-2">
                                            <h3
                                                class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-lg font-bold text-transparent"
                                                :data-segment-text="'Questions 37-40'"
                                                v-html="getHighlightedSegment('Questions 37-40')"
                                            ></h3>
                                        </div>
                                        <div class="rounded-xl border border-green-200 bg-white p-6 shadow-sm">
                                            <p
                                                class="mb-4 text-base leading-relaxed font-medium text-gray-800 sm:text-lg"
                                                :data-segment-text="'Complete each of the following statements with the best ending (A-G) from the box below'"
                                                v-html="
                                                    getHighlightedSegment(
                                                        'Complete each of the following statements with the best ending (A-G) from the box below',
                                                    )
                                                "
                                            ></p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div
                                            id="question-37"
                                            class="rounded-xl border border-green-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                        >
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg"
                                                >
                                                    <span class="text-sm font-bold text-white">37</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-4">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q37"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="A">A</option>
                                                            <option value="B">B</option>
                                                            <option value="C">C</option>
                                                            <option value="D">D</option>
                                                            <option value="E">E</option>
                                                            <option value="F">F</option>
                                                            <option value="G">G</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Maillart designed the hollow-box arch order to .......'"
                                                            v-html="getHighlightedSegment('Maillart designed the hollow-box arch order to .......')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            id="question-38"
                                            class="mt-4 rounded-xl border border-green-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                        >
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg"
                                                >
                                                    <span class="text-sm font-bold text-white">38</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-4">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q38"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="A">A</option>
                                                            <option value="B">B</option>
                                                            <option value="C">C</option>
                                                            <option value="D">D</option>
                                                            <option value="E">E</option>
                                                            <option value="F">F</option>
                                                            <option value="G">G</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Following the construction of the Tavanasa Bridge Maillart failed to ......'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'Following the construction of the Tavanasa Bridge Maillart failed to ......',
                                                                )
                                                            "
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            id="question-39"
                                            class="mt-4 rounded-xl border border-green-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                        >
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg"
                                                >
                                                    <span class="text-sm font-bold text-white">39</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-4">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q39"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="A">A</option>
                                                            <option value="B">B</option>
                                                            <option value="C">C</option>
                                                            <option value="D">D</option>
                                                            <option value="E">E</option>
                                                            <option value="F">F</option>
                                                            <option value="G">G</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'The transverse walls of the Flienglibach Bridge allowed Maillart to .....'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'The transverse walls of the Flienglibach Bridge allowed Maillart to .....',
                                                                )
                                                            "
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            id="question-40"
                                            class="mt-4 rounded-xl border border-green-200 bg-white p-6 shadow-md transition-all duration-200 hover:shadow-lg"
                                        >
                                            <div class="flex items-start gap-4">
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 shadow-lg"
                                                >
                                                    <span class="text-sm font-bold text-white">40</span>
                                                </div>
                                                <div class="grid grid-cols-12 gap-4">
                                                    <div class="col-span-3">
                                                        <select
                                                            v-model="answers.q40"
                                                            class="w-full rounded-full border-2 border-green-200 bg-green-50 px-3 py-2 text-base transition-colors focus:border-green-500 focus:bg-white focus:outline-none sm:text-lg"
                                                        >
                                                            <option value="">Select...</option>
                                                            <option value="A">A</option>
                                                            <option value="B">B</option>
                                                            <option value="C">C</option>
                                                            <option value="D">D</option>
                                                            <option value="E">E</option>
                                                            <option value="F">F</option>
                                                            <option value="G">G</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-9">
                                                        <p
                                                            class="text-base leading-relaxed text-gray-700 sm:text-lg"
                                                            :data-segment-text="'Of all his bridges, the Salginatobel enabled Maillart to .....'"
                                                            v-html="
                                                                getHighlightedSegment(
                                                                    'Of all his bridges, the Salginatobel enabled Maillart to .....',
                                                                )
                                                            "
                                                        ></p>
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
